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
use Illuminate\Support\Facades\Storage;

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

        // CREATE SHARE RECORD
        $share = FolderShare::create([
            'folder_id'   => $request->folder_id,
            'shared_by'   => auth()->id(),
            'shared_with' => $sharedWithUser->id,
        ]);

        // 🔥 SEND NOTIFICATION HERE (Correct place)
        if ($sharedWithUser->device_token) {
            $folderName = Folder::find($request->folder_id)->name;
            $senderName = Auth::user()->name;

            $this->sendPushV1(
                $sharedWithUser->device_token,
                "New Shared Folder",
                "$senderName shared \"$folderName\" with you."
            );
        }

        return response()->json(['success' => true, 'share' => $share]);
    }

    /**
     * Get folders shared with the authenticated user
     */
    public function mySharedFolders()
    {
        $shares = FolderShare::where('shared_with', auth()->id())
            ->with('folder')
            ->get();

        $folders = $shares->map(function ($share) {
            return [
                'id'        => $share->folder->id,
                'name'      => $share->folder->name,
                'user_id'   => $share->folder->user_id,   // OWNER
                'shared_by' => $share->shared_by,
            ];
        });

        return response()->json([
            'success' => true,
            'folders' => $folders
        ]);
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

        $hasAccess =
            Folder::where('id', $id)->where('user_id', $userId)->exists()
            || FolderShare::where('folder_id', $id)->where('shared_with', $userId)->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found or not shared with you.'
            ], 404);
        }

        $folder = Folder::findOrFail($id);

        // ✅ REAL STORAGE PATH (IMPORTANT)
        $basePath = $folder->user_id . '/' . Str::slug($folder->name, '_');

        // ✅ GET ALL SUBFOLDERS FROM STORAGE
        $folders = [];
        if (Storage::disk('public')->exists($basePath)) {
            $folders = collect(Storage::disk('public')->directories($basePath))
                ->map(function ($dir) {
                    return [
                        'id'   => null,
                        'name' => basename($dir),
                        'path' => $dir,
                    ];
                })->values();
        }

        // ✅ 1. FILES ONLY in MAIN (not subfolders)
        $mainPhotos = [];

        if (Storage::disk('public')->exists($basePath)) {
            $mainPhotos = collect(Storage::disk('public')->files($basePath))
                ->map(function ($file) {
                    return [
                        'name' => basename($file),
                        'path' => $file,
                        'url'  => asset('storage/' . $file),
                        'type' => pathinfo($file, PATHINFO_EXTENSION),
                    ];
                })->values();
        }

        // ✅ 2. SUBFOLDERS + THEIR FILES
        $folders = [];

        if (Storage::disk('public')->exists($basePath)) {
            $folders = collect(Storage::disk('public')->directories($basePath))
                ->map(function ($dir) {

                    $files = collect(Storage::disk('public')->files($dir))
                        ->map(function ($file) {
                            return [
                                'name' => basename($file),
                                'path' => $file,
                                'url'  => asset('storage/' . $file),
                                'type' => pathinfo($file, PATHINFO_EXTENSION),
                            ];
                        })->values();

                    return [
                        'id'    => null,
                        'name'  => basename($dir),
                        'path'  => $dir,
                        'files' => $files,
                    ];
                })->values();
        }

        return response()->json([
            'success'   => true,
            'folder_id' => $id,
            'folder'    => [
                'name' => $folder->name,
            ],
            'main_files' => $mainPhotos,  // ✅ ONLY main folder files
            'folders'    => $folders,     // ✅ your Flutter uses this
        ]);
    }

    public function getSharedFolderByPath(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $basePath = $request->path;

        if (!Storage::disk('public')->exists($basePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found'
            ], 404);
        }

        $folders = collect(Storage::disk('public')->directories($basePath))
            ->map(function ($dir) {
                return [
                    'name' => basename($dir),
                    'path' => $dir,
                ];
            })->values();

        $photos = collect(Storage::disk('public')->files($basePath))
            ->map(function ($file) {
                return [
                    'path' => $file,
                    'url' => asset("storage/" . $file)
                ];
            })->values();

        return response()->json([
            'success' => true,
            'folders' => $folders,
            'photos' => $photos
        ]);
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
            'files.*' => 'file|mimes:jpg,jpeg,png,mp4,pdf',
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

            // ✅ No subfolders — store all files in the main folder
            $path = $file->storeAs("{$folder->user_id}/{$safeFolderName}", $filename, 'public');

            Photo::create([
                'path'        => $path,
                'user_id'     => $folder->user_id,
                'folder_id'   => $folderId,
                'uploaded_by' => $userId,
            ]);

            $uploaded[] = [
                'url' => asset('storage/' . $path),
                'type' => $extension = strtolower($file->getClientOriginalExtension()),
            ];
        }

        return response()->json(['success' => true, 'uploaded' => $uploaded]);
    }

    // private function getAccessToken()
    // {
    //     $jsonPath = storage_path('app/firebase/firebase-key.json');

    //     $json = json_decode(file_get_contents($jsonPath), true);

    //     $now = time();
    //     $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    //     $claim = [
    //         'iss' => $json['client_email'],
    //         'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    //         'aud' => 'https://oauth2.googleapis.com/token',
    //         'exp' => $now + 3600,
    //         'iat' => $now,
    //     ];

    //     $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    //     $claimEncoded = rtrim(strtr(base64_encode(json_encode($claim)), '+/', '-_'), '=');

    //     openssl_sign("$headerEncoded.$claimEncoded", $signature, $json['private_key'], 'SHA256');
    //     $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    //     $jwt = "$headerEncoded.$claimEncoded.$signatureEncoded";

    //     $post_fields = http_build_query([
    //         'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    //         'assertion' => $jwt,
    //     ]);

    //     $ch = curl_init('https://oauth2.googleapis.com/token');
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     $response = curl_exec($ch);
    //     curl_close($ch);

    //     return json_decode($response, true)['access_token'];
    // }

    private function sendPushV1($token, $title, $body)
    {
        $accessToken = $this->getAccessToken();

        $projectId = 'scanvault-app-8a335';

        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $message = [
            "message" => [
                "token" => $token,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}
