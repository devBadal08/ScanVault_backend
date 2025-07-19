<x-filament::page>
    <h2 class="text-2xl font-bold mb-6">Uploads</h2>

    {{-- Display folder list --}}
    @if ($selectedFolder === null)
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($folders as $folder)
                <a href="{{ route('filament.admin.pages.folder-wise-photos', ['folder' => $folder]) }}"
                   class="bg-white border border-gray-200 p-6 rounded-xl shadow hover:bg-gray-100 text-center text-lg font-medium transition">
                    📁 {{ $folder }}
                </a>
            @endforeach
        </div>

    @else
        {{-- Go back button using Filament UI --}}
        <div class="mb-2">
            <x-filament::button
                color="primary"
                icon="heroicon-o-arrow-left"
                tag="a"
                href="{{ route('filament.admin.pages.folder-wise-photos') }}">
                Back to folders
            </x-filament::button>
        </div>

        <h3 class="text-xl font-semibold mb-4">Images in "{{ $selectedFolder }}"</h3>

        {{-- Show images --}}
        @if (count($images) > 0)
            <div class="flex space-x-4 overflow-x-auto pb-4">
                @foreach ($images as $image)
                    <div class="flex-none bg-white p-2 rounded shadow text-center">
                        <img src="{{ asset('storage/uploads/' . $selectedFolder . '/' . $image) }}"
                            alt="{{ $image }}"
                            class="w-32 h-32 object-cover rounded border">
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-600">No images found in this folder.</p>
        @endif
    @endif
</x-filament::page>
