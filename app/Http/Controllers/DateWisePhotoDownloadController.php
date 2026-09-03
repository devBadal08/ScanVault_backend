<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;
use App\Models\User;
use App\Models\Company;
use ZipStream\CompressionMethod;

class DateWisePhotoDownloadController extends Controller
{
    public function download(Request $request)
    {
        set_time_limit(0);

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
        | Admin company + child companies
        |--------------------------------------------------------------------------
        */

        $companyIds = collect([
            $companyId,
        ])
            ->merge(
                Company::where('parent_id', $companyId)->pluck('id')
            )
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Get managers created by this admin
        |--------------------------------------------------------------------------
        */

        $managerIds = User::where('role', 'manager')
            ->where('created_by', $authUser->id)
            ->pluck('id');

        $backupUserIds = User::where(function ($query) use ($authUser, $managerIds) {

            $query->where('id', $authUser->id)

                ->orWhere(function ($q) use ($authUser) {
                    $q->where('role', 'user')
                        ->where('created_by', $authUser->id);
                })

                ->orWhere(function ($q) use ($managerIds) {
                    $q->where('role', 'manager')
                        ->whereIn('id', $managerIds);
                })

                ->orWhere(function ($q) use ($managerIds) {
                    $q->where('role', 'user')
                        ->whereIn('created_by', $managerIds);
                });

        })->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Date range
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Check whether photos exist
        |--------------------------------------------------------------------------
        */

        $hasPhotos = Photo::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('user_id', $backupUserIds)
            ->whereBetween('created_at', [
                $startDate,
                $endDate,
            ])
            ->exists();

        if (!$hasPhotos) {
            return redirect()->back()->with(
                'error',
                "No photos or folders are available between {$startDate->format('d-m-Y')} and {$endDate->format('d-m-Y')}."
            );
        }

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
        | Get users
        |--------------------------------------------------------------------------
        */

        $userIds = Photo::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('user_id', $backupUserIds)
            ->whereBetween('created_at', [
                $startDate,
                $endDate,
            ])
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id');

        /*
        |--------------------------------------------------------------------------
        | Stream ZIP through Laravel
        |--------------------------------------------------------------------------
        */

        return response()->streamDownload(
            function () use (
                $zipFilename,
                $companyIds,
                $backupUserIds,
                $startDate,
                $endDate,
                $userIds
            ) {

                /*
                |--------------------------------------------------------------------------
                | Remove previous output buffers
                |--------------------------------------------------------------------------
                */

                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                /*
                |--------------------------------------------------------------------------
                | Create ZipStream
                |--------------------------------------------------------------------------
                |
                | STORE:
                | Images/videos/PDFs are already compressed.
                |
                | defaultEnableZeroHeader:
                | Allows efficient one-pass streaming.
                |
                | enableZip64:
                | Supports very large ZIP archives.
                |
                | sendHttpHeaders:
                | Laravel handles the HTTP headers.
                |
                */

                $zip = new ZipStream(
                    outputName: $zipFilename,
                    defaultCompressionMethod: CompressionMethod::STORE,
                    defaultEnableZeroHeader: true,
                    enableZip64: true,
                    sendHttpHeaders: false,
                );

                /*
                |--------------------------------------------------------------------------
                | Total files added
                |--------------------------------------------------------------------------
                */

                $count = 0;

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

                    $user = User::find($userId);

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
                    */

                    $photos = Photo::query()
                        ->whereIn('company_id', $companyIds)
                        ->where('user_id', $userId)
                        ->whereBetween('created_at', [
                            $startDate,
                            $endDate,
                        ])
                        ->orderBy('id')
                        ->cursor();

                    /*
                    |--------------------------------------------------------------------------
                    | Add photos to ZIP
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
                        | Final ZIP path
                        |--------------------------------------------------------------------------
                        */

                        $zipPath = $userName . '/' . $folderPath;

                        /*
                        |--------------------------------------------------------------------------
                        | Add file
                        |--------------------------------------------------------------------------
                        */

                        $zip->addFileFromPath(
                            fileName: $zipPath,
                            path: $fullPath,
                        );

                        $count++;

                        /*
                        |--------------------------------------------------------------------------
                        | Progress logging every 500 files
                        |--------------------------------------------------------------------------
                        */

                        if ($count % 500 === 0) {

                            \Log::info('DATEWISE ZIP download progress', [
                                'files_added' => $count,
                            ]);
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Make sure ZIP has files
                |--------------------------------------------------------------------------
                */

                if ($count === 0) {

                    \Log::warning('DATEWISE ZIP contains no valid files');

                    abort(404, 'No valid files found for the selected date range.');
                }

                /*
                |--------------------------------------------------------------------------
                | Finish ZIP
                |--------------------------------------------------------------------------
                */

                \Log::info('DATEWISE ZIP finish START', [
                    'files_added' => $count,
                ]);

                $zip->finish();

                \Log::info('DATEWISE ZIP finish COMPLETED', [
                    'files_added' => $count,
                ]);

                /*
                |--------------------------------------------------------------------------
                | ONLY AFTER successful ZIP finish
                |--------------------------------------------------------------------------
                */

                Photo::whereIn('company_id', $companyIds)
                    ->whereIn('user_id', $backupUserIds)
                    ->whereBetween('created_at', [
                        $startDate,
                        $endDate,
                    ])
                    ->update([
                        'backed_up_at' => now(),
                    ]);
            },

            $zipFilename,

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