<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class BackfillCompanyStats extends Command
{
    protected $signature = 'companies:backfill-stats';
    protected $description = 'Backfill used_storage_mb and total_photos for companies';

    public function handle()
    {
        $this->info('Starting company stats backfill...');

        $imageExtensions = ['jpg', 'jpeg', 'png'];

        Company::chunk(10, function ($companies) use ($imageExtensions) {

            foreach ($companies as $company) {

                $companyPath = storage_path("app/public/{$company->id}");

                $totalBytes = 0;
                $totalPhotos = 0;

                if (is_dir($companyPath)) {

                    foreach (new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($companyPath, FilesystemIterator::SKIP_DOTS)
                    ) as $file) {

                        if (!$file->isFile()) {
                            continue;
                        }

                        $totalBytes += $file->getSize();

                        if (in_array(strtolower($file->getExtension()), $imageExtensions)) {
                            $totalPhotos++;
                        }
                    }
                }

                $usedStorageMB = round($totalBytes / (1024 * 1024), 2);

                $company->update([
                    'used_storage_mb' => $usedStorageMB,
                    'total_photos'    => $totalPhotos,
                ]);

                $this->line(
                    "Company {$company->id} → Storage: {$usedStorageMB} MB | Photos: {$totalPhotos}"
                );
            }
        });

        $this->info('Company stats backfill completed.');
        return 0;
    }
}
