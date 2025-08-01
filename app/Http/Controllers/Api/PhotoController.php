<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{
  
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if ($request->hasFile('image')) {
            $folder = $request->input('folder', 'default'); // Optional: choose folder like "test18"
            $path = $request->file('image')->store("uploads/$folder", 'public');

            // Save path in DB
            Photo::create([
                'path' => $path, // e.g., "uploads/test18/image.jpg"
                'user_id' => Auth::id(),
            ]);
            return response()->json(['message' => 'Uploaded successfully']);
        }
        return response()->json(['error' => 'No image found'], 422);
    }

    public function getUserPhotos(Request $request)
    {
        $user = Auth::user();
        $folder = $request->input('folder');

        $query = Photo::where('user_id', $user->id);

        if ($folder) {
            $query->where('path', 'like', "%uploads/$folder/%");
        }
        return response()->json($query->get());
    }
    

    public function uploadAll(Request $request)
    {
        $folders = $request->input('folders'); // e.g. ['test1', 'test1/test1_1', 'test2', ...]
        $userId = Auth::id(); // Get logged-in user ID

        if ($request->hasFile('images')) {
            $images = $request->file('images');

            if (count($images) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match images count'], 422);
            }

            foreach ($images as $index => $image) {
                $originalFolder = $folders[$index];
                $filename = $image->getClientOriginalName();

                // Prefix the folder with user ID
                $userFolder = "$userId/$originalFolder";

                // Store under public disk at path: storage/app/public/{userId}/{folder}
                $image->storeAs($userFolder, $filename, 'public');

                // Save full relative path in DB: e.g., 21/test1/image.jpg
                Photo::create([
                    'path' => "$userFolder/$filename",
                    'user_id' => $userId,
                ]);
            }

            return response()->json(['message' => 'Images uploaded successfully']);
        }

        return response()->json(['error' => 'No images uploaded'], 400);
    }

    public function getImagesByFolder($folderName)
    {
        $folderPath = "uploads/$folderName"; // not 'public/uploads/...'
        if (!Storage::disk('public')->exists($folderPath)) {
            return response()->json(['message' => 'Folder not found'], 404);
        }

        $files = Storage::disk('public')->files($folderPath);

        $images = array_map(function ($filePath) {
            return asset('storage/' . $filePath); // convert to public URL
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
