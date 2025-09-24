<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FolderShare;
use App\Models\Folder;
use App\Models\Photo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class FolderShareController extends Controller
{
    public function getFolderId(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $folder = Folder::where('name', $request->name)
            ->where('user_id', Auth::id())
            ->first();

        if (!$folder) {
            return response()->json(['folder_id' => null], 404);
        }

        return response()->json(['folder_id' => $folder->id]);
    }

    public function share(Request $request)
    {
        $request->validate([
            'folder_id'   => 'required|exists:folders,id',
            'shared_with' => 'required|email|exists:users,email',
        ]);

        // ✅ Find user by email
        $sharedWithUser = User::where('email', $request->shared_with)->first();
        if (!$sharedWithUser) {
            return response()->json(['success' => false, 'message' => 'Enter a valid email'], 200);
        }

        if (!$sharedWithUser) {
            return response()->json([
                'success' => false,
                'message' => 'User with this email does not exist.'
            ], 404);
        }

        // ✅ Create share entry
        $share = FolderShare::create([
            'folder_id'   => $request->folder_id,
            'shared_by'   => auth()->id(),
            'shared_with' => $sharedWithUser->id, // use ID, not email
        ]);

        return response()->json([
            'success' => true,
            'share'   => $share
        ]);
    }

    public function mySharedFolders()
    {
        $folders = FolderShare::with(['folder.photos'])->where('shared_with', auth()->id())->get();

        $folders->each(function ($share) {
            $share->folder->photos->each(function ($photo) {
                $photo->url = asset('storage/' . $photo->path);
            });
        });

        return response()->json($folders);
    }

    public function getSharedFolderPhotos($id)
    {
        // Find the shared folder entry
        $share = FolderShare::with(['folder.photos'])
            ->where('shared_with', auth()->id()) // only if this user has access
            ->where('folder_id', $id)
            ->first();

        if (!$share) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found or not shared with you.'
            ], 404);
        }

        // Attach public URLs for photos
        $photos = $share->folder->photos->map(function ($photo) {
            return [
                'id'   => $photo->id,
                'path' => $photo->path,
                'url'  => asset('storage/' . $photo->path),
            ];
        });

        return response()->json([
            'success' => true,
            'folder_id' => $id,
            'photos' => $photos,
        ]);
    }

    public function uploadToSharedFolder(Request $request, $folderId)
    {
        $userId = Auth::id();

        // check access
        $hasAccess = Folder::where('id', $folderId)->where('user_id', $userId)->exists()
            || FolderShare::where('folder_id', $folderId)->where('shared_with', $userId)->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        $folder = Folder::findOrFail($folderId);

        $request->validate([
            'photos'   => 'required|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png|max:5120',
        ]);

        // ✅ multiple files come in as array
        $files = $request->file('photos');

        if (!$files || !is_array($files)) {
            return response()->json([
                'success' => false,
                'message' => 'No files uploaded.'
            ], 400);
        }

        $uploaded = [];
        foreach ($files as $file) {
            if (!$file->isValid()) continue;

            $originalName = $file->getClientOriginalName();

            // ✅ check if file with same name already exists in this folder
            $exists = Photo::where('folder_id', $folderId)
                ->where('user_id', $folder->user_id)
                ->where('path', 'like', "%$originalName")
                ->exists();

            if ($exists) {
                continue; // skip duplicate
            }

            $filename = time().'_'.uniqid().'_'.$originalName;
            $safeFolderName = Str::slug($folder->name, '_');

            $path = $file->storeAs("{$folder->user_id}/shared/{$safeFolderName}", $filename, 'public');

            Photo::create([
                'path'        => $path,
                'user_id'     => $folder->user_id,
                'folder_id'   => $folderId,
                'uploaded_by' => $userId,
            ]);

            $uploaded[] = asset('storage/'.$path);
        }

        return response()->json([
            'success'  => true,
            'uploaded' => $uploaded,
        ]);
    }
}