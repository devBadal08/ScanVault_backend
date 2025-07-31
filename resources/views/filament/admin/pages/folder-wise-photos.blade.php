<x-filament::page>
    <h2 class="text-xl font-bold mb-4">Folder Wise Photos</h2>

    @if (!$selectedFolder)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($folders as $folder)
                <a href="?folder={{ urlencode($folder) }}" class="p-4 bg-gray-100 rounded shadow hover:bg-gray-200">
                    📁 {{ $folder }}
                </a>
            @endforeach
        </div>
    @else
        <div class="mb-4">
            <x-filament::button tag="a" href="{{ url()->current() }}" color="primary" icon="heroicon-o-arrow-left">
                Back to folders
            </x-filament::button>
        </div>

        @if (!empty($subfolders))
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-2">
                @foreach ($subfolders as $sub)
                    <a href="{{ route('filament.admin.pages.folder-wise-photos', ['folder' => $sub]) }}"
                       class="bg-yellow-50 border border-yellow-300 p-4 rounded shadow hover:bg-yellow-100 text-center font-medium transition">
                        📁 {{ basename($sub) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($images)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($images as $image)
                        <div>
                            <a href="{{ asset('storage/' . $image) }}" target="_blank">
                                <img src="{{ asset('storage/' . $image) }}"
                                    alt="Image"
                                    style="width: 150px; height: 150px;"
                                    class="w-full h-40 object-cover rounded shadow hover:scale-105 transition-transform duration-200">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p>No images found in this folder.</p>
        @endif

    @endif
</x-filament::page>
