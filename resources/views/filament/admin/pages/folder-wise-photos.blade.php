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
            <a href="{{ url()->current() }}" class="text-blue-500 hover:underline">← Back to folders</a>
        </div>

        @if ($subfolders)
            <h3 class="text-lg font-semibold mb-2">Subfolders:</h3>
            <ul class="mb-4">
                @foreach ($subfolders as $subfolder)
                    <li>📁 {{ $subfolder }}</li>
                @endforeach
            </ul>
        @endif

        @if ($images)
            <h3 class="text-lg font-semibold mb-2">Images:</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($images as $image)
                    <div class="border rounded shadow p-2">
                        <img src="{{ asset('storage/uploads/' . $selectedFolder . '/' . basename($image)) }}" alt="Image" class="w-full h-auto rounded">
                        <p class="text-sm mt-1 truncate">{{ basename($image) }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p>No images found in this folder.</p>
        @endif
    @endif
</x-filament::page>
