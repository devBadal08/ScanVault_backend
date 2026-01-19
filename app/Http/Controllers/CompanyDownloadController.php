<?php

namespace App\Http\Controllers;

use ZipArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyDownloadController extends Controller
{
    public function downloadAll()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Company folder (source)
        $companyPath = storage_path("app/public/{$companyId}");

        if (! is_dir($companyPath)) {
            return back()->withErrors('No data found for this company.');
        }

        // Company name for zip
        $company = DB::table('companies')->where('id', $companyId)->first();
        $companyName = $company?->company_name ?? 'company';

        $zipFileName = $companyName . '_Backup.zip';
        $zipPath = storage_path("app/temp/{$zipFileName}");

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors('Unable to create ZIP file.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($companyPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($companyPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
