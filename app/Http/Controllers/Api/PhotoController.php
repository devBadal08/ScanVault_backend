<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Folder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhotoController extends Controller
{
    public function uploadAll(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        $companyId = $request->input('company_id');
        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 422);
        }

        // OPTIONAL SECURITY CHECK
        if (!Auth::user()->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json(['error' => 'You do not have access to this company'], 403);
        }

        // Each company has separate storage tracking
        $usedStorage = $this->calculateUsedStorageMB($userId, $companyId);

        $companyAdmin = \DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('users.role', 'admin')
            ->select('users.max_storage')
            ->first();

        $maxStorage = $companyAdmin->max_storage ?? 0; // MB

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
        $folderIds = [];

        /**************************************
         *     COMMON FOLDER LOGIC FUNCTION
         **************************************/
        $getFolder = function ($folderData) use ($userId, $companyId) {

            // NEW: folder_id-based upload
            if (is_array($folderData) && isset($folderData['folder_id'])) {

                $folder = Folder::where('id', $folderData['folder_id'])
                    ->where('user_id', $userId)
                    ->where('company_id', $companyId)
                    ->firstOrFail();

                $storagePath = "{$companyId}/{$userId}/{$folder->name}";
                return [$folder, $storagePath];
            }

            // BACKWARD COMPATIBILITY (old uploads)
            $originalFolder = is_array($folderData)
                ? $folderData['path']
                : $folderData;

            $parts = explode('/', $originalFolder);
            $parentName = $parts[0];
            $childName  = $parts[1] ?? null;

            $parent = Folder::firstOrCreate([
                'name'       => $parentName,
                'user_id'    => $userId,
                'parent_id'  => null,
                'company_id' => $companyId,
            ]);

            if ($childName) {
                $folder = Folder::firstOrCreate([
                    'name'       => $childName,
                    'parent_id'  => $parent->id,
                    'user_id'    => $userId,
                    'company_id' => $companyId,
                ]);

                $storagePath = "{$companyId}/{$userId}/{$parentName}/{$childName}";
            } else {
                $folder = $parent;
                $storagePath = "{$companyId}/{$userId}/{$parentName}";
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
                        'company_id'=> $companyId,
                    ]);

                    $uploaded[] = asset('storage/'.$path);

                    $folderIds[$index] = $folder->id;

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
                        'company_id'=> $companyId,
                    ]);

                    $uploaded[] = asset('storage/'.$path);

                    $folderIds[$index] = $folder->id;
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
                        'company_id'=> $companyId,
                    ]);

                    $uploaded[] = asset('storage/'.$path);

                    $folderIds[$index] = $folder->id;
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
            'folder_ids'=> array_values(array_unique($folderIds)),
        ]);
    }

    public function renameFolder(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'company_id' => 'required|integer',
        ]);

        $userId    = Auth::id();
        $companyId = $request->company_id;
        $newName   = $request->name;

        // 1️⃣ Get folder with full scope
        $folder = Folder::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $oldName = $folder->name;

        $basePath = "{$companyId}/{$folder->user_id}";

        if ($folder->parent_id) {
            $parent = Folder::find($folder->parent_id);
            $oldPath = "{$basePath}/{$parent->name}/{$folder->name}";
            $newPath = "{$basePath}/{$parent->name}/{$newName}";
        } else {
            $oldPath = "{$basePath}/{$folder->name}";
            $newPath = "{$basePath}/{$newName}";
        }

        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->move($oldPath, $newPath);
        } else {
            \Log::warning('RENAME STORAGE PATH NOT FOUND', [
                'oldPath' => $oldPath,
            ]);
        }

        // Update DB
        $folder->name = $newName;
        $folder->save();

        \Log::info('RENAME DEBUG', [
            'folder_id' => $id,
            'auth_user' => Auth::id(),
            'company_id' => $companyId,
            'folder_exists_any' => Folder::where('id', $id)->exists(),
            'folder_exists_user' => Folder::where('id', $id)
                ->where('user_id', Auth::id())
                ->exists(),
            'folder_exists_company' => Folder::where('id', $id)
                ->where('company_id', $companyId)
                ->exists(),
        ]);

        return response()->json([
            'success'   => true,
            'folder_id'=> $folder->id,
            'old_name' => $oldName,
            'new_name' => $newName,
        ]);
    }

    private function calculateUsedStorageMB($userId, $companyId)
    {
        $totalSize = 0;
        $userPath = storage_path("app/public/{$companyId}/{$userId}");

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
