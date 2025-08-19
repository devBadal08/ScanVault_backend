<x-filament::page>
    <div>
        <h2 class="text-xl font-bold mb-4">My Users</h2>

        <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
            @forelse ($managerUsers as $user)
                <a href="{{ route('filament.admin.pages.manager-users-page') }}?user={{ $user->id ?? 'N/A' }}"
                    class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                    <x-heroicon-s-user class="w-20 h-16 text-blue-600" />
                    <span class="mt-1 text-sm text-black truncate w-24">{{ $user->name }}</span>
                </a>
            @empty
                <p class="text-gray-600">No users created yet.</p>
            @endforelse
        </div>
    </div>
</x-filament::page>
