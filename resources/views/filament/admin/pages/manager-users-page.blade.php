@php use Illuminate\Support\Facades\Auth; @endphp
<x-filament::page>
    <div>
        {{-- Step 1: Show Users --}}
        @if (!$selectedUser)
            <h2 class="text-xl font-bold mb-4">Select User</h2>
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
                @foreach ($managerUsers as $user)
                    <a href="?user={{ $user->id }}"
                       class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                        {{-- User Icon --}}
                        <x-heroicon-s-user class="w-20 h-16" style="color:#1D4ED8;"/>
                        {{-- User Name --}}
                        <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                    </a>
                @endforeach
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
                <x-filament::button
                    tag="a"
                    href="{{ route('download-today-folders', ['user' => $selectedUser->id]) }}"
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                >
                    Download Today’s Folders
                </x-filament::button>
            </div>
            
            @foreach ($folders as $group => $items)
                <div class="mb-2 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 hover:bg-gray-200 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $group }}</span>
                        <span class="text-sm">▼</span>
                    </button>
                    <div class="accordion-content px-4 py-2">
                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                            @foreach ($items as $folder)
                                <div class="flex flex-col items-center justify-center text-center">
                                    {{-- Download --}}
                                    <a href="{{ route('download-folder', ['path' => $folder['path']]) }}"
                                        class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200"
                                        title="Download Folder">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                                    </a>
                                    {{-- Open Folder --}}
                                    <a href="?user={{ $selectedUser->id }}&folder={{ urlencode($folder['path']) }}"
                                        class="flex flex-col items-center hover:text-yellow-600 transition duration-150 ease-in-out">
                                        <div class="w-24 h-24 flex items-center justify-center">
                                            <x-heroicon-s-folder class="w-16 h-16 text-yellow-500" style="color: #facc15;" />
                                        </div>
                                        <span class="mt-1 text-xs text-black truncate w-24" title="{{ $folder['name'] }}">
                                            {{ \Illuminate\Support\Str::limit($folder['name'], 10) }}
                                        </span>
                                    </a>
                                </div>
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

        {{-- Step 3: Subfolders + Images --}}
        @elseif ($selectedFolder && !$selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedFolder) }}</h2>

            <div class="mb-4">
                <x-filament::button
                    tag="a"
                    href="?user={{ $selectedUser->id }}"
                    color="primary"
                    icon="heroicon-o-arrow-left">
                    Back
                </x-filament::button>
            </div>

            {{-- Select All + Download (images in this folder/page) --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count">0</span>)
                </label>
                <button id="download-selected" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            {{-- ITEMS grouped by date --}}
            @foreach ($items as $date => $groupItems)
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $date }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            @foreach ($groupItems as $item)
                                @if ($item['type'] === 'folder')
                                    {{-- folder card --}}
                                    <div class="relative w-32 h-36 bg-white rounded shadow border text-xs font-medium overflow-hidden flex flex-col">
                                        {{-- Top row: checkbox + download button --}}
                                        <div class="flex justify-between items-start p-1">
                                            <input type="checkbox"
                                                class="folder-checkbox"
                                                style="transform: scale(1.2);"
                                                value="{{ route('download-folder', ['path' => $item['path']]) }}">

                                            <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                            class="p-1 rounded-full hover:bg-gray-200"
                                            title="Download Subfolder">
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                                            </a>
                                        </div>

                                        {{-- Folder icon + name --}}
                                        <a href="?user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($item['path']) }}"
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
                                        {{-- Top-left checkbox --}}
                                        <div class="flex justify-between items-start p-1">
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="{{ isset($selectedSubfolder) ? 'image-checkbox-subfolder' : 'image-checkbox' }}"
                                                    value="{{ asset('storage/' . $item['path']) }}">
                                                @if(isset($item['linked']) && $item['linked'])
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">Linked</span>
                                                @endif
                                            </div>

                                            {{-- 3-dot button (top-right) --}}
                                            <button 
                                                onclick="openPropertiesModal('{{ $item['name'] }}', '{{ $item['type'] }}', '{{ $item['created_at'] ?? 'N/A' }}', '{{ asset('storage/' . $item['path']) }}')"
                                                class="p-1 rounded-full hover:bg-gray-200 transition"
                                                title="More options">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                </svg>
                                            </button>
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
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 rounded text-center p-2 text-xs hover:bg-gray-200 transition"
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

        {{-- Step 4: Inside Subfolder --}}
        @elseif ($selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedSubfolder) }}</h2>

            <div class="mb-4">
                @php $parentPath = dirname($selectedSubfolder); @endphp

                <x-filament::button 
                    tag="a" 
                    href="?user={{ $selectedUser->id }}&folder={{ urlencode($parentPath) }}"
                    color="primary" 
                    icon="heroicon-o-arrow-left">
                    Back to {{ basename($parentPath) }}
                </x-filament::button>
            </div>

            {{-- Select All + Download for subfolder-level images --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count-subfolder">0</span>)
                </label>
                <button id="download-selected-subfolder" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            {{-- same merged items UI as above, but links refer to deeper levels --}}
            @foreach ($items as $date => $groupItems)
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $date }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            @foreach ($groupItems as $item)
                                @if ($item['type'] === 'folder')
                                    <div class="relative w-32 h-36 bg-white rounded shadow border text-xs font-medium overflow-hidden flex flex-col">
                                        {{-- Top row: checkbox + download button --}}
                                        <div class="flex justify-between items-start p-1">
                                            <input type="checkbox"
                                                class="folder-checkbox"
                                                style="transform: scale(1.2);"
                                                value="{{ route('download-folder', ['path' => $item['path']]) }}">

                                            <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}"
                                            class="p-1 rounded-full hover:bg-gray-200"
                                            title="Download Subfolder">
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                                            </a>
                                        </div>

                                        {{-- Folder icon + name --}}
                                        <a href="?user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($item['path']) }}"
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
                                        {{-- Top-left checkbox --}}
                                        <div class="flex justify-between items-start p-1">
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="{{ isset($selectedSubfolder) ? 'image-checkbox-subfolder' : 'image-checkbox' }}"
                                                    value="{{ asset('storage/' . $item['path']) }}">
                                                @if(isset($item['linked']) && $item['linked'])
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">Linked</span>
                                                @endif
                                            </div>

                                            {{-- 3-dot button (top-right) --}}
                                            <button 
                                                onclick="openPropertiesModal('{{ $item['name'] }}', '{{ $item['type'] }}', '{{ $item['created_at'] ?? 'N/A' }}', '{{ asset('storage/' . $item['path']) }}')"
                                                class="p-1 rounded-full hover:bg-gray-200 transition"
                                                title="More options">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                </svg>
                                            </button>
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
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 rounded text-center p-2 text-xs hover:bg-gray-200 transition"
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

            {{-- pagination (same as above) --}}
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
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-sm relative p-6 overflow-hidden">
            {{-- Close Button --}}
            <button onclick="closePropertiesModal()"
                class="absolute top-3 right-3 text-gray-600 hover:text-gray-800 text-2xl leading-none focus:outline-none">
                &times;
            </button>

            <h3 class="text-lg font-bold mb-4 text-center">File Properties</h3>
            <div class="space-y-3 text-sm break-words overflow-hidden">
                <p class="truncate"><strong>Name:</strong> 
                    <span id="prop-name" class="break-words block text-gray-700"></span>
                </p>
                <p><strong>Created At:</strong> 
                    <span id="prop-date" class="text-gray-700"></span>
                </p>
                <p><strong>Path:</strong> 
                    <span id="prop-path" 
                        class="break-all text-blue-600 block max-h-24 overflow-y-auto p-1 bg-blue-50 rounded"></span>
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
