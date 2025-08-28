<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FolderShare;
use App\Models\Folder;
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
}
