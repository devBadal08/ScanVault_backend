@php use Illuminate\Support\Facades\Auth; @endphp
<x-filament::page>
    <div>
        {{-- Step 1: Show Admin Users + Managers --}}
        @if (!$selectedManager && !$selectedUser)
            <h2 class="text-xl font-bold mb-4">Select Manager or Admin User </h2>
            <div class="grid gap-2 mb-6" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                @foreach ($managers as $manager)
                    <div onclick="window.location.href='?manager={{ $manager->id }}'"
                        class="flex flex-col items-center justify-center w-32 h-32 bg-white rounded-lg shadow hover:shadow-md transition border hover:bg-orange-100 cursor-pointer text-center overflow-hidden group">
                        <div class="text-3xl">👨‍💼</div>
                        <div class="mt-1 text-[10px] font-semibold text-gray-800 truncate w-full px-1">
                            {{ $manager->name }}
                        </div>
                    </div>
                @endforeach

                {{-- Admin’s own Users --}}
                @foreach ($adminUsers as $user)
                    <a href="?user={{ $user->id }}"
                        class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                        <x-heroicon-s-user class="w-20 h-16" style="color:#1D4ED8;" />
                        <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                    </a>
                @endforeach
            </div>

        {{-- Step 2: Show Users (if Manager selected) --}}
        @elseif ($selectedManager && !$selectedUser)
            <div class="mb-4">
                <x-filament::button tag="a" href="{{ url()->current() }}" color="primary" icon="heroicon-o-arrow-left">
                    Back
                </x-filament::button>
            </div>
            <h2 class="text-xl font-bold mb-4">Users under {{ $selectedManager->name }}</h2>
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
                @foreach ($users as $user)
                    <a href="?manager={{ $selectedManager->id }}&user={{ $user->id }}"
                        class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                        <x-heroicon-s-user class="w-20 h-16" style="color:#1D4ED8;" />
                        <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                    </a>
                @endforeach
            </div>

        {{-- Step 3: Show Folders (grouped by date) --}}
        @elseif ($selectedUser && !$selectedFolder)
            <div class="mb-4">
                <x-filament::button tag="a" href="?{{ $selectedManager ? 'manager='.$selectedManager->id : '' }}" color="primary" icon="heroicon-o-arrow-left">
                    Back to Users
                </x-filament::button>
            </div>

            <h2 class="text-xl font-bold mb-4">Folders of {{ $selectedUser->name }}</h2>

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
                <div class="mb-2 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 hover:bg-gray-200 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold">{{ $group }}</span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-2">
                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                            @foreach ($items as $folder)
                                <div class="flex flex-col items-center justify-center text-center">
                                    {{-- Download folder --}}
                                    <a href="{{ route('download-folder', ['path' => $folder['path']]) }}" class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200" title="Download Folder">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                                    </a>

                                    {{-- Linked folder badge --}}
                                    @if(isset($folder['linked']) && $folder['linked'])
                                        <span class="absolute top-0 left-0 bg-blue-500 text-white text-xs px-1 rounded-br">Linked</span>
                                    @endif

                                    {{-- Open folder --}}
                                    <a href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}&folder={{ urlencode($folder['path']) }}" class="flex flex-col items-center hover:text-yellow-600 transition duration-150 ease-in-out">
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

        {{-- Step 4: Inside a Folder --}}
        @elseif ($selectedFolder && !$selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedFolder) }}</h2>
            <div class="mb-4">
                <x-filament::button tag="a" href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}" color="primary" icon="heroicon-o-arrow-left">
                    Back
                </x-filament::button>
            </div>

            {{-- Select All + Download --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all" class="form-checkbox">
                    <span class="text-sm">Select All</span> (<span id="selected-count">0</span>)
                </label>
                <button id="download-selected" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            {{-- Display items --}}
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
                                    <div class="relative w-40 h-32 bg-white rounded shadow border text-center text-xs font-medium">
                                        <a href="{{ route('download-folder', ['path' => $item['path']]) }}"
                                        class="absolute top-2 right-2 bg-white p-1 shadow hover:bg-gray-200 z-20"
                                        title="Download Subfolder">
                                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                                        </a>

                                        {{-- Linked badge --}}
                                        @if(isset($item['linked']) && $item['linked'])
                                            <span class="absolute top-0 left-0 bg-blue-500 text-white text-xs px-1 rounded-br">Linked</span>
                                        @endif

                                        <a href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($item['path']) }}"
                                        class="absolute inset-0 flex flex-col items-center justify-center px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate px-1 w-full" title="{{ $item['name'] }}">
                                                {{ \Illuminate\Support\Str::limit($item['name'], 10) }}
                                            </div>
                                        </a>
                                    </div>
                                @else
                                    {{-- media card (image or video) --}}
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                                        {{-- Top-left checkbox --}}
                                        <div class="absolute top-1 left-1 z-50">
                                            <input type="checkbox"
                                                class="{{ isset($subfolder) ? 'image-checkbox-subfolder' : 'image-checkbox' }}"
                                                value="{{ asset('storage/' . $item['path']) }}">
                                        </div>

                                        @if ($item['type'] === 'image')
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('{{ $item['name'] }}', '{{ asset('storage/' . $item['path']) }}', '{{ $item['created_at'] ?? 'N/A' }}', 'image')"
                                                class="w-full h-full block">
                                                <img src="{{ asset('storage/' . $item['path']) }}" class="w-full h-full object-cover rounded" alt="{{ $item['name'] }}">
                                            </a>
                                        @elseif ($item['type'] === 'video')
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('{{ $item['name'] }}', '{{ asset('storage/' . $item['path']) }}', '{{ $item['created_at'] ?? 'N/A' }}', 'video')"
                                                class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded">
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

        {{-- Step 5: Inside a Subfolder --}}
        @elseif ($selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedSubfolder) }}</h2>
            <div class="mb-4">
                @php $parentPath = dirname($selectedSubfolder); @endphp
                <x-filament::button tag="a" href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}&folder={{ urlencode($parentPath) }}" color="primary" icon="heroicon-o-arrow-left">
                    Back to {{ basename($parentPath) }}
                </x-filament::button>
            </div>

            {{-- Select All + Download for subfolder-level images --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                    <span class="text-sm">Select All</span> (<span id="selected-count-subfolder">0</span>)
                </label>
                <button id="download-selected-subfolder" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            {{-- Display subfolder items --}}
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
                                    <div class="relative w-32 h-32 bg-white rounded shadow border text-center text-xs font-medium">
                                        <a href="{{ route('download-folder') }}?path={{ urlencode($item['path']) }}" class="absolute top-2 right-2 bg-white p-1 rounded-full shadow hover:bg-gray-200 z-20" title="Download Subfolder">
                                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                                        </a>

                                        <a href="?{{ $selectedManager ? 'manager='.$selectedManager->id.'&' : '' }}&user={{ $selectedUser->id }}&folder={{ urlencode($selectedFolder) }}&subfolder={{ urlencode($item['path']) }}" class="absolute inset-0 flex flex-col items-center justify-center px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate px-1 w-full" title="{{ $item['name'] }}">{{ \Illuminate\Support\Str::limit($item['name'], 10) }}</div>
                                        </a>
                                    </div>
                                @else
                                    {{-- media card (image or video) --}}
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                                        {{-- Top-left checkbox --}}
                                        <div class="absolute top-1 left-1 z-50">
                                            <input type="checkbox"
                                                class="{{ isset($subfolder) ? 'image-checkbox-subfolder' : 'image-checkbox' }}"
                                                value="{{ asset('storage/' . $item['path']) }}">
                                        </div>

                                        @if ($item['type'] === 'image')
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('{{ $item['name'] }}', '{{ asset('storage/' . $item['path']) }}', '{{ $item['created_at'] ?? 'N/A' }}', 'image')"
                                                class="w-full h-full block">
                                                <img src="{{ asset('storage/' . $item['path']) }}" class="w-full h-full object-cover rounded" alt="{{ $item['name'] }}">
                                            </a>
                                        @elseif ($item['type'] === 'video')
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('{{ $item['name'] }}', '{{ asset('storage/' . $item['path']) }}', '{{ $item['created_at'] ?? 'N/A' }}', 'video')"
                                                class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded">
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
        @endif
    </div>

    <!-- Modal -->
    <div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full overflow-hidden relative animate-scale-up">
            {{-- Close Button --}}
            <button onclick="closeImageModal()"
                    class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-2xl font-bold transition">
                &times;
            </button>

            {{-- Media container --}}
            <div class="w-full bg-gray-100 flex items-center justify-center p-4">
                <div class="max-w-full max-h-[40vh] flex items-center justify-center">
                    <img id="modalImage" src="" class="max-h-[60vh] max-w-full object-contain rounded-lg" alt="Image Preview">
                    <video id="modalVideo" controls class="max-h-[40vh] max-w-full object-contain rounded-lg hidden">
                        <source id="modalVideoSource" src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>

            {{-- Properties --}}
            <div class="px-6 py-4 bg-white border-t border-gray-200">
                <h3 class="text-lg font-semibold mb-2">Media Details</h3>
                <p class="text-sm text-gray-700"><strong>Name:</strong> <span id="modalName"></span></p>
                <p class="text-sm text-gray-700"><strong>Path:</strong> <span id="modalPath"></span></p>
                <p class="text-sm text-gray-700"><strong>Created At:</strong> <span id="modalCreated"></span></p>
            </div>
        </div>
    </div>

</x-filament::page>

<script>
    function openImageModal(name, path, created, type = 'image') {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImage');
        const video = document.getElementById('modalVideo');
        const videoSource = document.getElementById('modalVideoSource');

        // Reset: hide both
        img.classList.add('hidden');
        video.classList.add('hidden');
        video.pause();

        // Show correct media
        if(type === 'image') {
            img.src = path;
            img.classList.remove('hidden');
        } else if(type === 'video') {
            videoSource.src = path;
            video.load();
            video.classList.remove('hidden');
        }

        // Update details
        document.getElementById('modalName').innerText = name;
        document.getElementById('modalPath').innerText = path;
        document.getElementById('modalCreated').innerText = created;

        modal.classList.remove('hidden');
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        const video = document.getElementById('modalVideo');
        modal.classList.add('hidden');
        video.pause();
    }

document.addEventListener('DOMContentLoaded', function () {
    // Accordion logic
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', function () {
            const content = this.nextElementSibling;
            content.classList.toggle('hidden');
            this.querySelector('span:last-child').classList.toggle('rotate-180');
        });
    });

    const updateCount = (selector, countId) => {
        document.getElementById(countId).textContent = `${document.querySelectorAll(selector+':checked').length} selected`;
    };

    // Folder level
    const folderCheckboxes = document.querySelectorAll('.image-checkbox');
    document.getElementById('select-all')?.addEventListener('change', function () {
        folderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateCount('.image-checkbox', 'selected-count');
    });
    folderCheckboxes.forEach(cb => cb.addEventListener('change', () => updateCount('.image-checkbox', 'selected-count')));

    // Subfolder level
    const subfolderCheckboxes = document.querySelectorAll('.image-checkbox-subfolder');
    document.getElementById('select-all-subfolder')?.addEventListener('change', function () {
        subfolderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateCount('.image-checkbox-subfolder', 'selected-count-subfolder');
    });
    subfolderCheckboxes.forEach(cb => cb.addEventListener('change', () => updateCount('.image-checkbox-subfolder', 'selected-count-subfolder')));

    // Download logic (folder & subfolder)
    const download = (selector) => {
        const selected = [...document.querySelectorAll(selector+':checked')].map(cb => cb.value);
        if (!selected.length) return alert('Please select at least one image to download.');
        selected.forEach(url => { const a=document.createElement('a'); a.href=url; a.download=''; a.style.display='none'; document.body.appendChild(a); a.click(); document.body.removeChild(a); });
    };
    document.getElementById('download-selected')?.addEventListener('click', () => download('.image-checkbox'));
    document.getElementById('download-selected-subfolder')?.addEventListener('click', () => download('.image-checkbox-subfolder'));
});
</script>
