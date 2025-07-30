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
        $folder = $request->input('folders')[0] ?? 'default_folder';

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = $image->getClientOriginalName();
                \Log::info("Storing image to: public/$folder");
                $path = $image->storeAs("public/$folder", $filename);

                // Save image record to DB
                Photo::create([
                    'path' => "$folder/$filename", // this will match your `getUrlAttribute`
                    'user_id' => Auth::id(), // or pass user ID from request if needed
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
