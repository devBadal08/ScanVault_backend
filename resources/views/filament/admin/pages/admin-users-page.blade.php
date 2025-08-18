@php use Illuminate\Support\Facades\Auth; @endphp
<x-filament::page>
    <div>
        {{-- ✅ Step 1: Show Managers created by Admin --}}
        <h2 class="text-xl font-bold mb-4">Managers</h2>
        <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
            @forelse ($managers as $manager)
                <div onclick="window.location.href='{{ route('filament.admin.pages.manager-users-page') }}?manager={{ $manager->id }}'"
                    class="flex flex-col items-center justify-center w-32 h-32 bg-white rounded-lg shadow hover:shadow-md transition border hover:bg-orange-100 cursor-pointer text-center overflow-hidden group">
                    <div class="text-3xl">👨‍💼</div>
                    <div class="mt-1 text-[10px] font-semibold text-gray-800 truncate w-full px-1">
                        {{ $manager->name }}
                    </div>
                </div>
            @empty
                <p class="text-gray-600">No managers found.</p>
            @endforelse
        </div>

        {{-- ✅ Step 2: Show Users created directly by Admin --}}
        <h2 class="text-xl font-bold mt-8 mb-4">Admin Users</h2>
        <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
            @forelse ($adminUsers as $user)
                <a href="{{ route('filament.admin.pages.folder-wise-photos') }}?user={{ $user->id }}"
                    class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                    <x-heroicon-s-user class="w-20 h-16" style="color:#1D4ED8;" />
                    <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                </a>
            @empty
                <p class="text-gray-600">No users created by you.</p>
            @endforelse
        </div>
    </div>
</x-filament::page>
