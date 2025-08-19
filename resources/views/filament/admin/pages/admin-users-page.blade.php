<x-filament::page>
    <div>
        {{-- ✅ Step 1: Show Admin Users --}}
        <h2 class="text-xl font-bold mb-4">Admin Users</h2>
        <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
            @forelse ($adminUsers as $user)
                <a href="{{ route('filament.admin.pages.admin-users-page') }}?user={{ $user->id }}"
                   class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                    <x-heroicon-s-user class="w-20 h-16" style="color:#1D4ED8;" />
                    <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                </a>
            @empty
                <p class="text-gray-600">No users created by you.</p>
            @endforelse
        </div>

        {{-- ✅ Step 2: Folder navigation --}}
        @if ($selectedUser)
            <h2 class="text-lg font-semibold mt-8">Folders of {{ $selectedUser->name }}</h2>

            {{-- Show folders --}}
            @if (!$selectedFolder)
                <div class="grid gap-2 mt-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                    @foreach ($folders as $folder)
                        <a href="{{ route('filament.admin.pages.admin-users-page') }}?user={{ $selectedUser->id }}&folder={{ $folder }}"
                           class="flex flex-col items-center justify-center w-32 h-32 bg-white rounded-lg shadow hover:bg-blue-100 cursor-pointer">
                            📁 <span class="mt-1 text-xs truncate">{{ basename($folder) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Show subfolders + images --}}
            @if ($selectedFolder)
                <h3 class="text-md font-semibold mt-6">Inside {{ basename($selectedFolder) }}</h3>

                {{-- Subfolders --}}
                @if ($subfolders)
                    <div class="grid gap-2 mt-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                        @foreach ($subfolders as $sf)
                            <a href="{{ route('filament.admin.pages.admin-users-page') }}?user={{ $selectedUser->id }}&folder={{ $selectedFolder }}&subfolder={{ $sf }}"
                               class="flex flex-col items-center justify-center w-32 h-32 bg-white rounded-lg shadow hover:bg-blue-100 cursor-pointer">
                                📂 <span class="mt-1 text-xs truncate">{{ basename($sf) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Images --}}
                <div class="grid gap-3 mt-4" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                    @foreach ($images as $img)
                        <div class="w-32 h-32 bg-gray-100 rounded overflow-hidden shadow hover:shadow-md cursor-pointer">
                            <img src="{{ asset('storage/' . $img) }}" alt="photo" class="object-cover w-full h-full" />
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-filament::page>
