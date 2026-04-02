@php use Illuminate\Support\Facades\Auth; @endphp
<x-filament::page>
    <div>
        <div class="mb-6 flex items-center gap-3">
            <input
                type="text"
                wire:model.defer="globalSearch"
                wire:keydown.enter="searchGlobal"
                placeholder="🔍 Search user folders"
                class="flex-1 px-4 py-3 rounded-xl border
                    border-gray-300 dark:border-gray-700
                    bg-white dark:bg-gray-900
                    text-gray-900 dark:text-white
                    focus:ring-2 focus:ring-orange-500 shadow"
            >

            <x-filament::button
                wire:click="searchGlobal"
                wire:loading.attr="disabled"
                color="primary"
                class="h-[48px]"
            >
                Search
            </x-filament::button>

            <span
                wire:loading
                wire:target="searchGlobal"
                class="text-sm text-gray-500"
            >
                Searching…
            </span>
        </div>

        @if(!empty($globalResults))
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-6 shadow border">
                <h3 class="text-lg font-bold mb-2">Search Results</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($globalResults as $item)
                        <a
                            href="?user={{ $item['user_id'] }}
&folder={{ urlencode($item['folder']) }}
@if(!empty($item['subfolder']))
&subfolder={{ urlencode($item['subfolder']) }}
@endif
&from_search=1"
                            class="p-3 rounded-lg border hover:bg-orange-100 dark:hover:bg-orange-900/30 transition"
                        >
                            <div class="text-sm font-semibold">
                                {{ $item['name'] }}
                            </div>

                            <div class="text-xs text-gray-500">
                                {{ strtoupper($item['type']) }} in {{ $item['user'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        <br>

        {{-- Step 1: Show Users --}}
        @if (!$selectedUser)
            <div wire:ignore.self>
                <h2 class="text-xl font-bold mb-4">Select User</h2>
                <div class="grid gap-6"
                    style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">

                    @foreach ($managerUsers as $user)
                        <div
                            data-url="{{ isset($selectedManager)
                                ? '?manager='.$selectedManager->id.'&user='.$user->id
                                : '?user='.$user->id }}"
                            onclick="window.location.href=this.dataset.url"
                            class="app-card cursor-pointer
                                flex items-center justify-between
                                px-4 py-4
                                h-[160px] w-full
                                rounded-xl
                                border border-gray-200 dark:border-gray-700
                                bg-white dark:bg-gray-800
                                hover:shadow-lg transition">

                            {{-- LEFT: User info --}}
                            <div class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 rounded-full overflow-hidden border
                                    border-gray-300 dark:border-gray-600
                                    bg-gray-100">
                                    <img
                                        src="{{ $user->profile_photo
                                            ? asset('storage/' . $user->profile_photo)
                                            : asset('images/user_icon2.png') }}"
                                        alt="User Avatar"
                                        class="w-full h-full object-cover"
                                    />
                                </div>

                                <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white truncate max-w-[120px]">
                                    {{ $user->name }}
                                </div>
                            </div>

                            {{-- RIGHT: Photo count --}}
                            <div class="flex flex-col items-center justify-center ml-auto mr-6 text-center">
                                <div class="text-4xl font-bold text-gray-900 dark:text-white leading-none">
                                    {{ $user->photo_count ?? 0 }}
                                </div>

                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    Total Photos
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

        {{-- Step 2: Show Folders --}}
        @elseif ($selectedUser && !$selectedFolder)
            <h2 class="text-xl font-bold mb-4">Folders of {{ $selectedUser->name }}</h2>

            <div class="mb-4 flex justify-between items-center">
                {{-- Back to User list --}}
                <x-filament::button 
                    tag="a" 
                    href="{{ url()->current() }}" 
                    color="primary" 
                    icon="heroicon-o-arrow-left">
                    Back to Users
                </x-filament::button>

                {{-- Download Today’s Folders --}}
                
            </div>

            <div class="flex items-center justify-between mb-3">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-main" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count-main">0</span>)
                </label>

                @if($this->canDeletePhotos())
                    <button
                        onclick="deleteSelected()"
                        class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                        Delete
                    </button>
                @endif
            </div>
            
            @foreach ($folders as $group => $items)
                <div class="mb-2 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $group }}</span>
                        <span class="text-sm">▼</span>
                    </button>
                    <div class="accordion-content px-4 py-2">
                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                            @foreach ($items as $folder)
                                <div class="flex flex-col items-center text-center">

                                    <!-- FIXED SIZE CONTAINER -->
                                    <div class="relative w-24 h-24 flex items-center justify-center">

                                        <!-- Folder Icon (clickable) -->
                                        <a href="?user={{ $selectedUser->id }}&folder={{ urlencode($folder['path']) }}"
                                        class="w-full h-full flex items-center justify-center z-0">
                                            <x-heroicon-s-folder class="w-20 h-20 text-yellow-500" style="color: #facc15;"/>
                                        </a>

                                        <!-- Checkbox (top-left) -->
                                        <input
                                            type="checkbox"
                                            class="folder-checkbox absolute top-1 left-1 z-20"
                                            data-type="folder"
                                            value="{{ $folder['path'] }}"
                                        >

                                        <!-- Download (bottom-right) -->
                                        <a href="{{ route('download-folder', ['path' => $folder['path']]) }}"
                                            class="absolute bottom-2 right-2 z-50 p-1 rounded-full bg-white shadow hover:bg-gray-200"
                                            title="Download Folder">
                                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700 dark:text-white" />
                                        </a>

                                    </div>

                                    <!-- Folder Name -->
                                    <span class="mt-1 text-xs text-black truncate w-24">
                                        {{ \Illuminate\Support\Str::limit($folder['name'], 10) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- pagination (images use $total & $perPage) --}}
            @if ($total > $datesPerPage)
                @php
                    $totalPages = ceil($total / $datesPerPage);
                @endphp

                <div class="mt-6 flex items-center justify-center gap-2 text-sm">

                    {{-- Previous --}}
                    <a
                        href="{{ $page > 1 ? request()->fullUrlWithQuery(['page' => $page - 1]) : '#' }}"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg border
                            {{ $page > 1
                                ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    >
                        <span>←</span>
                        <span>Previous</span>
                    </a>

                    {{-- Page Numbers (Scrollable) --}}
                    @php
                        $totalPages = ceil($total / $datesPerPage);
                        $window = 2;

                        $start = max(1, $page - $window);
                        $end   = min($totalPages, $page + $window);
                    @endphp

                    {{-- First --}}
                    @if ($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                            class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                            First
                        </a>
                    @endif

                    {{-- Left dots --}}
                    @if ($start > 1)
                        <span class="px-2">…</span>
                    @endif

                    {{-- Page Numbers --}}
                    @for ($i = $start; $i <= $end; $i++)
                        <a
                            href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="min-w-[40px] text-center px-3 py-2 rounded-lg border transition
                                {{ $i === $page
                                    ? 'bg-orange-500 text-white border-orange-500'
                                    : 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200' }}"
                        >
                            {{ $i }}
                        </a>
                    @endfor

                    {{-- Right dots --}}
                    @if ($end < $totalPages)
                        <span class="px-2">…</span>
                    @endif

                    {{-- Last --}}
                    @if ($page < $totalPages)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}"
                            class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                            Last
                        </a>
                    @endif

                    {{-- Next --}}
                    <a
                        href="{{ $page < $totalPages ? request()->fullUrlWithQuery(['page' => $page + 1]) : '#' }}"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg border
                            {{ $page < $totalPages
                                ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    >
                        <span>Next</span>
                        <span>→</span>
                    </a>

                </div>
            @endif

        {{-- Step 3: Subfolders + Images --}}
        @elseif ($selectedFolder && !$selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedFolder) }}</h2>

            <div class="mb-4">
                <x-filament::button
                    tag="a"
                    href="{{ request()->url() . '?user=' . $selectedUser->id }}"
                    color="primary"
                    icon="heroicon-o-arrow-left">
                    Back to Main Folders
                </x-filament::button>
            </div>

            {{-- Select All + Download (images in this folder/page) --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count">0</span>)
                </label>
                <div class="flex gap-2">

                    <button id="download-selected"
                        class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                        Download
                    </button>

                    @if($this->canDeletePhotos())
                        <button
                            onclick="deleteSelected()"
                            class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                            Delete
                        </button>
                    @endif

                </div>
            </div>

            {{-- ITEMS grouped by date --}}
            @foreach ($items as $date => $groupItems)
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $date }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            @foreach ($groupItems as $item)
                                @if ($item['type'] === 'folder')
                                    {{-- folder card --}}
                                    <div class="relative w-32 h-36 bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-xs font-medium overflow-hidden flex flex-col">
                                        {{-- Top row: checkbox + download button --}}
                                        <div class="flex justify-between items-start p-1">
                                            <input type="checkbox"
                                                class="folder-checkbox"
                                                style="transform: scale(1.2);"
                                                data-type="folder"
                                                value="{{ $item['path'] }}">

                                            <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                                            title="Download Subfolder">
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700 dark:text-white" 
                                        />
                                            </a>
                                        </div>

                                        {{-- Folder icon + name --}}
                                        @php
                                            $currentSubfolder = request()->get('subfolder');
                                            $folderName = basename($item['path']);

                                            if ($currentSubfolder) {
                                                $parts = explode('/', $currentSubfolder);

                                                if (end($parts) === $folderName) {
                                                    $nextSubfolder = $currentSubfolder;
                                                } else {
                                                    $nextSubfolder = trim($currentSubfolder . '/' . $folderName, '/');
                                                }
                                            } else {
                                                $nextSubfolder = $folderName;
                                            }
                                        @endphp
                                        <a href="?user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($nextSubfolder) }}"
                                        class="flex flex-col items-center justify-center flex-1 px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate w-full text-center" title="{{ $item['name'] }}">
                                                {{ \Illuminate\Support\Str::limit($item['name'], 10) }}
                                            </div>
                                        </a>
                                    </div>

                                @else
                                    {{-- media card (image or video) --}}
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">

                                        {{-- Top controls --}}
                                        <div class="flex justify-between items-start p-1 bg-white/90 dark:bg-gray-800/90">

                                            {{-- Checkbox --}}
                                            <input type="checkbox"
                                                    class="image-checkbox 
                                                        text-blue-600 dark:text-blue-400 
                                                        bg-white dark:bg-gray-800 
                                                        border-gray-300 dark:border-gray-600"
                                                    value="{{ asset('storage/' . $item['path']) }}">

                                            <div class="flex items-center gap-1">

                                                {{-- Delete button (only if permission granted) --}}
                                                @if($this->canDeletePhotos())
                                                    <button
                                                        wire:click="deletePhoto('{{ $item['path'] }}')"
                                                        onclick="if(!confirm('Delete this media file?')) event.stopImmediatePropagation()"
                                                        class="p-1 rounded hover:bg-red-100 text-red-600"
                                                        title="Delete Photo"
                                                    >
                                                        <x-heroicon-o-trash class="w-4 h-4"/>
                                                    </button>
                                                @endif

                                                {{-- Properties button --}}
                                                <button
                                                    data-name="{{ $item['name'] }}"
                                                    data-date="{{ $item['created_at'] ?? 'N/A' }}"
                                                    data-path="{{ asset('storage/' . $item['path']) }}"
                                                    onclick="openPropertiesModal(this)"
                                                    class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                                                    title="More options"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-gray-700 dark:text-white"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
                                                    </svg>
                                                </button>

                                            </div>
                                        </div>

                                        @if ($item['type'] === 'image')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank" class="w-full h-full block">
                                                <img src="{{ asset('storage/' . $item['path']) }}" 
                                                    class="w-full h-full object-cover rounded" 
                                                    alt="{{ $item['name'] }}">
                                            </a>
                                        @elseif ($item['type'] === 'video')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank" class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded" controls>
                                                    <source src="{{ asset('storage/' . $item['path']) }}" type="video/mp4">
                                                </video>
                                            </a>
                                        @elseif ($item['type'] === 'pdf')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 rounded text-gray-900 dark:text-white rounded text-center p-2 text-xs hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                                title="{{ $item['name'] }}">
                                                <div class="text-3xl">📄</div>
                                                <div class="mt-1 truncate w-full">{{ \Illuminate\Support\Str::limit($item['name'], 10) }}</div>
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- pagination (images use $total & $perPage) --}}
            @php
                $totalPages = ceil($total / $perPage);
                $window = 2;

                $start = max(1, $page - $window);
                $end   = min($totalPages, $page + $window);
            @endphp

            @if ($total > $perPage)
                <div class="mt-6 flex items-center justify-center gap-2 text-sm">

                    {{-- Previous --}}
                    <a
                        href="{{ $page > 1 ? request()->fullUrlWithQuery(['page' => $page - 1]) : '#' }}"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg border
                            {{ $page > 1
                                ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    >
                        <span>←</span>
                        <span>Previous</span>
                    </a>

                    {{-- Page Numbers --}}
                    {{-- First --}}
                    @if ($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                            class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                            First
                        </a>
                    @endif

                    {{-- Left dots --}}
                    @if ($start > 1)
                        <span class="px-2">…</span>
                    @endif

                    {{-- Page numbers --}}
                    @for ($i = $start; $i <= $end; $i++)
                        <a
                            href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="min-w-[40px] text-center px-3 py-2 rounded-lg border transition
                                {{ $i === $page
                                    ? 'bg-orange-500 text-white border-orange-500'
                                    : 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200' }}"
                        >
                            {{ $i }}
                        </a>
                    @endfor

                    {{-- Right dots --}}
                    @if ($end < $totalPages)
                        <span class="px-2">…</span>
                    @endif

                    {{-- Last --}}
                    @if ($page < $totalPages)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}"
                            class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                            Last
                        </a>
                    @endif

                    {{-- Next --}}
                    <a
                        href="{{ $page < ceil($total / $perPage) ? request()->fullUrlWithQuery(['page' => $page + 1]) : '#' }}"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg border
                            {{ $page < ceil($total / $perPage)
                                ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    >
                        <span>Next</span>
                        <span>→</span>
                    </a>

                </div>
            @endif

        {{-- Step 4: Inside Subfolder --}}
        @elseif ($selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedSubfolder) }}</h2>

            <div class="mb-4">
                @php
                    $segments = explode('/', $selectedSubfolder);
                    array_pop($segments);

                    $parentSubfolder = implode('/', $segments);
                    $parentName = $parentSubfolder
                        ? basename($parentSubfolder)
                        : basename($selectedFolder);
                @endphp

                <x-filament::button 
                    tag="a"
                    href="?user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}{{ $parentSubfolder ? '&subfolder=' . urlencode($parentSubfolder) : '' }}"
                    color="primary"
                    icon="heroicon-o-arrow-left">
                    Back to {{ $parentName }}
                </x-filament::button>
            </div>

            {{-- Select All + Download for subfolder-level images --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count-subfolder">0</span>)
                </label>

                <div class="flex gap-2">

                    <button id="download-selected-subfolder"
                        class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                        Download
                    </button>

                    @if($this->canDeletePhotos())
                        <button
                            onclick="deleteSelected()"
                            class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                            Delete
                        </button>
                    @endif

                </div>
            </div>

            {{-- same merged items UI as above, but links refer to deeper levels --}}
            @foreach ($items as $date => $groupItems)
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $date }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            @foreach ($groupItems as $item)
                                @if ($item['type'] === 'folder')
                                    <div class="relative w-32 h-36 bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-xs font-medium overflow-hidden flex flex-col">
                                        {{-- Top row: checkbox + download button --}}
                                        <div class="flex justify-between items-start p-1">
                                            <input type="checkbox"
                                                class="folder-checkbox"
                                                style="transform: scale(1.2);"
                                                data-type="folder"
                                                value="{{ $item['path'] }}">

                                            <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                                            title="Download Subfolder">
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700 dark:text-white" 
                                        />
                                            </a>
                                        </div>

                                        {{-- Folder icon + name --}}
                                        @php
                                            $currentSubfolder = request()->get('subfolder');
                                            $folderName = basename($item['path']);

                                            if ($currentSubfolder) {
                                                $parts = explode('/', $currentSubfolder);

                                                if (end($parts) === $folderName) {
                                                    $nextSubfolder = $currentSubfolder;
                                                } else {
                                                    $nextSubfolder = trim($currentSubfolder . '/' . $folderName, '/');
                                                }
                                            } else {
                                                $nextSubfolder = $folderName;
                                            }
                                        @endphp
                                        <a href="?user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($nextSubfolder) }}"
                                        class="flex flex-col items-center justify-center flex-1 px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate w-full text-center" title="{{ $item['name'] }}">
                                                {{ \Illuminate\Support\Str::limit($item['name'], 10) }}
                                            </div>
                                        </a>
                                    </div>
                                @else
                                    {{-- media card (image / video / pdf) --}}
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">

                                        {{-- Top controls --}}
                                        <div class="flex justify-between items-start p-1 bg-white/90 dark:bg-gray-800/90">

                                            {{-- Checkbox --}}
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="image-checkbox-subfolder
                                                        text-blue-600 dark:text-blue-400
                                                        bg-white dark:bg-gray-800
                                                        border-gray-300 dark:border-gray-600"
                                                    value="{{ asset('storage/' . $item['path']) }}">

                                                @if(isset($item['linked']) && $item['linked'])
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">
                                                        Linked
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Right side actions --}}
                                            <div class="flex items-center gap-1">

                                                {{-- Delete button (permission based) --}}
                                                @if($this->canDeletePhotos())
                                                    <button
                                                        wire:click="deletePhoto('{{ $item['path'] }}')"
                                                        onclick="if(!confirm('Delete this media file?')) event.stopImmediatePropagation()"
                                                        class="p-1 rounded hover:bg-red-100 text-red-600"
                                                        title="Delete Photo"
                                                    >
                                                        <x-heroicon-o-trash class="w-4 h-4"/>
                                                    </button>
                                                @endif

                                                {{-- Properties button --}}
                                                <button
                                                    data-name="{{ $item['name'] }}"
                                                    data-date="{{ $item['created_at'] ?? 'N/A' }}"
                                                    data-path="{{ asset('storage/' . $item['path']) }}"
                                                    onclick="openPropertiesModal(this)"
                                                    class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                                                    title="More options"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4 text-gray-700 dark:text-white"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
                                                    </svg>
                                                </button>

                                            </div>
                                        </div>

                                        {{-- Media preview --}}
                                        @if ($item['type'] === 'image')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank" class="w-full h-full block">
                                                <img
                                                    src="{{ asset('storage/' . $item['path']) }}"
                                                    class="w-full h-full object-cover rounded"
                                                    alt="{{ $item['name'] }}"
                                                >
                                            </a>

                                        @elseif ($item['type'] === 'video')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank" class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded" controls>
                                                    <source src="{{ asset('storage/' . $item['path']) }}" type="video/mp4">
                                                </video>
                                            </a>

                                        @elseif ($item['type'] === 'pdf')
                                            <a href="{{ asset('storage/' . $item['path']) }}"
                                            target="_blank"
                                            class="w-full h-full flex flex-col items-center justify-center
                                                    bg-gray-100 dark:bg-gray-800
                                                    text-gray-900 dark:text-white
                                                    rounded text-center p-2 text-xs
                                                    hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                            title="{{ $item['name'] }}">
                                                <div class="text-3xl">📄</div>
                                                <div class="mt-1 truncate w-full">
                                                    {{ \Illuminate\Support\Str::limit($item['name'], 10) }}
                                                </div>
                                            </a>
                                        @endif

                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- pagination (same as above) --}}
            @php
                $totalPages = ceil($total / $perPage);
                $window = 2;

                $start = max(1, $page - $window);
                $end   = min($totalPages, $page + $window);
            @endphp

            @if ($total > $perPage)
                <div class="mt-6 flex items-center justify-center gap-2 text-sm">

                    {{-- Previous --}}
                    <a
                        href="{{ $page > 1 ? request()->fullUrlWithQuery(['page' => $page - 1]) : '#' }}"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg border
                            {{ $page > 1
                                ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    >
                        <span>←</span>
                        <span>Previous</span>
                    </a>

                    {{-- First --}}
                    @if ($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                            class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                            First
                        </a>
                    @endif

                    {{-- Left dots --}}
                    @if ($start > 1)
                        <span class="px-2">…</span>
                    @endif

                    {{-- Page Numbers --}}
                    @for ($i = $start; $i <= $end; $i++)
                        <a
                            href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="min-w-[40px] text-center px-3 py-2 rounded-lg border transition
                                {{ $i === $page
                                    ? 'bg-orange-500 text-white border-orange-500'
                                    : 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200' }}"
                        >
                            {{ $i }}
                        </a>
                    @endfor

                    {{-- Right dots --}}
                    @if ($end < $totalPages)
                        <span class="px-2">…</span>
                    @endif

                    {{-- Last --}}
                    @if ($page < $totalPages)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}"
                            class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                            Last
                        </a>
                    @endif

                    {{-- Next --}}
                    <a
                        href="{{ $page < $totalPages ? request()->fullUrlWithQuery(['page' => $page + 1]) : '#' }}"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg border
                            {{ $page < $totalPages
                                ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                    >
                        <span>Next</span>
                        <span>→</span>
                    </a>

                </div>
            @endif
        @endif
    </div>

    <!-- Properties Modal -->
    <div id="propertiesModal"
        class="hidden fixed inset-0 bg-black/50 dark:bg-black/60 
            flex items-center justify-center z-50 p-4 transition">

        <div class="rounded-lg shadow-2xl w-full max-w-sm relative p-6
                    bg-white dark:bg-gray-800
                    border border-gray-200 dark:border-gray-700
                    text-gray-900 dark:text-white">

            {{-- Close Button --}}
            <button onclick="closePropertiesModal()"
                class="absolute top-3 right-3 text-gray-600 dark:text-white 
                    hover:text-gray-800 dark:hover:text-white 
                    text-2xl leading-none focus:outline-none transition">
                &times;
            </button>

            <h3 class="text-lg font-bold mb-4 text-center">
                File Properties
            </h3>

            <div class="space-y-3 text-sm break-words overflow-hidden">

                {{-- File Name --}}
                <p class="truncate">
                    <strong>Name:</strong>
                    <span id="prop-name"
                        class="break-words block text-gray-700 dark:text-white">
                    </span>
                </p>

                {{-- Created At --}}
                <p>
                    <strong>Created At:</strong>
                    <span id="prop-date" class="text-gray-700 dark:text-white"></span>
                </p>

                {{-- File Path --}}
                <p>
                    <strong>Path:</strong>
                    <span id="prop-path"
                        class="break-all block max-h-24 overflow-y-auto p-2 rounded
                            bg-blue-50 dark:bg-blue-900/30
                            text-blue-600 dark:text-blue-300 border 
                            border-blue-200 dark:border-blue-800">
                    </span>
                </p>
            </div>
        </div>
    </div>

</x-filament::page>

<script>
    const mainCheckboxes = document.querySelectorAll('.folder-checkbox');
    const selectAllMain = document.getElementById('select-all-main');

    if (selectAllMain) {
        selectAllMain.addEventListener('change', function () {
            mainCheckboxes.forEach(cb => cb.checked = this.checked);
            document.getElementById('selected-count-main').textContent =
                document.querySelectorAll('.folder-checkbox:checked').length;
        });
    }

    mainCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            document.getElementById('selected-count-main').textContent =
                document.querySelectorAll('.folder-checkbox:checked').length;
        });
    });

    function deleteSelected() {
        if (!confirm('Delete selected items?')) return;

        const selected = [
            ...document.querySelectorAll('.image-checkbox:checked'),
            ...document.querySelectorAll('.image-checkbox-subfolder:checked'),
            ...document.querySelectorAll('.folder-checkbox:checked')
        ].map(cb => ({
            path: cb.value,
            type: cb.dataset.type || 'file'
        }));

        if (!selected.length) {
            alert('Please select items to delete');
            return;
        }

        Livewire.dispatch('bulkDeleteMedia', { items: selected });
    }
    function openPropertiesModal(btn) {
        document.getElementById('prop-name').innerText = btn.dataset.name;
        document.getElementById('prop-date').innerText = btn.dataset.date;
        document.getElementById('prop-path').innerText = btn.dataset.path;
        document.getElementById('propertiesModal').classList.remove('hidden');
    }

    function closePropertiesModal() {
        document.getElementById('propertiesModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Accordion toggle
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', function () {
                const content = this.nextElementSibling;
                content.classList.toggle('hidden');
                this.querySelector('span:last-child').classList.toggle('rotate-180');
            });
        });

        // Function to update count text
        const updateCount = (selector, countId) => {
            const count = document.querySelectorAll(selector).length;
            document.getElementById(countId).textContent = `${count} selected`;
        };

        // -------- Folder Level --------
        const folderCheckboxes = document.querySelectorAll('.image-checkbox, .folder-checkbox');
        const selectAll = document.getElementById('select-all');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                folderCheckboxes.forEach(cb => cb.checked = this.checked);
                updateCount('.image-checkbox:checked, .folder-checkbox:checked', 'selected-count');
                if (!this.checked) document.getElementById('selected-count').textContent = '0 selected';
            });
        }

        folderCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateCount('.image-checkbox:checked, .folder-checkbox:checked', 'selected-count');
            });
        });

        // -------- Subfolder Level (images + folders) --------
        const subfolderCheckboxes = document.querySelectorAll('.image-checkbox-subfolder, .folder-checkbox, .folder-checkbox-subfolder');
        const selectAllSubfolder = document.getElementById('select-all-subfolder');

        if (selectAllSubfolder) {
            selectAllSubfolder.addEventListener('change', function () {
                subfolderCheckboxes.forEach(cb => cb.checked = this.checked);
                updateCount('.image-checkbox-subfolder:checked, .folder-checkbox:checked, .folder-checkbox-subfolder:checked', 'selected-count-subfolder');
                if (!this.checked) document.getElementById('selected-count-subfolder').textContent = '0 selected';
            });
        }

        subfolderCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateCount('.image-checkbox-subfolder:checked, .folder-checkbox:checked, .folder-checkbox-subfolder:checked', 'selected-count-subfolder');
            });
        });

        // -------- Download Selected --------
        const download = (selector) => {
            const selected = [...document.querySelectorAll(selector.replace(/([^,]+)/g, '$1:checked'))].map(cb => cb.value);
            if (!selected.length) return alert('Please select at least one item to download.');

            selected.forEach((url, i) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }, i * 300);
            });
        };

        document.getElementById('download-selected')?.addEventListener('click', () =>
            download('.image-checkbox, .folder-checkbox')
        );
        document.getElementById('download-selected-subfolder')?.addEventListener('click', () =>
            download('.image-checkbox-subfolder, .folder-checkbox, .folder-checkbox-subfolder')
        );
    });
</script>