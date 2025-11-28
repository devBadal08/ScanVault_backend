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
    public function uploadAll(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $maxStorage = $user->max_storage ?? 0; // in MB
        $usedStorage = $this->calculateUsedStorageMB($user->id);

        if ($maxStorage > 0) {
            $percentUsed = round(($usedStorage / $maxStorage) * 100, 2);

            if ($percentUsed >= 99 && $percentUsed <= 100) {
                return response()->json([
                    'error' => "🚫 Storage almost full ($percentUsed% used)",
                ], 403);
            }

            if ($percentUsed > 100) {
                return response()->json([
                    'error' => "❌ Storage limit exceeded ($percentUsed% used)",
                ], 403);
            }
        }

        $folders = $request->input('folders'); // array like: ["parent", "parent/child"]

        if (!$folders || !is_array($folders)) {
            return response()->json(['error' => 'Folders array required'], 422);
        }

        $uploaded = [];
        $failed = [];

        /**************************************
         *     COMMON FOLDER LOGIC FUNCTION
         **************************************/
        $getFolder = function ($originalFolder) use ($userId) {

            $parts = explode('/', $originalFolder);

            $parentName = $parts[0];
            $childName  = $parts[1] ?? null;

            // ✅ Parent folder
            $parent = Folder::firstOrCreate([
                'name'    => $parentName,
                'user_id' => $userId,
                'parent_id' => null,
            ]);

            // ✅ Subfolder if exists
            if ($childName) {
                $folder = Folder::firstOrCreate([
                    'name'      => $childName,
                    'parent_id' => $parent->id,
                    'user_id'   => $userId,
                ]);

                $storagePath = "$userId/$parentName/$childName";
            } else {
                $folder = $parent;
                $storagePath = "$userId/$parentName";
            }

            return [$folder, $storagePath];
        };

        /**************************************
         *     UPLOAD IMAGES
         **************************************/
        if ($request->hasFile('images')) {
            $images = $request->file('images');

            if (count($images) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match images count'], 422);
            }

            foreach ($images as $index => $image) {
                try {

                    [$folder, $storagePath] = $getFolder($folders[$index]);

                    $filename = time().'_'.uniqid().'_'.$image->getClientOriginalName();

                    $path = $image->storeAs($storagePath, $filename, 'public');

                    Photo::create([
                        'path'      => $path,
                        'user_id'   => $userId,
                        'folder_id' => $folder->id,
                        'type'      => 'image',
                    ]);

                    $uploaded[] = asset('storage/'.$path);

                } catch (\Exception $e) {
                    \Log::error("Image upload failed: " . $e->getMessage());
                    $failed[] = $image->getClientOriginalName();
                }
            }
        }

        /**************************************
         *     UPLOAD VIDEOS
         **************************************/
        if ($request->hasFile('videos')) {
            $videos = $request->file('videos');

            if (count($videos) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match videos count'], 422);
            }

            foreach ($videos as $index => $video) {
                try {

                    [$folder, $storagePath] = $getFolder($folders[$index]);

                    $filename = time().'_'.uniqid().'_'.$video->getClientOriginalName();

                    $path = $video->storeAs($storagePath, $filename, 'public');

                    Photo::create([
                        'path'      => $path,
                        'user_id'   => $userId,
                        'folder_id' => $folder->id,
                        'type'      => 'video',
                    ]);

                    $uploaded[] = asset('storage/'.$path);

                } catch (\Exception $e) {
                    \Log::error("Video upload failed: " . $e->getMessage());
                    $failed[] = $video->getClientOriginalName();
                }
            }
        }

        /**************************************
         *     UPLOAD PDFs
         **************************************/
        if ($request->hasFile('pdfs')) {
            $pdfs = $request->file('pdfs');

            if (count($pdfs) !== count($folders)) {
                return response()->json(['error' => 'Folders count must match PDFs count'], 422);
            }

            foreach ($pdfs as $index => $pdf) {
                try {

                    [$folder, $storagePath] = $getFolder($folders[$index]);

                    $filename = time().'_'.uniqid().'_'.$pdf->getClientOriginalName();

                    $path = $pdf->storeAs($storagePath, $filename, 'public');

                    Photo::create([
                        'path'      => $path,
                        'user_id'   => $userId,
                        'folder_id' => $folder->id,
                        'type'      => 'pdf',
                    ]);

                    $uploaded[] = asset('storage/'.$path);

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
            'message'  => 'Upload completed successfully',
            'uploaded' => $uploaded,
            'failed'   => $failed,
        ]);
    }

    private function calculateUsedStorageMB($userId)
    {
        $totalSize = 0;
        $userPath = storage_path("app/public/{$userId}");

        if (is_dir($userPath)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($userPath, \FilesystemIterator::SKIP_DOTS)
            ) as $file) {
                $totalSize += $file->getSize();
            }
        }

        return round($totalSize / (1024 ** 2), 2);
    }
}
