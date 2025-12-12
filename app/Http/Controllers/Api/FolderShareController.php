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
            ->with('folder')
            ->get();

        return response()->json(['success' => true,'folders' => $shares]);
    }

    public function getSharedFolderPhotos($folderId)
    {
        $folder = Folder::with(['photos', 'children'])->find($folderId);

        if (!$folder) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found'
            ], 404);
        }

        // Detect correct company ID
        $companyId = $folder->company_id;
        if (!$companyId) {
            $companyId = auth()->user()->companies()->first()?->id;
        }

        // Build correct storage path
        $path = "{$companyId}/{$folder->user_id}/{$folder->name}";

        // Get all physical files inside storage/public/{company}/{user}/{folder}
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
        $pdfs = [];

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

        // Subfolders
        $subfolders = $folder->children->map(function ($sub) use ($companyId) {
            return [
                'id'   => $sub->id,
                'name' => $sub->name,
                'path' => "{$companyId}/{$sub->user_id}/{$sub->name}",
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
}
