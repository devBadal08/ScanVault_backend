<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;
use ZipStream\CompressionMethod;

class UserPhotoDownloadController extends Controller
{
    public function download(Request $request)
    {
        set_time_limit(0);

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
        | Get photos
        |--------------------------------------------------------------------------
        */

        $photosQuery = Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->orderBy('id');

        if (!$photosQuery->exists()) {
            abort(404, 'No photos found for this user.');
        }

        /*
        |--------------------------------------------------------------------------
        | ZIP filename
        |--------------------------------------------------------------------------
        */

        $fileName = $user->name . '_photos.zip';

        /*
        |--------------------------------------------------------------------------
        | Stream ZIP through Laravel
        |--------------------------------------------------------------------------
        */

        return response()->streamDownload(
            function () use ($photosQuery, $fileName, $companyId, $userId) {

                /*
                |--------------------------------------------------------------------------
                | Remove any previous output
                |--------------------------------------------------------------------------
                */

                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                /*
                |--------------------------------------------------------------------------
                | Create ZipStream
                |--------------------------------------------------------------------------
                */

                $zip = new ZipStream(
                    outputName: $fileName,
                    defaultCompressionMethod: CompressionMethod::STORE,
                    defaultEnableZeroHeader: true,
                    enableZip64: true,
                    sendHttpHeaders: false,
                );

                /*
                |--------------------------------------------------------------------------
                | Add files
                |--------------------------------------------------------------------------
                */
                $count = 0;

                foreach ($photosQuery->cursor() as $photo) {

                    /*
                    |--------------------------------------------------------------------------
                    | Skip missing physical files
                    |--------------------------------------------------------------------------
                    */

                    if (!Storage::disk('public')->exists($photo->path)) {
                        continue;
                    }

                    $absolutePath = Storage::disk('public')
                        ->path($photo->path);

                    /*
                    |--------------------------------------------------------------------------
                    | Make ZIP path
                    |--------------------------------------------------------------------------
                    */

                    $parts = explode('/', trim($photo->path, '/'));

                    if (count($parts) < 3) {
                        continue;
                    }

                    $relativePath = implode(
                        '/',
                        array_slice($parts, 2)
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Add file
                    |--------------------------------------------------------------------------
                    */

                    $zip->addFileFromPath(
                        fileName: $relativePath,
                        path: $absolutePath,
                    );

                    $count++;

                    if ($count % 500 === 0) {
                        \Log::info('ZIP download progress', [
                            'user_id' => $userId,
                            'files_added' => $count,
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | VERY IMPORTANT
                |--------------------------------------------------------------------------
                |
                | This writes the ZIP central directory and EOF records.
                |
                */
                if ($count === 0) {
                    \Log::warning('ZIP contains no files', [
                        'user_id' => $userId,
                    ]);

                    abort(404, 'No valid files found for this user.');
                }

                \Log::info('ZIP finish START', [
                    'user_id' => $userId,
                    'files_added' => $count,
                ]);

                $zip->finish();

                \Log::info('ZIP finish COMPLETED', [
                    'user_id' => $userId,
                    'files_added' => $count,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Only after ZIP successfully finishes
                |--------------------------------------------------------------------------
                */

                Photo::where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->update([
                        'backed_up_at' => now(),
                    ]);
            },
            $fileName,
            [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}