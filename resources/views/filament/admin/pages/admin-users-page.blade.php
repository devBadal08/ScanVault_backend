@php use Illuminate\Support\Facades\Auth; @endphp

<x-filament::page>
    <div>

    <div class="mb-6">
    <input
        type="text"
        wire:model.live="globalSearch"
        placeholder="🔍 Global Search (users, folders, images...)"
        class="w-full px-4 py-3 rounded-xl border
        border-gray-300 dark:border-gray-700
        bg-white dark:bg-gray-900
        text-gray-900 dark:text-white
        focus:ring-2 focus:ring-orange-500 shadow"
    >
</div>

@if(!empty($globalResults))

    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-6 shadow border">

        <h3 class="text-lg font-bold mb-2">Search Results</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">

            @foreach($globalResults as $item)

                <a
                    href="?user={{ $item['user_id'] }}&folder={{ urlencode($item['path']) }}"
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


        {{-- Step 1: Show Admin Users + Managers --}}
        @if (!$selectedManager && !$selectedUser)
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Select Manager or Admin User
            </h2>

            <div class="grid gap-2 mb-6"
                 style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">

                {{-- Managers --}}
                @foreach ($managers as $manager)
                    <div onclick="window.location.href='?manager={{ $manager->id }}'"
                        class="flex flex-col items-center justify-center w-32 h-32 rounded-lg shadow border
                               bg-white dark:bg-gray-800
                               border-gray-200 dark:border-gray-700
                               hover:bg-orange-100 dark:hover:bg-orange-900/40
                               text-center overflow-hidden cursor-pointer transition group">

                        <div class="text-3xl">👨‍💼</div>

                        <div class="mt-1 text-[10px] font-semibold 
                                    text-gray-800 dark:text-gray-200
                                    truncate w-full px-1">
                            {{ $manager->name }}
                        </div>
                    </div>
                @endforeach

                {{-- Admin Users --}}
                @foreach ($adminUsers as $user)
                    <a href="?user={{ $user->id }}"
                        class="flex flex-col items-center justify-center text-center font-medium rounded-lg p-2
                               text-gray-900 dark:text-gray-100
                               transition duration-150 ease-in-out
                               hover:text-blue-700 dark:hover:text-blue-300">

                        <x-heroicon-s-user class="w-20 h-16 text-blue-600" style="color:#1D4ED8;" />

                        <span class="mt-1 text-sm truncate w-24">
                            {{ $user->name }}
                        </span>
                    </a>
                @endforeach
            </div>

        {{-- Step 2: Show Users under Manager --}}
        @elseif ($selectedManager && !$selectedUser)

            <div class="mb-4">
                <x-filament::button 
                    tag="a" 
                    href="{{ url()->current() }}"
                    color="primary" 
                    icon="heroicon-o-arrow-left">
                    Back
                </x-filament::button>
            </div>

            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Users under {{ $selectedManager->name }}
            </h2>

            <div class="grid gap-2"
                 style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">

                @foreach ($users as $user)
                    <a href="?manager={{ $selectedManager->id }}&user={{ $user->id }}"
                        class="flex flex-col items-center justify-center text-center font-medium rounded-lg p-2
                               text-gray-900 dark:text-gray-100
                               transition duration-150 ease-in-out
                               hover:text-blue-700 dark:hover:text-blue-300" style="color:#1D4ED8;">

                        <x-heroicon-s-user class="w-20 h-16 text-blue-600 dark:text-blue-300" />

                        <span class="mt-1 text-sm truncate w-24">
                            {{ $user->name }}
                        </span>
                    </a>
                @endforeach
            </div>

        {{-- Step 3: Show Folders (grouped by date) --}}
        @elseif ($selectedUser && !$selectedFolder)

            <div class="mb-4">
                <x-filament::button 
                    tag="a" 
                    href="?{{ $selectedManager ? 'manager='.$selectedManager->id : '' }}" 
                    color="primary" 
                    icon="heroicon-o-arrow-left">
                    Back to Users
                </x-filament::button>
            </div>

            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Folders of {{ $selectedUser->name }}
            </h2>

            <div class="mb-4 flex justify-end">
                <x-filament::button
                    tag="a"
                    href="{{ route('download-today-folders') }}"
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                >
                    Download Today’s Folders
                </x-filament::button>
            </div>

            @foreach ($folders as $group => $items)
                <div class="mb-2 border rounded 
                            border-gray-200 dark:border-gray-700 
                            bg-white dark:bg-gray-800">

                    {{-- Accordion header --}}
                    <button class="w-full text-left px-4 py-2 flex justify-between items-center
                                bg-gray-100 dark:bg-gray-700 
                                hover:bg-gray-200 dark:hover:bg-gray-600 
                                accordion-header 
                                text-gray-900 dark:text-gray-100">
                        <span class="text-sm font-semibold">{{ $group }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    {{-- Accordion content --}}
                    <div class="accordion-content px-4 py-2 bg-white dark:bg-gray-800">
                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">

                            @foreach ($items as $folder)
                                <div class="flex flex-col items-center justify-center text-center relative">

                                    {{-- Download folder --}}
                                    <a href="{{ route('download-folder', ['path' => $folder['path']]) }}"
                                        class="self-end -mb-6 mr-6 z-10 p-1 rounded-full
                                            hover:bg-gray-200 dark:hover:bg-gray-600 
                                            transition"
                                        title="Download Folder">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700 dark:text-white" />
                                    </a>

                                    {{-- Linked badge --}}
                                    @if(isset($folder['linked']) && $folder['linked'])
                                        <span class="absolute top-0 left-0 
                                                    bg-blue-500 text-white text-xs px-1 rounded-br">
                                            Linked
                                        </span>
                                    @endif

                                    {{-- Folder card --}}
                                    <a href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}&folder={{ urlencode($folder['path']) }}"
                                        class="flex flex-col items-center hover:text-yellow-600 dark:hover:text-yellow-400 transition duration-150 ease-in-out">

                                        <div class="w-24 h-24 flex items-center justify-center">
                                            <x-heroicon-s-folder class="w-16 h-16 text-yellow-500" style="color: #facc15;"/>
                                        </div>

                                        <span class="mt-1 text-xs truncate w-24
                                                    text-gray-900 dark:text-gray-200"
                                            title="{{ $folder['name'] }}">
                                            {{ \Illuminate\Support\Str::limit($folder['name'], 10) }}
                                        </span>
                                    </a>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Pagination --}}
            @if ($total > $perPage)
                <div class="mt-4 flex justify-center space-x-2">

                    @if ($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
                            class="px-3 py-1 rounded
                                bg-gray-200 dark:bg-gray-700
                                hover:bg-gray-300 dark:hover:bg-gray-600
                                text-gray-900 dark:text-gray-100">
                            Previous
                        </a>
                    @endif

                    @for ($i = 1; $i <= ceil($total / $perPage); $i++)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="px-3 py-1 rounded
                                {{ $i == $page 
                                        ? 'bg-blue-500 text-white' 
                                        : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100' }}">
                            {{ $i }}
                        </a>
                    @endfor

                    @if ($page < ceil($total / $perPage))
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
                            class="px-3 py-1 rounded
                                bg-gray-200 dark:bg-gray-700
                                hover:bg-gray-300 dark:hover:bg-gray-600
                                text-gray-900 dark:text-gray-100">
                            Next
                        </a>
                    @endif

                </div>
            @endif

        {{-- Step 4: Inside a Folder --}}
        @elseif ($selectedFolder && !$selectedSubfolder)

            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Content in {{ basename($selectedFolder) }}
            </h2>

            <div class="mb-4">
                <x-filament::button 
                    tag="a" 
                    href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}" 
                    color="primary" 
                    icon="heroicon-o-arrow-left">
                    Back
                </x-filament::button>
            </div>

            {{-- Select All + Download --}}
            <div class="flex items-center justify-between mb-2 
                        text-gray-900 dark:text-gray-100">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all" class="form-checkbox 
                            text-blue-600 dark:text-blue-400 
                            bg-white dark:bg-gray-800 
                            border-gray-300 dark:border-gray-600">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count">0</span>)
                </label>

                <button id="download-selected"
                    class="inline-flex items-center justify-center px-4 py-2 rounded
                        bg-primary-600 text-white 
                        hover:bg-primary-700 dark:hover:bg-primary-500 
                        transition">
                    Download
                </button>
            </div>

            {{-- Display grouped items --}}
            @foreach ($items as $date => $groupItems)

                <div class="mb-4 rounded border 
                            border-gray-200 dark:border-gray-700 
                            bg-white dark:bg-gray-800">

                    {{-- Accordion Header --}}
                    <button class="w-full text-left px-4 py-2 flex justify-between items-center
                                bg-gray-100 dark:bg-gray-700
                                hover:bg-gray-200 dark:hover:bg-gray-600
                                accordion-header
                                text-gray-900 dark:text-gray-100">
                        <span class="text-sm font-semibold">{{ $date }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    {{-- Accordion Content --}}
                    <div class="accordion-content px-4 py-3 bg-white dark:bg-gray-800">
                        <div class="grid gap-3" 
                            style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">

                            @foreach ($groupItems as $item)

                                {{-- ========== FOLDER CARD ========== --}}
                                @if ($item['type'] === 'folder')
                                    <div class="relative w-40 h-36 rounded shadow border 
                                                bg-white dark:bg-gray-800
                                                border-gray-300 dark:border-gray-700 
                                                text-xs font-medium overflow-hidden flex flex-col">

                                        {{-- Top row inside folder card --}}
                                        <div class="flex justify-between items-start p-1">

                                            {{-- Checkbox --}}
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="folder-checkbox 
                                                        text-blue-600 dark:text-blue-400 
                                                        bg-white dark:bg-gray-800 
                                                        border-gray-300 dark:border-gray-600"
                                                    value="{{ route('download-folder', ['path' => $item['path']]) }}">

                                                {{-- Linked badge --}}
                                                @if(isset($item['linked']) && $item['linked'])
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">
                                                        Linked
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Download button --}}
                                            <a href="{{ route('download-folder', ['path' => $item['path']]) }}"
                                                class="p-1 rounded-full 
                                                    hover:bg-gray-200 dark:hover:bg-gray-600"
                                                title="Download Subfolder">
                                                <x-heroicon-o-arrow-down-tray 
                                                    class="w-5 h-5 text-gray-700 dark:text-white" />
                                            </a>
                                        </div>

                                        {{-- Folder icon + name --}}
                                        <a href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($item['path']) }}"
                                            class="flex flex-col items-center justify-center flex-1 
                                                text-gray-900 dark:text-gray-100">

                                            <div class="text-3xl">📁</div>

                                            <div class="mt-1 truncate w-full text-center"
                                                title="{{ $item['name'] }}">
                                                {{ \Illuminate\Support\Str::limit($item['name'], 10) }}
                                            </div>
                                        </a>
                                    </div>

                                {{-- ========== FILE CARD (IMAGE / VIDEO / PDF) ========== --}}
                                @else
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group 
                                                bg-white dark:bg-gray-800
                                                border border-gray-300 dark:border-gray-700">

                                        {{-- Checkbox + badge + menu button --}}
                                        <div class="flex justify-between items-start p-1">

                                            {{-- Checkbox --}}
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="image-checkbox 
                                                        text-blue-600 dark:text-blue-400 
                                                        bg-white dark:bg-gray-800 
                                                        border-gray-300 dark:border-gray-600"
                                                    value="{{ asset('storage/' . $item['path']) }}">

                                                {{-- Linked badge --}}
                                                @if(isset($item['linked']) && $item['linked'])
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">
                                                        Linked
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- 3-dot menu button --}}
                                            <button onclick="openPropertiesModal('{{ $item['name'] }}', '{{ $item['type'] }}', '{{ $item['created_at'] ?? 'N/A' }}', '{{ asset('storage/' . $item['path']) }}')"
                                                class="p-1 rounded-full 
                                                    hover:bg-gray-200 dark:hover:bg-gray-600"
                                                title="More options">
                                                <svg xmlns="http://www.w3.org/2000/svg" 
                                                    class="w-4 h-4 text-gray-700 dark:text-white" 
                                                    fill="currentColor" 
                                                    viewBox="0 0 20 20">
                                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- IMAGE --}}
                                        @if ($item['type'] === 'image')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank" class="block w-full h-full">
                                                <img src="{{ asset('storage/' . $item['path']) }}"
                                                    class="w-full h-full object-cover rounded"
                                                    alt="{{ $item['name'] }}">
                                            </a>

                                        {{-- VIDEO --}}
                                        @elseif ($item['type'] === 'video')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank" class="block w-full h-full">
                                                <video class="w-full h-full object-cover rounded" controls>
                                                    <source src="{{ asset('storage/' . $item['path']) }}" type="video/mp4">
                                                </video>
                                            </a>

                                        {{-- PDF --}}
                                        @elseif ($item['type'] === 'pdf')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                                class="block w-full h-full p-2 rounded text-center text-xs 
                                                    bg-gray-100 dark:bg-gray-700 
                                                    text-gray-900 dark:text-gray-100 
                                                    hover:bg-gray-200 dark:hover:bg-gray-600">
                                                <div class="text-3xl">📄</div>
                                                <div class="mt-1 truncate">{{ \Illuminate\Support\Str::limit($item['name'], 10) }}</div>
                                            </a>
                                        @endif

                                    </div>
                                @endif

                            @endforeach
                        </div>
                    </div>
                </div>

            @endforeach

            {{-- Pagination --}}
            @if ($total > $perPage)
                <div class="mt-4 flex justify-center space-x-2">

                    @if ($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
                            class="px-3 py-1 rounded
                                bg-gray-200 dark:bg-gray-700
                                hover:bg-gray-300 dark:hover:bg-gray-600
                                text-gray-900 dark:text-gray-100">
                            Previous
                        </a>
                    @endif

                    @for ($i = 1; $i <= ceil($total / $perPage); $i++)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="px-3 py-1 rounded
                                {{ $i == $page 
                                        ? 'bg-blue-500 text-white' 
                                        : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100' }}">
                            {{ $i }}
                        </a>
                    @endfor

                    @if ($page < ceil($total / $perPage))
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
                            class="px-3 py-1 rounded
                                bg-gray-200 dark:bg-gray-700
                                hover:bg-gray-300 dark:hover:bg-gray-600
                                text-gray-900 dark:text-gray-100">
                            Next
                        </a>
                    @endif

                </div>
            @endif

        {{-- Step 5: Inside a Subfolder --}}
        @elseif ($selectedSubfolder)

            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Content in {{ basename($selectedSubfolder) }}
            </h2>

            <div class="mb-4">
                @php $parentPath = dirname($selectedSubfolder); @endphp

                <x-filament::button 
                    tag="a"
                    href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}&folder={{ urlencode($parentPath) }}" 
                    color="primary" 
                    icon="heroicon-o-arrow-left">
                    Back to {{ basename($parentPath) }}
                </x-filament::button>
            </div>

            {{-- Select All + Download --}}
            <div class="flex items-center justify-between mb-2 
                        text-gray-900 dark:text-gray-100">

                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder"
                        class="form-checkbox
                            text-blue-600 dark:text-blue-400
                            bg-white dark:bg-gray-800
                            border-gray-300 dark:border-gray-600">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count-subfolder">0</span>)
                </label>

                <button id="download-selected-subfolder"
                    class="inline-flex items-center justify-center px-4 py-2 rounded
                        bg-primary-600 text-white
                        hover:bg-primary-700 dark:hover:bg-primary-500 
                        transition">
                    Download
                </button>
            </div>

            {{-- Display grouped items --}}
            @foreach ($items as $date => $groupItems)

                <div class="mb-4 rounded border 
                            border-gray-200 dark:border-gray-700 
                            bg-white dark:bg-gray-800">

                    {{-- Accordion header --}}
                    <button class="w-full text-left px-4 py-2 flex justify-between items-center
                                bg-gray-100 dark:bg-gray-700
                                hover:bg-gray-200 dark:hover:bg-gray-600
                                accordion-header
                                text-gray-900 dark:text-gray-100">
                        <span class="text-sm font-semibold">{{ $date }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    {{-- Content --}}
                    <div class="accordion-content px-4 py-3 bg-white dark:bg-gray-800">
                        <div class="grid gap-3"
                            style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">

                            @foreach ($groupItems as $item)

                                {{-- ========== FOLDER ========== --}}
                                @if ($item['type'] === 'folder')

                                    <div class="relative w-32 h-36 rounded shadow border
                                                bg-white dark:bg-gray-800
                                                border-gray-300 dark:border-gray-700
                                                text-xs font-medium overflow-hidden flex flex-col">

                                        <div class="flex justify-between items-start p-1">

                                            {{-- Checkbox --}}
                                            <input type="checkbox"
                                                class="folder-checkbox-subfolder
                                                        text-blue-600 dark:text-blue-400
                                                        bg-white dark:bg-gray-800
                                                        border-gray-300 dark:border-gray-600"
                                                value="{{ route('download-folder', ['path' => $item['path']]) }}">

                                            {{-- Download --}}
                                            <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                                class="p-1 rounded-full
                                                    hover:bg-gray-200 dark:hover:bg-gray-600"
                                                title="Download Subfolder">
                                                <x-heroicon-o-arrow-down-tray
                                                    class="w-5 h-5 text-gray-700 dark:text-white" />
                                            </a>
                                        </div>

                                        {{-- Folder icon --}}
                                        <a href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}
                                                &user={{ $selectedUser->id }}
                                                &folder={{ urlencode($selectedFolder) }}
                                                &subfolder={{ urlencode($item['path']) }}"
                                        class="flex flex-col items-center justify-center flex-1
                                                text-gray-900 dark:text-gray-100 px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate w-full text-center"
                                                title="{{ $item['name'] }}">
                                                {{ \Illuminate\Support\Str::limit($item['name'], 10) }}
                                            </div>
                                        </a>
                                    </div>

                                {{-- ========== FILE CARD ========== --}}
                                @else
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group 
                                                bg-white dark:bg-gray-800
                                                border border-gray-300 dark:border-gray-700">

                                        <div class="flex justify-between items-start p-1">

                                            {{-- Checkbox --}}
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="image-checkbox-subfolder
                                                        text-blue-600 dark:text-blue-400
                                                        bg-white dark:bg-gray-800
                                                        border-gray-300 dark:border-gray-600"
                                                    value="{{ asset('storage/' . $item['path']) }}">

                                                {{-- Linked --}}
                                                @if(isset($item['linked']) && $item['linked'])
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">
                                                        Linked
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- More options --}}
                                            <button onclick="openPropertiesModal('{{ $item['name'] }}', '{{ $item['type'] }}', '{{ $item['created_at'] ?? 'N/A' }}', '{{ asset('storage/' . $item['path']) }}')"
                                                class="p-1 rounded-full
                                                    hover:bg-gray-200 dark:hover:bg-gray-600"
                                                title="More options">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="w-4 h-4 text-gray-700 dark:text-white"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- IMAGE --}}
                                        @if ($item['type'] === 'image')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                                class="block w-full h-full">
                                                <img src="{{ asset('storage/' . $item['path']) }}"
                                                    class="w-full h-full object-cover rounded"
                                                    alt="{{ $item['name'] }}">
                                            </a>

                                        {{-- VIDEO --}}
                                        @elseif ($item['type'] === 'video')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                                class="block w-full h-full">
                                                <video class="w-full h-full object-cover rounded" controls>
                                                    <source src="{{ asset('storage/' . $item['path']) }}" type="video/mp4">
                                                </video>
                                            </a>

                                        {{-- PDF --}}
                                        @elseif ($item['type'] === 'pdf')
                                            <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                                class="block w-full h-full p-2 rounded text-center text-xs
                                                    bg-gray-100 dark:bg-gray-700
                                                    text-gray-900 dark:text-gray-100
                                                    hover:bg-gray-200 dark:hover:bg-gray-600">
                                                <div class="text-3xl">📄</div>
                                                <div class="mt-1 truncate">
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

            {{-- pagination (images use $total & $perPage) --}}
            @if ($total > $perPage)
                <div class="mt-4 flex justify-center space-x-2">
                    @if ($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" class="px-3 py-1 bg-gray-200 rounded">Previous</a>
                    @endif

                    @for ($i = 1; $i <= ceil($total / $perPage); $i++)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="px-3 py-1 rounded {{ $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' }}">{{ $i }}</a>
                    @endfor

                    @if ($page < ceil($total / $perPage))
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" class="px-3 py-1 bg-gray-200 rounded">Next</a>
                    @endif
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
                    text-gray-900 dark:text-gray-100">

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
    function openPropertiesModal(name, type, date, path) {
        document.getElementById('prop-name').innerText = name;
        document.getElementById('prop-date').innerText = date;
        document.getElementById('prop-path').innerText = path;
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
