<x-filament::page>
    <h2 class="text-xl font-bold mb-6 text-center">Link Folders</h2>

    {{-- Filament Form --}}
    {{ $this->form }}

    {{-- Link Button --}}
    <x-filament::button wire:click="linkFolders" class="bg-blue-500 text-white px-4 py-2 mt-4">
        Link
    </x-filament::button>

    {{-- Linked Folders --}}
    <h3 class="font-semibold mt-6 mb-2">Linked Folders</h3>
    <ul>
        @forelse($this->linkedFolders as $link)
            <li class="flex justify-between items-center py-1 border-b border-gray-200">
                {{ $link->target_name ?? 'Deleted folder' }}
                ({{ ucfirst($link->link_type) }})
                <x-filament::button wire:click="unlinkFolder({{ $link->id }})" class="text-red-500 ml-4">
                    Unlink
                </x-filament::button>
            </li>
        @empty
            <li class="text-sm text-gray-500">No folders linked for selected source.</li>
        @endforelse
    </ul>
</x-filament::page>
