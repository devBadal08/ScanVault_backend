@php use Illuminate\Support\Facades\Auth; @endphp
<x-filament::page>
    <div>
        {{-- Step 1: Show Managers --}}
        @if (!$selectedManager)
            <h2 class="text-xl font-bold mb-4">Select Manager</h2>
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                @foreach ($managers as $manager)
                    <div onclick="window.location.href='?manager={{ $manager->id }}'"
                        class="flex flex-col items-center justify-center w-32 h-32 bg-white rounded-lg shadow hover:shadow-md transition border hover:bg-orange-100 cursor-pointer text-center overflow-hidden group">
                        <div class="text-3xl">👨‍💼</div>
                        <div class="mt-1 text-[10px] font-semibold text-gray-800 truncate w-full px-1">
                            {{ $manager->name }}
                        </div>
                    </div>
                @endforeach
            </div>

        {{-- Step 2: Show Users --}}
        @elseif (!$selectedUser)
            
            @if (Auth::user()->role === 'admin')
                <div class="mb-4">
                    {{-- Back to Managers --}}
                    <x-filament::button tag="a" href="{{ url()->current() }}" color="primary" icon="heroicon-o-arrow-left">
                        Back to Managers
                    </x-filament::button>
                </div>
            @endif
            <h2 class="text-xl font-bold mb-4">Select User</h2>
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
                @foreach ($users as $user)
                    <a href="?manager={{ $selectedManager->id }}&user={{ $user->id }}"
                    class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">

                    {{-- User Icon --}}
                    <x-heroicon-s-user class="w-20 h-16" style="color:#1D4ED8;"/>

                    {{-- User Name --}}
                    <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                    </a>
                @endforeach
            </div>

        {{-- Step 3: Show Folders --}}
        @elseif (!$selectedFolder)
            <div class="mb-4">
                {{-- Back to Users --}}
                <x-filament::button tag="a" href="?manager={{ $selectedManager->id }}" color="primary" icon="heroicon-o-arrow-left">
                    Back to Users
                </x-filament::button>
            </div>
            <h2 class="text-xl font-bold mb-4">Folders of {{ $selectedUser->name }}</h2>
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                @foreach ($folders as $folder)
                    <div class="flex flex-col items-center justify-center text-center">
                        {{-- Download Icon (top-right of folder) --}}
                        <a href="{{ route('download-folder', ['path' => $folder]) }}"
                        class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200"
                        title="Download Folder">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                        </a>

                        {{-- Folder Link --}}
                        <a href="?manager={{ $selectedManager->id }}&user={{ $selectedUser->id }}&folder={{ $folder }}"
                        class="flex flex-col items-center hover:text-yellow-600 transition duration-150 ease-in-out">
                            <div class="w-24 h-24 flex items-center justify-center">
                                <x-heroicon-s-folder class="w-20 h-20 text-yellow-500" style="color: #facc15;" />
                            </div>
                            {{-- Folder Name --}}
                            <span class="mt-1 text-sm text-black truncate w-24" title="{{ basename($folder) }}">
                                {{ Str::limit(basename($folder), 20) }}
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>

        {{-- Step 4: Show Subfolders & Images --}}
        @elseif ($selectedFolder && !$selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedFolder) }}</h2>

            {{-- Select All and Download Button --}}
            <div class="flex items-center justify-between mb-2 ">
                <label class="flex items-center space-x-8">
                    <input type="checkbox" id="select-all" class="form-checkbox">
                    <span>Select All</span>
                </label>
                <button id="download-selected"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                    Download
                </button>
            </div>

            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                {{-- Subfolders --}}
                @foreach ($subfolders as $subfolder)
                    <div class="relative w-32 h-32 bg-white rounded shadow border hover:bg-orange-100 text-center text-xs font-medium">
                        
                        {{-- 📥 Download subfolder --}}
                        <a href="{{ route('download-folder', ['path' => $subfolder]) }}"
                        class="absolute top-2 right-2 bg-white p-1 shadow hover:bg-gray-200 z-20"
                        title="Download Subfolder">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                        </a>

                        <a href="?manager={{ $selectedManager->id }}&user={{ $selectedUser->id }}&folder={{ $selectedFolder }}&subfolder={{ $subfolder }}"
                        class="absolute inset-0 flex flex-col items-center justify-center px-2">
                            📁
                            <div class="mt-1 truncate px-1 w-full" title="{{ basename($subfolder) }}">
                                {{ Str::limit(basename($subfolder), 20) }}
                            </div>
                        </a>
                    </div>
                @endforeach

                {{-- Images --}}
                @foreach ($images as $image)
                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                        <input type="checkbox" class="absolute top-1 left-1 z-50 image-checkbox" value="{{ asset('storage/' . $image) }}">
                        <a href="{{ asset('storage/' . $image) }}" target="_blank"
                        class="relative w-32 h-32 rounded shadow overflow-hidden group">
                            <img src="{{ asset('storage/' . $image) }}"
                                class="w-full h-full object-cover" alt="Image">
                        </a>

                        <a href="{{ asset('storage/' . $image) }}" download
                        class="absolute bottom-2 right-2 z-50 bg-white p-1 rounded-full shadow hover:bg-gray-100 transition"
                        title="Download Image">
                            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5 text-gray-700')
                        </a>
                    </div>
                @endforeach

                @if ($total > $perPage)
                    <div class="mt-4 flex justify-center space-x-2">
                        {{-- Previous --}}
                        @if ($page > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                        @endif

                        {{-- Page numbers --}}
                        @for ($i = 1; $i <= ceil($total / $perPage); $i++)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="px-3 py-1 rounded {{ $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' }}">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- Next --}}
                        @if ($page < ceil($total / $perPage))
                            <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                        @endif
                    </div>
                @endif
            </div>

        {{-- Step 5: Show Images and subfolder in Subfolder --}}
        @elseif ($selectedSubfolder)
            <h2 class="text-xl font-bold mb-4">Content in {{ basename($selectedSubfolder) }}</h2>

            {{-- Select All and Download Buttons --}}
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                    <span class="text-sm font-medium text-gray-700">Select All</span>
                </label>
                <button id="download-selected-subfolder"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                    Download
                </button>
            </div>

            {{-- Combined grid for subfolders + images --}}
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                {{-- Subfolders inside this subfolder --}}
                @if (!empty($subfolders))
                    @foreach ($subfolders as $sf)
                        <div class="relative w-32 h-32 bg-white rounded shadow border hover:bg-orange-100 text-center text-xs font-medium">
                            {{-- Download ZIP icon for subfolder --}}
                            <a href="{{ route('download-folder') }}?path={{ urlencode($sf) }}"
                                class="absolute top-2 right-2 bg-white p-1 rounded-full shadow hover:bg-gray-200 z-20"
                                title="Download Subfolder">
                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-gray-700" />
                            </a>

                            <a href="?manager={{ $selectedManager->id }}&user={{ $selectedUser->id }}&folder={{ $selectedFolder }}&subfolder={{ $sf }}"
                                class="absolute inset-0 flex flex-col items-center justify-center px-2">
                                📁
                                <div class="mt-1 truncate px-1 w-full" title="{{ basename($sf) }}">
                                    {{ Str::limit(basename($sf), 20) }}
                                </div>
                            </a>
                        </div>
                    @endforeach
                @endif

                {{-- Images --}}
                @forelse ($images as $image)
                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                        <input type="checkbox" class="absolute top-1 left-1 z-10 image-checkbox-subfolder" value="{{ asset('storage/' . $image) }}">
                        <a href="{{ asset('storage/' . $image) }}" target="_blank"
                            class="relative w-32 h-32 rounded shadow overflow-hidden group">
                            <img src="{{ asset('storage/' . $image) }}" class="w-full h-full object-cover" alt="Image">
                        </a>
                    </div>
                @empty
                @endforelse

                @if ($total > $perPage)
                    <div class="mt-4 flex justify-center space-x-2">
                        {{-- Previous --}}
                        @if ($page > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                        @endif

                        {{-- Page numbers --}}
                        @for ($i = 1; $i <= ceil($total / $perPage); $i++)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                            class="px-3 py-1 rounded {{ $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' }}">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- Next --}}
                        @if ($page < ceil($total / $perPage))
                            <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament::page>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select All in folder
    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.image-checkbox').forEach(cb => cb.checked = this.checked);
    });

    // Download selected in folder
    document.getElementById('download-selected')?.addEventListener('click', function () {
        const selected = [...document.querySelectorAll('.image-checkbox:checked')].map(cb => cb.value);
        if (selected.length === 0) return alert('Please select at least one image to download.');

        selected.forEach(url => {
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    });

    // Select All in subfolder
    document.getElementById('select-all-subfolder')?.addEventListener('change', function () {
        document.querySelectorAll('.image-checkbox-subfolder').forEach(cb => cb.checked = this.checked);
    });

    // Download selected in subfolder
    document.getElementById('download-selected-subfolder')?.addEventListener('click', function () {
        const selected = [...document.querySelectorAll('.image-checkbox-subfolder:checked')].map(cb => cb.value);
        if (selected.length === 0) return alert('Please select at least one image to download.');

        selected.forEach(url => {
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    });
});
</script>
