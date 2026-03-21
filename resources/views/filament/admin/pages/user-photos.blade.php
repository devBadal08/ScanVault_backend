@php use Illuminate\Support\Str; @endphp

<x-filament::page>
<div>

    {{-- 🔍 Global Search --}}
    <div class="mb-6 flex items-center gap-3">
        <input
            type="text"
            wire:model.defer="globalSearch"
            wire:keydown.enter="searchGlobal"
            placeholder="🔍 Global Search (folders ...)"
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
    </div>

    {{-- ================= SEARCH RESULTS ================= --}}
    @if(!empty($globalResults))
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-6 shadow border">

            <h3 class="text-lg font-bold mb-2">Search Results</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($globalResults as $item)
                    <a
                        href="?folder={{ urlencode($item['path']) }}"
                        class="p-3 rounded-lg border hover:bg-orange-100 dark:hover:bg-orange-900/30 transition"
                    >
                        <div class="text-sm font-semibold">
                            {{ $item['name'] }}
                        </div>

                        <div class="text-xs text-gray-500">
                            {{ strtoupper($item['type']) }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <br>

    {{-- ================= FOLDER LIST ================= --}}
    @if (!$selectedFolder)

        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
            My Folders
        </h2>

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
                    <span class="text-sm transition-transform">▼</span>
                </button>

                {{-- Accordion content --}}
                <div class="accordion-content px-4 py-3 bg-white dark:bg-gray-800">
                    <div class="grid gap-4"
                         style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">

                        @foreach ($items as $folder)
                            <div class="flex flex-col items-center text-center">

                                {{-- Download --}}
                                    <a href="{{ route('download-folder', ['path' => $folder['path']]) }}"
                                        class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                                        title="Download Folder">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700 dark:text-white" 
                                    />
                                    </a>
                                <a href="?folder={{ urlencode($folder['path']) }}"
                                   class="flex flex-col items-center hover:text-yellow-600 dark:hover:text-yellow-400 transition">

                                    {{-- FIXED ICON SIZE --}}
                                    <div class="w-24 h-24 flex items-center justify-center">
                                        <x-heroicon-s-folder class="w-16 h-16 text-yellow-500" style="color: #facc15;"/>
                                    </div>

                                    <span class="mt-1 text-xs truncate w-24
                                                text-gray-900 dark:text-gray-200"
                                          title="{{ $folder['name'] }}">
                                        {{ Str::limit($folder['name'], 10) }}
                                    </span>
                                </a>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        @endforeach

        {{-- pagination --}}
        @php
            $totalPages = ceil($total / $datesPerPage);
            $window = 2;

            $start = max(1, $page - $window);
            $end   = min($totalPages, $page + $window);
        @endphp

        @if ($total > $datesPerPage)
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
                @if ($start > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                    class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                        First
                    </a>
                    <span class="px-1">…</span>
                @endif

                {{-- Page Numbers --}}
                @for ($i = $start; $i <= $end; $i++)
                    <a
                        href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                        class="min-w-[40px] text-center px-3 py-2 rounded-lg border transition
                            {{ $i == $page
                                ? 'bg-orange-500 text-white border-orange-500'
                                : 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30 text-gray-800 dark:text-gray-200' }}"
                    >
                        {{ $i }}
                    </a>
                @endfor

                {{-- Last --}}
                @if ($end < $totalPages)
                    <span class="px-1">…</span>
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

    {{-- ================= INSIDE FOLDER ================= --}}
    @elseif ($selectedFolder && !$selectedSubfolder)

        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">
            Content in {{ basename($selectedFolder) }}
        </h2>

        <div class="mb-4">
            <x-filament::button 
                tag="a" 
                href="{{ url()->current() }}" 
                color="primary" 
                icon="heroicon-o-arrow-left">
                Back to Folders
            </x-filament::button>
        </div>

        {{-- Select All + Download --}}
        <div class="flex items-center justify-between mb-3 text-gray-900 dark:text-gray-100">
            <label class="flex items-center space-x-2">
                <input type="checkbox" id="select-all" class="form-checkbox">
                <span class="text-sm">Select All</span>
                (<span id="selected-count">0 selected</span>)
            </label>

            <button id="download-selected"
                class="inline-flex items-center justify-center px-4 py-2 rounded
                    bg-primary-600 text-white hover:bg-primary-700 transition">
                Download
            </button>
        </div>

        @foreach ($items as $date => $groupItems)

            <div class="mb-4 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">

                {{-- Accordion Header --}}
                <button class="w-full text-left px-4 py-2 flex justify-between items-center
                            bg-gray-100 dark:bg-gray-700 accordion-header">
                    <span class="text-sm font-semibold">{{ $date }}</span>
                    <span class="text-sm transition-transform">▼</span>
                </button>

                {{-- Accordion Content --}}
                <div class="accordion-content px-4 py-3">
                    <div class="grid gap-3"
                        style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">

                        @foreach ($groupItems as $item)

                            {{-- 📁 SUBFOLDER CARD --}}
                            @if ($item['type'] === 'folder')

                                @php
                                    $nextSubfolder = basename($item['path']);
                                @endphp

                                <div class="relative w-32 h-36 bg-white dark:bg-gray-800 rounded shadow border
                                            border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white
                                            text-xs font-medium overflow-hidden flex flex-col">

                                    {{-- Top row --}}
                                    <div class="flex justify-between items-start p-1">
                                        <input type="checkbox"
                                            class="folder-checkbox"
                                            value="{{ route('download-folder', ['path' => $item['path']]) }}">

                                        <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                        class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                            <x-heroicon-o-arrow-down-tray class="w-5 h-5"/>
                                        </a>
                                    </div>

                                    {{-- Body --}}
                                    <a href="?folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($nextSubfolder) }}"
                                    class="flex flex-col items-center justify-center flex-1 px-2">

                                        <div class="text-3xl">📁</div>

                                        <div class="mt-1 truncate w-full text-center">
                                            {{ Str::limit($item['name'], 10) }}
                                        </div>
                                    </a>
                                </div>

                            {{-- 🖼 IMAGE / 🎥 VIDEO / 📄 PDF --}}
                            @else

                                <div class="relative w-32 h-32 bg-white dark:bg-gray-800 rounded shadow border
                                            border-gray-200 dark:border-gray-700 overflow-hidden group">

                                    {{-- Top row --}}
                                    <div class="flex justify-between items-start p-1">

                                        <input type="checkbox"
                                            class="image-checkbox"
                                            value="{{ asset('storage/' . $item['path']) }}">

                                        <button
                                            data-name="{{ $item['name'] }}"
                                            data-date="{{ $item['created_at'] }}"
                                            data-path="{{ asset('storage/' . $item['path']) }}"
                                            onclick="openPropertiesModal(this)"
                                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                            ⋮
                                        </button>
                                    </div>

                                    {{-- Preview --}}
                                    @if ($item['type'] === 'image')
                                        <a href="{{ asset('storage/' . $item['path']) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $item['path']) }}"
                                                class="w-full h-full object-cover rounded">
                                        </a>

                                    @elseif ($item['type'] === 'video')
                                        <a href="{{ asset('storage/' . $item['path']) }}" target="_blank">
                                            <video class="w-full h-full object-cover rounded">
                                                <source src="{{ asset('storage/' . $item['path']) }}">
                                            </video>
                                        </a>

                                    @elseif ($item['type'] === 'pdf')
                                        <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                        class="w-full h-full flex flex-col items-center justify-center
                                                bg-gray-100 dark:bg-gray-900 text-xs">

                                            <div class="text-3xl">📄</div>
                                            <div class="mt-1 truncate w-full text-center px-1">
                                                {{ Str::limit($item['name'], 10) }}
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

        {{-- pagination --}}
        @php
            $totalPages = ceil($total / $datesPerPage);
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
                            ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                >
                    ← Previous
                </a>

                {{-- First --}}
                @if ($start > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                    class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                        First
                    </a>
                    <span>…</span>
                @endif

                {{-- Numbers --}}
                @for ($i = $start; $i <= $end; $i++)
                    <a
                        href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                        class="px-3 py-2 rounded-lg border
                            {{ $i == $page
                                ? 'bg-orange-500 text-white'
                                : 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30' }}"
                    >
                        {{ $i }}
                    </a>
                @endfor

                {{-- Last --}}
                @if ($end < $totalPages)
                    <span>…</span>
                    <a href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}"
                    class="px-3 py-2 rounded-lg border bg-white dark:bg-gray-800">
                        Last
                    </a>
                @endif

                {{-- Next --}}
                <a
                    href="{{ $page < $totalPages ? request()->fullUrlWithQuery(['page' => $page + 1]) : '#' }}"
                    class="px-3 py-2 rounded-lg border
                        {{ $page < $totalPages
                            ? 'bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/30'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' }}"
                >
                    Next →
                </a>

            </div>
        @endif

    {{-- ================= STEP 4: SUBFOLDER VIEW ================= --}}
    @elseif ($selectedSubfolder)

        <h2 class="text-xl font-bold mb-4">
            Content in {{ basename($selectedSubfolder) }}
        </h2>

        @php
            $segments = explode('/', $selectedSubfolder);
            array_pop($segments);
            $parentSubfolder = implode('/', $segments);
        @endphp

        <div class="mb-4">
            <x-filament::button 
                tag="a"
                href="?user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}{{ $parentSubfolder ? '&subfolder=' . urlencode($parentSubfolder) : '' }}"
                icon="heroicon-o-arrow-left">
                Back
            </x-filament::button>
        </div>

        {{-- Select All + Download (SUBFOLDER) --}}
        <div class="flex items-center justify-between mb-3 text-gray-900 dark:text-gray-100">

            <label class="flex items-center space-x-2">
                <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                <span class="text-sm">Select All</span>
                (<span id="selected-count-subfolder">0 selected</span>)
            </label>

            <button id="download-selected-subfolder"
                class="inline-flex items-center justify-center px-4 py-2 rounded
                    bg-primary-600 text-white hover:bg-primary-700 transition">
                Download
            </button>
        </div>

        @foreach ($items as $date => $groupItems)

            <div class="mb-4 border rounded bg-white dark:bg-gray-800">

                <button class="accordion-header w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-700 flex justify-between">
                    <span class="text-sm font-semibold">{{ $date }}</span>
                    <span>▼</span>
                </button>

                <div class="accordion-content px-4 py-3">
                    <div class="grid gap-3"
                        style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">

                        @foreach ($groupItems as $item)

                            {{-- 📁 Nested Folder --}}
                            @if ($item['type'] === 'folder')

                                @php
                                    $nextSubfolder = trim($selectedSubfolder.'/'.basename($item['path']), '/');
                                @endphp

                                <div class="relative w-32 h-36 bg-white dark:bg-gray-800 rounded shadow border
                                            border-gray-200 dark:border-gray-700 text-xs font-medium overflow-hidden flex flex-col">

                                    <div class="flex justify-between items-start p-1">
                                        <input type="checkbox"
                                            class="folder-checkbox-subfolder"
                                            value="{{ route('download-folder', ['path' => $item['path']]) }}">

                                        <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                        class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                            <x-heroicon-o-arrow-down-tray class="w-5 h-5"/>
                                        </a>
                                    </div>

                                    <a href="?folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($nextSubfolder) }}"
                                    class="flex flex-col items-center justify-center flex-1 px-2">

                                        <div class="text-3xl">📁</div>

                                        <div class="mt-1 truncate w-full text-center">
                                            {{ Str::limit($item['name'], 10) }}
                                        </div>
                                    </a>
                                </div>

                            {{-- MEDIA --}}
                            @else

                                <div class="relative w-32 h-32 bg-white dark:bg-gray-800 rounded shadow border
                                            border-gray-200 dark:border-gray-700 overflow-hidden group">

                                    <div class="flex justify-between items-start p-1">

                                        <input type="checkbox"
                                            class="image-checkbox-subfolder"
                                            value="{{ asset('storage/' . $item['path']) }}">

                                        <button
                                            data-name="{{ $item['name'] }}"
                                            data-date="{{ $item['created_at'] }}"
                                            data-path="{{ asset('storage/' . $item['path']) }}"
                                            onclick="openPropertiesModal(this)"
                                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                            ⋮
                                        </button>
                                    </div>

                                    @if ($item['type'] === 'image')
                                        <a href="{{ asset('storage/' . $item['path']) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $item['path']) }}"
                                                class="w-full h-full object-cover rounded">
                                        </a>

                                    @elseif ($item['type'] === 'video')
                                        <a href="{{ asset('storage/' . $item['path']) }}" target="_blank">
                                            <video class="w-full h-full object-cover rounded">
                                                <source src="{{ asset('storage/' . $item['path']) }}">
                                            </video>
                                        </a>

                                    @elseif ($item['type'] === 'pdf')
                                        <a href="{{ asset('storage/' . $item['path']) }}" target="_blank"
                                        class="w-full h-full flex flex-col items-center justify-center
                                                bg-gray-100 dark:bg-gray-900 text-xs">

                                            <div class="text-3xl">📄</div>
                                            <div class="mt-1 truncate w-full text-center px-1">
                                                {{ Str::limit($item['name'], 10) }}
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