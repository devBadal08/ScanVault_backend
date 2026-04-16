<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\Folder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

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

        $user = Auth::user();
        $company = DB::table('companies')->where('id', $user->company_id)->first();
        $companyPrefix = $company ? $company->company_name : 'company';

        $zipFileName = $companyPrefix . '_' . $folderName . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

            $addedFiles = 0;

            // =====================================================
            // 1. ADD PHYSICAL FILES (Current Folder)
            // =====================================================
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();

                    if (!$filePath || !file_exists($filePath)) {
                        continue;
                    }

                    $relativePath = substr($filePath, strlen($folderPath) + 1);

                    $zip->addFile($filePath, $relativePath);
                    $zip->setCompressionName($relativePath, ZipArchive::CM_STORE);
                    $addedFiles++;
                }
            }

            // =====================================================
            // 2. ADD VIRTUAL / LINKED FOLDERS
            // =====================================================
            // Parse path to find the source folder in the database
            $pathParts = explode('/', trim($folder, '/'));
            if (count($pathParts) >= 3) {
                $folderCompanyId = (int) $pathParts[0];
                $folderUserId    = (int) $pathParts[1];
                $dbFolderName    = $pathParts[2];

                $sourceFolderModel = Folder::where('company_id', $folderCompanyId)
                    ->where('user_id', $folderUserId)
                    ->where('name', $dbFolderName)
                    ->first();

                if ($sourceFolderModel) {
                    // Get all linked folders
                    $linkedFolders = DB::table('folder_links')
                        ->join('folders', 'folders.id', '=', 'folder_links.target_folder_id')
                        ->where('folder_links.source_folder_id', $sourceFolderModel->id)
                        ->select('folders.company_id', 'folders.user_id', 'folders.name')
                        ->get();

                    foreach ($linkedFolders as $linked) {
                        // Find the true physical path of the linked folder
                        $linkedPhysicalPath = storage_path("app/public/{$linked->company_id}/{$linked->user_id}/{$linked->name}");

                        if (is_dir($linkedPhysicalPath)) {
                            $linkedFiles = new \RecursiveIteratorIterator(
                                new \RecursiveDirectoryIterator($linkedPhysicalPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO),
                                \RecursiveIteratorIterator::LEAVES_ONLY
                            );

                            foreach ($linkedFiles as $file) {
                                if (!$file->isDir()) {
                                    $filePath = $file->getRealPath();

                                    if (!$filePath || !file_exists($filePath)) {
                                        continue;
                                    }

                                    // Calculate relative path for inside the zip
                                    $relativeToLinked = substr($filePath, strlen($linkedPhysicalPath) + 1);
                                    
                                    // Prefix with the linked folder's name so it looks like a subfolder in the ZIP
                                    $zipInternalPath = $linked->name . '/' . $relativeToLinked;

                                    $zip->addFile($filePath, $zipInternalPath);
                                    $zip->setCompressionName($zipInternalPath, ZipArchive::CM_STORE);
                                    $addedFiles++;
                                }
                            }
                        }
                    }
                }
            }

            // =====================================================
            // FINALIZE ZIP
            // =====================================================
            if ($addedFiles === 0) {
                $zip->close();
                return response()->json(['error' => 'Folder is empty'], 400);
            }

            $zip->close();

            if (ob_get_level()) {
                ob_end_clean();
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'Could not create zip.'], 500);
    }

    public function downloadToday(Request $request)
    {
        $today = now()->toDateString();

        // 1. Get all images uploaded today
        $images = DB::table('photos')
            ->whereDate('created_at', $today)
            ->get();

        if ($images->isEmpty()) {
            Notification::make()
                ->title('No Images Found')
                ->body('No remaining images for today.')
                ->warning()
                ->send();

            return back();
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