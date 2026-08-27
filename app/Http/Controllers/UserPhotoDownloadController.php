<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;

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
        | Check whether photos exist
        |--------------------------------------------------------------------------
        */

        $hasPhotos = Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->exists();

        if (!$hasPhotos) {
            abort(404, 'No photos found for this user.');
        }

        /*
        |--------------------------------------------------------------------------
        | Get photos using cursor
        |--------------------------------------------------------------------------
        |
        | cursor() keeps only one Photo model in memory at a time.
        |
        */

        $photos = Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->orderBy('id')
            ->cursor();

        /*
        |--------------------------------------------------------------------------
        | ZIP filename
        |--------------------------------------------------------------------------
        */

        $fileName = $user->name . '_photos.zip';

        /*
        |--------------------------------------------------------------------------
        | Start ZipStream
        |--------------------------------------------------------------------------
        |
        | No temporary ZIP file is created.
        | ZIP is streamed directly to the browser.
        |
        */

        $zip = new ZipStream(
            outputName: $fileName,
            sendHttpHeaders: true,
        );

        /*
        |--------------------------------------------------------------------------
        | Add photos one by one
        |--------------------------------------------------------------------------
        */

        foreach ($photos as $photo) {

            /*
             * Skip missing files.
             */
            if (!Storage::disk('public')->exists($photo->path)) {
                continue;
            }

            /*
             * Get absolute file path.
             */
            $absolutePath = Storage::disk('public')
                ->path($photo->path);

            /*
             * Keep folder structure.
             *
             * Example:
             *
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

            /*
             * Add file directly to ZIP stream.
             */
            $zip->addFileFromPath(
                $relativePath,
                $absolutePath
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Finish ZIP
        |--------------------------------------------------------------------------
        */

        $zip->finish();

        /*
        |--------------------------------------------------------------------------
        | Update backup timestamp
        |--------------------------------------------------------------------------
        */

        Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->update([
                'backed_up_at' => now(),
            ]);
    }
}