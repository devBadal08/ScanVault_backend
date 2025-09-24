<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class DownloadController extends Controller
{
    public function download(Request $request)
    {
        $folder = urldecode($request->query('path'));
        $folderPath = storage_path('app/public/' . $folder);

        if (!is_dir($folderPath)) {
            return response()->json(['error' => 'Folder does not exist.'], 404);
        }

        $folderName = basename($folder);

        // ✅ Get company info of the logged-in user
        $user = Auth::user();
        $company = \DB::table('companies')->where('id', $user->company_id)->first();

        // If company exists, use its name, else fallback
        $companyPrefix = $company ? $company->company_name : 'company';

        // ✅ Zip filename with company prefix
        $zipFileName = $companyPrefix . '_' . $folderName . '.zip';

        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'Could not create zip.'], 500);
    }

    public function downloadToday(Request $request)
    {
        $today = now()->toDateString();

        // 1. Get all images uploaded today
        $images = \DB::table('photos')
            ->whereDate('created_at', $today)
            ->get();

        if ($images->isEmpty()) {
            return response()->json(['error' => 'No images found for today.'], 404);
        }

        // 2. Prepare zip
        $zipFileName = 'today_images_' . $today . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($images as $img) {
                $filePath = storage_path('app/public/' . $img->path);
                if (file_exists($filePath)) {
                    // Add to zip (keep relative folder structure)
                    $zip->addFile($filePath, $img->path);
                }
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'Could not create zip.'], 500);
    }
}
