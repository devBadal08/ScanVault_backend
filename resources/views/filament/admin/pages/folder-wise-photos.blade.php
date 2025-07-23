<x-filament::page>
    @if ($selectedFolder)
        {{-- ✅ Show Back button --}}
        <div class="mb-4">
            <x-filament::button
                color="primary"
                icon="heroicon-o-arrow-left"
                tag="a"
                href="{{ route('filament.admin.pages.folder-wise-photos', ['folder' => dirname($selectedFolder)]) }}">
                Back
            </x-filament::button>
        </div>

        <h3 class="text-xl font-semibold mb-4">📁 {{ $selectedFolder }}</h3>

        {{-- ✅ Subfolders --}}
        @if (!empty($subfolders))
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                @foreach ($subfolders as $sub)
                    <a href="{{ route('filament.admin.pages.folder-wise-photos', ['folder' => $sub]) }}"
                       class="bg-yellow-50 border border-yellow-300 p-4 rounded shadow hover:bg-yellow-100 text-center font-medium transition">
                        📁 {{ basename($sub) }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ✅ Images --}}
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

    @else
        {{-- ✅ Root Folders View --}}
        <h3 class="text-xl font-semibold mb-4">📁 Folders</h3>

        @if (!empty($folders))
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach ($folders as $folder)
                    <a href="{{ route('filament.admin.pages.folder-wise-photos', ['folder' => $folder]) }}"
                       class="bg-blue-50 border border-blue-300 p-4 rounded shadow hover:bg-blue-100 text-center font-medium transition">
                        📁 {{ basename($folder) }}
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-gray-600">No folders found in uploads directory.</p>
        @endif
    @endif
</x-filament::page>
