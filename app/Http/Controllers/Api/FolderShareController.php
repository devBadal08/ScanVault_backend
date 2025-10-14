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
    /**
     * Get folder ID by name for the current user
     */
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

    /**
     * Share a folder with another user
     */
    public function share(Request $request)
    {
        $request->validate([
            'folder_id'   => 'required|exists:folders,id',
            'shared_with' => 'required|email|exists:users,email',
        ]);

        $sharedWithUser = User::where('email', $request->shared_with)->first();

        if (!$sharedWithUser) {
            return response()->json(['success' => false, 'message' => 'User with this email does not exist.'], 404);
        }

        // Prevent duplicate share
        $exists = FolderShare::where('folder_id', $request->folder_id)
            ->where('shared_with', $sharedWithUser->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Folder already shared with this user.'], 200);
        }

        $share = FolderShare::create([
            'folder_id'   => $request->folder_id,
            'shared_by'   => auth()->id(),
            'shared_with' => $sharedWithUser->id,
        ]);

        return response()->json(['success' => true, 'share' => $share]);
    }

    /**
     * Get folders shared with the authenticated user
     */
    public function mySharedFolders()
    {
        $folders = FolderShare::with(['folder.photos'])->where('shared_with', auth()->id())->get();

        $folders->each(function ($share) {
            $share->folder->photos->each(function ($photo) {
                $photo->url = asset('storage/' . $photo->path);
                $photo->uploader = User::find($photo->uploaded_by)?->name ?? 'Unknown';
            });
        });

        return response()->json(['success' => true, 'folders' => $folders]);
    }

    /**
     * Get all accessible folders (owned + shared)
     */
    public function allAccessibleFolders()
    {
        $userId = auth()->id();

        $sharedFolderIds = FolderShare::where('shared_with', $userId)->pluck('folder_id');

        $folders = Folder::with(['photos'])
            ->where('user_id', $userId)
            ->orWhereIn('id', $sharedFolderIds)
            ->get();

        $folders->each(function ($folder) {
            $folder->photos->each(function ($photo) {
                $photo->url = asset('storage/' . $photo->path);
                $photo->uploader = User::find($photo->uploaded_by)?->name ?? 'Unknown';
            });
        });

        return response()->json(['success' => true, 'folders' => $folders]);
    }

    /**
     * Get all photos from a shared folder
     */
    public function getSharedFolderPhotos($id)
    {
        $userId = auth()->id();

        $hasAccess = Folder::where('id', $id)->where('user_id', $userId)->exists()
            || FolderShare::where('folder_id', $id)->where('shared_with', $userId)->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found or not shared with you.'
            ], 404);
        }

        $folder = Folder::with('photos')->findOrFail($id);

        $photos = $folder->photos->map(function ($photo) {
            return [
                'id'        => $photo->id,
                'path'      => $photo->path,
                'url'       => asset('storage/' . $photo->path),
                'uploaded_by' => User::find($photo->uploaded_by)?->name ?? 'Unknown',
            ];
        });

        return response()->json(['success' => true, 'folder_id' => $id, 'photos' => $photos]);
    }

    /**
     * Upload photos to a folder (owner or shared)
     */
    public function uploadToSharedFolder(Request $request, $folderId)
    {
        $userId = Auth::id();

        $hasAccess = Folder::where('id', $folderId)->where('user_id', $userId)->exists()
            || FolderShare::where('folder_id', $folderId)->where('shared_with', $userId)->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        $folder = Folder::findOrFail($folderId);

        $request->validate([
            'files'   => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,mp4|max:20480', // 20MB limit
        ]);

        $files = $request->file('files');
        if (!$files || !is_array($files)) {
            return response()->json(['success' => false, 'message' => 'No files uploaded.'], 400);
        }

        $uploaded = [];
        foreach ($files as $file) {
            if (!$file->isValid()) continue;

            $originalName = $file->getClientOriginalName();

            // Skip if same base filename already exists in this folder
            $exists = Photo::where('folder_id', $folderId)
                ->where('path', 'like', "%$originalName")
                ->exists();

            if ($exists) continue;

            $filename = time() . '_' . uniqid() . '_' . $originalName;
            $safeFolderName = Str::slug($folder->name, '_');

            // Determine subdirectory based on file type
            $extension = strtolower($file->getClientOriginalExtension());
            $subFolder = 'others';
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $subFolder = 'images';
            } elseif (in_array($extension, ['mp4', 'mov', 'avi'])) {
                $subFolder = 'videos';
            } elseif ($extension === 'pdf') {
                $subFolder = 'pdfs';
            }

            $path = $file->storeAs("{$folder->user_id}/shared/{$safeFolderName}/{$subFolder}", $filename, 'public');

            Photo::create([
                'path'        => $path,
                'user_id'     => $folder->user_id,  // folder owner
                'folder_id'   => $folderId,
                'uploaded_by' => $userId,           // actual uploader
            ]);

            $uploaded[] = [
                'url' => asset('storage/' . $path),
                'type' => $extension,
            ];
        }

        return response()->json(['success' => true, 'uploaded' => $uploaded]);
    }
}
