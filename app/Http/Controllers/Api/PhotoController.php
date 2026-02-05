<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Folder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FolderPermissionService;

class PhotoController extends Controller
{
    /**
     * Create folder (DB + physical directory)
     */
    public function createFolder(Request $request)
    {
        $parentFolder = null;

        if ($request->parent_id) {
            $parentFolder = Folder::findOrFail($request->parent_id);

            // 🔐 permission check
            if (!FolderPermissionService::canWrite($parentFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No permission to create subfolder'
                ], 403);
            }

            // ✅ KEEP OWNER SAME AS PARENT
            $userId = $parentFolder->user_id;
        } else {
            $userId = Auth::id();
        }

        $request->validate([
            'name'       => 'required|string|max:50',
            'company_id' => 'required|integer',
            'parent_id'  => 'nullable|integer|exists:folders,id',
        ]);

        $userId    = Auth::id();
        $companyId = $request->company_id;

        // Check existing folder
        $existing = Folder::where('name', $request->name)
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('parent_id', $request->parent_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'folder'  => $existing,
                'path'    => $existing->path,
                'exists'  => true,
            ]);
        }

        // Create folder record
        $folder = Folder::create([
            'name'       => $request->name,
            'company_id' => $companyId,
            'user_id'    => $userId,
            'parent_id'  => $request->parent_id,
        ]);

        // Build path using parent path (NO recursion)
        if ($request->parent_id) {
            $parent = Folder::findOrFail($request->parent_id);
            $folder->path = $parent->path . '/' . $folder->name;
        } else {
            $folder->path = $companyId . '/' . $userId . '/' . $folder->name;
        }

        $folder->save();

        // Create physical directory
        Storage::disk('public')->makeDirectory($folder->path);

        return response()->json([
            'success' => true,
            'folder'  => $folder,
            'path'    => $folder->path,
            'exists'  => false,
        ]);
    }

    /**
     * Upload images / videos / pdfs
     */
    public function uploadAll(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $request->input('company_id');
        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 422);
        }

        // Company access check
        if (!Auth::user()->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json(['error' => 'You do not have access to this company'], 403);
        }

        // Company storage info (NO filesystem scan)
        $company = DB::table('companies')
            ->select('id', 'used_storage_mb')
            ->where('id', $companyId)
            ->first();

        $companyAdmin = DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('users.role', 'admin')
            ->select('users.max_storage')
            ->first();

        $maxStorage = $companyAdmin->max_storage ?? 0;

        if ($maxStorage > 0) {
            $percentUsed = round(($company->used_storage_mb / $maxStorage) * 100, 2);

            if ($percentUsed >= 99) {
                return response()->json([
                    'error' => "🚫 Storage almost full ($percentUsed% used)",
                ], 403);
            }
        }

        $folders = $request->input('folders');
        if (!is_array($folders)) {
            return response()->json(['error' => 'Folders array required'], 422);
        }

        $uploaded  = [];
        $failed    = [];
        $folderIds = [];

        // Folder resolver (DB path, no recursion)
        $getFolder = function ($folderData) use ($companyId) {
            if (!isset($folderData['folder_id'])) {
                throw new \Exception('folder_id is required');
            }

            $folder = Folder::where('id', $folderData['folder_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();

            // 🔐 WRITE PERMISSION CHECK
            if (!FolderPermissionService::canWrite($folder)) {
                throw new \Exception('No write permission for this folder');
            }

            return [$folder, $folder->path];
        };

        /**
         * Generic upload handler
         */
        $handleUpload = function ($files, $type) use (
            $folders,
            $getFolder,
            $userId,
            $companyId,
            &$uploaded,
            &$failed,
            &$folderIds
        ) {
            foreach ($files as $index => $file) {
                try {
                    [$folder, $storagePath] = $getFolder($folders[$index]);

                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs($storagePath, $filename, 'public');

                    Photo::create([
                        'path'       => $path,
                        'user_id'    => $userId,
                        'folder_id'  => $folder->id,
                        'type'       => $type,
                        'company_id' => $companyId,
                        'uploaded_by' => $userId,
                    ]);

                    // Increment company storage
                    $sizeMB = round($file->getSize() / (1024 ** 2), 2);
                    DB::table('companies')
                        ->where('id', $companyId)
                        ->increment('used_storage_mb', $sizeMB);

                    // Count only jpg, jpeg, png as photos
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        DB::table('companies')
                            ->where('id', $companyId)
                            ->increment('total_photos');
                    }

                    $uploaded[] = asset('storage/' . $path);
                    $folderIds[] = $folder->id;

                } catch (\Exception $e) {
                    \Log::error(strtoupper($type) . ' upload failed: ' . $e->getMessage());
                    $failed[] = $file->getClientOriginalName();
                }
            }
        };

        if ($request->hasFile('images')) {
            $handleUpload($request->file('images'), 'image');
        }

        if ($request->hasFile('videos')) {
            $handleUpload($request->file('videos'), 'video');
        }

        if ($request->hasFile('pdfs')) {
            $handleUpload($request->file('pdfs'), 'pdf');
        }

        if (empty($uploaded) && empty($failed)) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        return response()->json([
            'message'    => 'Upload completed successfully',
            'uploaded'   => $uploaded,
            'failed'     => $failed,
            'folder_ids' => array_values(array_unique($folderIds)),
        ]);
    }

    /**
     * Rename folder (safe for nested paths)
     */
    public function renameFolder(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'company_id' => 'required|integer',
        ]);

        $folder = Folder::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        $oldPath = $folder->path;
        $newPath = dirname($oldPath) . '/' . $request->name;

        Storage::disk('public')->move($oldPath, $newPath);

        // Update all child paths
        Folder::where('path', 'like', $oldPath . '%')->update([
            'path' => DB::raw("REPLACE(path, '$oldPath', '$newPath')")
        ]);

        $folder->update([
            'name' => $request->name,
            'path' => $newPath,
        ]);

        return response()->json([
            'success'   => true,
            'folder_id'=> $folder->id,
            'old_name' => basename($oldPath),
            'new_name' => $request->name,
        ]);
    }
}
