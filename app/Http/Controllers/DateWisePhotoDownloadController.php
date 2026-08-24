<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;

class DateWisePhotoDownloadController extends Controller
{
    public function download(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $authUser = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Get manager company
        |--------------------------------------------------------------------------
        */

        $companyId = $authUser->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Company not found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Date range
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | ZIP filename
        |--------------------------------------------------------------------------
        */

        $zipFilename =
            'Photos_' .
            $startDate->format('Y-m-d') .
            '_to_' .
            $endDate->format('Y-m-d') .
            '.zip';

        /*
        |--------------------------------------------------------------------------
        | Create streaming ZIP
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | This ZIP is NOT saved in storage/app.
        | ZipStream sends it directly to the browser.
        |
        */

        $zip = new ZipStream(
            outputName: $zipFilename,
            sendHttpHeaders: true,
            defaultEnableZeroHeader: true,
        );

        /*
        |--------------------------------------------------------------------------
        | Get users in sequence
        |--------------------------------------------------------------------------
        */

        $userIds = Photo::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [
                $startDate,
                $endDate,
            ])
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id');

        /*
        |--------------------------------------------------------------------------
        | Process USER 1 → USER 2 → USER 3...
        |--------------------------------------------------------------------------
        */

        foreach ($userIds as $userId) {

            /*
            |--------------------------------------------------------------------------
            | Get user
            |--------------------------------------------------------------------------
            */

            $user = \App\Models\User::find($userId);

            if (!$user) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Safe user folder name
            |--------------------------------------------------------------------------
            */

            $userName = preg_replace(
                '/[\\\\\/:*?"<>|]/',
                '_',
                $user->name ?? ('User_' . $userId)
            );

            /*
            |--------------------------------------------------------------------------
            | Get this user's photos
            |--------------------------------------------------------------------------
            |
            | cursor() prevents loading all photos into memory.
            |
            */

            $photos = Photo::query()
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->whereBetween('created_at', [
                    $startDate,
                    $endDate,
                ])
                ->orderBy('id')
                ->cursor();

            /*
            |--------------------------------------------------------------------------
            | Add each photo to ZIP
            |--------------------------------------------------------------------------
            */

            foreach ($photos as $photo) {

                $path = $photo->path;

                /*
                |--------------------------------------------------------------------------
                | Check physical file
                |--------------------------------------------------------------------------
                */

                if (!Storage::disk('public')->exists($path)) {
                    continue;
                }

                $fullPath = Storage::disk('public')->path($path);

                if (!is_file($fullPath)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Remove company/user IDs from ZIP path
                |--------------------------------------------------------------------------
                |
                | Example:
                |
                | 28/130/ProjectA/photo.jpg
                |
                | becomes:
                |
                | User Name/ProjectA/photo.jpg
                |
                */

                $parts = explode('/', trim($path, '/'));

                if (count($parts) >= 3) {

                    $folderPath = implode(
                        '/',
                        array_slice($parts, 2)
                    );

                } else {

                    $folderPath = basename($path);
                }

                /*
                |--------------------------------------------------------------------------
                | Final path inside ZIP
                |--------------------------------------------------------------------------
                */

                $zipPath = $userName . '/' . $folderPath;

                /*
                |--------------------------------------------------------------------------
                | Add file directly to ZIP stream
                |--------------------------------------------------------------------------
                */

                $zip->addFileFromPath(
                    fileName: $zipPath,
                    path: $fullPath,
                );

                $photo->update([
                    'backed_up_at' => now(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Finish ZIP
        |--------------------------------------------------------------------------
        */

        $zip->finish();
    }
}