<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UserPhotoDownloadController extends Controller
{
    public function download(Request $request)
    {
        $authUser = Auth::user();

        abort_unless(
            in_array($authUser->role, ['admin', 'manager']),
            403
        );

        $userId = $request->integer('user');

        $user = User::findOrFail($userId);

        $companyId = $authUser->companies()->first()?->id;

        abort_unless($companyId, 403);

        /*
        |--------------------------------------------------------------------------
        | Get ALL photos
        |--------------------------------------------------------------------------
        */

        $photos = Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->orderBy('path')
            ->get();

        if ($photos->isEmpty()) {
            abort(404, 'No photos found for this user.');
        }

        /*
        |--------------------------------------------------------------------------
        | Temporary ZIP
        |--------------------------------------------------------------------------
        */

        $zipName = 'user_' . $userId . '_' . time() . '.zip';

        $zipPath = storage_path(
            'app/temp/' . $zipName
        );

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create ZIP file.');
        }

        foreach ($photos as $photo) {

            if (!Storage::disk('public')->exists($photo->path)) {
                continue;
            }

            $absolutePath = Storage::disk('public')
                ->path($photo->path);

            /*
             * Keep folder structure.
             *
             * Example:
             * 9/61/FolderA/Subfolder/photo.jpg
             *
             * becomes:
             *
             * FolderA/Subfolder/photo.jpg
             */

            $parts = explode('/', $photo->path);

            $relativePath = implode(
                '/',
                array_slice($parts, 2)
            );

            $zip->addFile(
                $absolutePath,
                $relativePath
            );

            $photo->update([
                'backed_up_at' => now(),
            ]);
        }

        $zip->close();

        return response()
            ->download(
                $zipPath,
                $user->name . '_photos.zip'
            )
            ->deleteFileAfterSend(true);
    }
}