<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Support\Facades\Cache;
use ZipArchive;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateCompanyBackup implements ShouldQueue
{
    public function handle()
    {
        $zipPath = storage_path('app/company_backups/company_backup.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $total = Photo::count();

        Cache::put('company_backup_total', $total);
        Cache::put('company_backup_processed', 0);
        Cache::put('company_backup_status', 'processing');

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        Photo::chunk(500, function ($photos) use ($zip) {
            foreach ($photos as $photo) {
                $path = storage_path('app/public/' . $photo->path);

                if (file_exists($path)) {
                    // keep folder structure inside zip
                    $zip->addFile($path, $photo->path);
                }
            }

            Cache::increment('company_backup_processed', $photos->count());
        });

        $zip->close();

        Cache::put('company_backup_status', 'ready');
    }
}
