<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FolderShare;
use App\Models\Folder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use App\Services\FolderPermissionService;

class FolderShareController extends Controller
{
    /* ✅ FIXED getFolderId FOR SUBFOLDERS */
    public function getFolderId(Request $request)
    {
        $request->validate([
            'name'      => 'required|string',
            'parent_id' => 'nullable|integer'
        ]);

        $query = Folder::where('name', $request->name)
            ->where('user_id', Auth::id());

        if ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        }

        $folder = $query->first();

        return response()->json([
            'folder_id' => $folder ? $folder->id : null
        ]);
    }

    public function share(Request $request)
    {
        $request->validate([
            'folder_id'   => 'required|exists:folders,id',
            'shared_with' => 'required|email|exists:users,email',
        ]);

        $sharedWithUser = User::where('email', $request->shared_with)->first();

        if (!$sharedWithUser) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $exists = FolderShare::where('folder_id', $request->folder_id)
            ->where('shared_with', $sharedWithUser->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Already shared']);
        }

        FolderShare::create([
            'folder_id'   => $request->folder_id,
            'shared_by'   => auth()->id(),
            'shared_with' => $sharedWithUser->id,
        ]);

        return response()->json(['success' => true]);
    }


    public function mySharedFolders()
    {
        $shares = FolderShare::where('shared_with', auth()->id())
            ->whereHas('folder')
            ->with([
                'folder:id,name,path,user_id',
                'sharedByUser:id,name'
            ])
            ->get()
            ->map(function ($share) {
                return [
                    'id'          => $share->folder->id,
                    'name'        => $share->folder->name,
                    'path'        => $share->folder->path,
                    'access_type' => $share->access_type, // ✅ REQUIRED
                    'shared_by'   => $share->sharedByUser->name,
                ];
            });

        return response()->json([
            'success' => true,
            'folders' => $shares
        ]);
    }

    public function getSharedFolderPhotos($folderId)
    {
        $folder = Folder::with(['children'])->find($folderId);

        if (!$folder) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found'
            ], 404);
        }

        $path = $folder->path; // ✅ USE STORED PATH

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'success' => true,
                'photos'  => [],
                'videos'  => [],
                'pdfs'    => [],
                'folders' => [],
            ]);
        }

        $physicalFiles = Storage::disk('public')->files($path);

        $photos = [];
        $videos = [];
        $pdfs   = [];

        foreach ($physicalFiles as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            $item = [
                'path' => $file,
                'url'  => asset("storage/$file"),
            ];

            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $photos[] = $item;
            } elseif ($ext === 'mp4') {
                $videos[] = $item;
            } elseif ($ext === 'pdf') {
                $pdfs[] = $item;
            }
        }

        $subfolders = $folder->children->map(function ($sub) {
            return [
                'id'   => $sub->id,
                'name' => $sub->name,
                'path' => $sub->path, // ✅ KEEP FULL PATH
            ];
        });

        return response()->json([
            'success' => true,
            'photos'  => $photos,
            'videos'  => $videos,
            'pdfs'    => $pdfs,
            'folders' => $subfolders,
        ]);
    }

    private function canWriteToFolder(Folder $folder): bool
    {
        // Owner always has access
        if ($folder->user_id === auth()->id()) {
            return true;
        }

        // Shared user with write access
        return FolderShare::where('folder_id', $folder->id)
            ->where('shared_with', auth()->id())
            ->where('access_type', 'write')
            ->exists();
    }

    public function uploadToSharedFolder(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);

        // permission check
        if (!$this->canWriteToFolder($folder)) {
            return response()->json([
                'success' => false,
                'message' => 'No write access'
            ], 403);
        }

        if (!$request->hasFile('files')) {
            return response()->json([
                'success' => false,
                'message' => 'No files uploaded'
            ], 400);
        }

        $uploaded = [];

        foreach ($request->file('files') as $file) {
            $ext = strtolower($file->getClientOriginalExtension());

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'mp4', 'pdf'])) {
                continue;
            }

            $filename = time() . '_' . uniqid() . '.' . $ext;
            $storedPath = $folder->path . '/' . $filename;

            // store file
            $file->storeAs($folder->path, $filename, 'public');

            // ✅ INSERT INTO DATABASE
            Photo::create([
                'user_id'   => auth()->id(),          // uploader
                'folder_id' => $folder->id,            // shared folder
                'path'      => $storedPath,            // REQUIRED
                'type'      => $this->detectType($file),
                'uploaded_by' => auth()->id(),
            ]);

            $uploaded[] = [
                'name' => $filename,
                'path' => $storedPath,
            ];
        }

        return response()->json([
            'success'  => true,
            'uploaded' => $uploaded,
        ]);
    }

    private function detectType($file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) return 'image';
        if ($ext === 'mp4') return 'video';
        if ($ext === 'pdf') return 'pdf';

        return 'file';
    }
}
