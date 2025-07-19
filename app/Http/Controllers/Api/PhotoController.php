<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(Request $request)
    {
        if ($request->hasFile('image')) {
            $folder = $request->input('folder', 'default'); // Optional: choose folder like "test18"
            $path = $request->file('image')->store("uploads/$folder", 'public');

            // Save path in DB
            Photo::create([
                'path' => $path, // e.g., "uploads/test18/image.jpg"
            ]);
        }

        return response()->json(['message' => 'Uploaded successfully']);
    }

    public function getImagesByFolder($folderName)
    {
        $folderPath = 'public/uploads/' . $folderName;

        if (!Storage::exists($folderPath)) {
            return response()->json(['message' => 'Folder not found'], 404);
        }

        $files = Storage::files($folderPath);

        $images = array_map(function ($filePath) {
            return asset(Storage::url($filePath)); // returns full URL
        }, $files);

        return response()->json($images);
    }

    public function index()
    {
        $photos = Photo::all()->map(function ($photo) {
            return [
                'id' => $photo->id,
                'path' => $photo->path,
                'image_url' => asset('storage/' . $photo->path),
            ];
        });

        return response()->json($photos);
    }
}
