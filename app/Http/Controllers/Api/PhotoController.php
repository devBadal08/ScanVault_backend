<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Folder;
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
            $path = $request->file('image')->store("$userId/$folder", 'public');

            // Save path in DB
            Photo::create([
                'path' => $path, // e.g., "test18/image.jpg"
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
            $query->where('path', 'like', "%$folder/%");
        }
        return response()->json($query->get());
    }
    

    public function uploadAll(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $folders = $request->input('folders'); // array of folder names
        if (!$folders || !is_array($folders)) {
            return response()->json(['error' => 'Folders array required'], 422);
        }

        $uploaded = [];
        $failed = [];

        // ---------- Upload Images ----------
        if ($request->hasFile('images')) {
            $images = $request->file('images');

            if (count($images) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match images count'], 422);
            }

            foreach ($images as $index => $image) {
                try {
                    $originalFolder = $folders[$index];
                    $filename = $image->getClientOriginalName();

                    $folder = Folder::firstOrCreate(
                        ['name' => $originalFolder, 'user_id' => $userId],
                        ['name' => $originalFolder, 'user_id' => $userId]
                    );

                    $path = $image->storeAs("$userId/$originalFolder", $filename, 'public');

                    Photo::create([
                        'path'      => $path,
                        'user_id'   => $userId,
                        'folder_id' => $folder->id,
                        'type'      => 'image',
                    ]);

                    $uploaded[] = asset('storage/' . $path);
                } catch (\Exception $e) {
                    \Log::error("Image upload failed: " . $e->getMessage());
                    $failed[] = $image->getClientOriginalName();
                }
            }
        }

        // ---------- Upload Videos ----------
        if ($request->hasFile('videos')) {
            $videos = $request->file('videos');

            if (count($videos) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match videos count'], 422);
            }

            foreach ($videos as $index => $video) {
                try {
                    $originalFolder = $folders[$index];
                    $filename = $video->getClientOriginalName();

                    $folder = Folder::firstOrCreate(
                        ['name' => $originalFolder, 'user_id' => $userId],
                        ['name' => $originalFolder, 'user_id' => $userId]
                    );

                    $path = $video->storeAs("$userId/$originalFolder", $filename, 'public');

                    Photo::create([
                        'path'      => $path,
                        'user_id'   => $userId,
                        'folder_id' => $folder->id,
                        'type'      => 'video',
                    ]);

                    $uploaded[] = asset('storage/' . $path);
                } catch (\Exception $e) {
                    \Log::error("Video upload failed: " . $e->getMessage());
                    $failed[] = $video->getClientOriginalName();
                }
            }
        }

        // ---------- Upload PDFs ----------
        if ($request->hasFile('pdfs')) {
            $pdfs = $request->file('pdfs');

            if (count($pdfs) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match PDFs count'], 422);
            }

            foreach ($pdfs as $index => $pdf) {
                try {
                    $originalFolder = $folders[$index];
                    $filename = $pdf->getClientOriginalName();

                    $folder = Folder::firstOrCreate(
                        ['name' => $originalFolder, 'user_id' => $userId],
                        ['name' => $originalFolder, 'user_id' => $userId]
                    );

                    $path = $pdf->storeAs("$userId/$originalFolder", $filename, 'public');

                    Photo::create([
                        'path'      => $path,
                        'user_id'   => $userId,
                        'folder_id' => $folder->id,
                        'type'      => 'pdf',
                    ]);

                    $uploaded[] = asset('storage/' . $path);
                } catch (\Exception $e) {
                    \Log::error("PDF upload failed: " . $e->getMessage());
                    $failed[] = $pdf->getClientOriginalName();
                }
            }
        }

        if (empty($uploaded) && empty($failed)) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        return response()->json([
            'message'  => 'Upload finished',
            'uploaded' => $uploaded,
            'failed'   => $failed,
        ]);
    }

    public function getImagesByFolder($folderName)
    {
        $folderPath = $folderName; // not 'public/...'
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
