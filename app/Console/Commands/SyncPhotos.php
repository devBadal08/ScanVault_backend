<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Carbon\Carbon;
use App\Models\Folder;

class SyncPhotos extends Command
{
    protected $signature = 'photos:sync';

    protected $description = 'Sync storage photos to database';

    public function handle()
    {
        $basePath = storage_path('app/public');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $basePath,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $batch = [];
        $batchSize = 500;

        $inserted = 0;

        foreach ($iterator as $file) {

            if (!$file->isFile()) {
                continue;
            }

            $relativePath = str_replace(
                $basePath . DIRECTORY_SEPARATOR,
                '',
                $file->getPathname()
            );

            $parts = explode('/', str_replace('\\', '/', $relativePath));

            if (count($parts) < 4) {
                continue;
            }

            $companyId = (int) $parts[0];
            $userId    = (int) $parts[1];

            if (!$companyId || !$userId) {
                continue;
            }

            // Folder path
            $folderPath = implode('/', array_slice($parts, 0, -1));
            $folderName = basename($folderPath);

            // Find/Create Folder
            $folder = Folder::firstOrCreate(
                [
                    'path' => $folderPath,
                ],
                [
                    'name'       => $folderName,
                    'company_id' => $companyId,
                    'user_id'    => $userId,
                    'parent_id'  => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

            $type = match ($extension) {
                'mp4', 'avi', 'mov' => 'video',
                'pdf' => 'pdf',
                default => 'image',
            };

            $fileDate = null;

            if ($type === 'image') {

                $exif = @exif_read_data($file->getPathname());

                if (!empty($exif['DateTimeOriginal'])) {

                    try {

                        $fileDate = Carbon::createFromFormat(
                            'Y:m:d H:i:s',
                            $exif['DateTimeOriginal']
                        );

                    } catch (\Exception $e) {
                        $fileDate = null;
                    }
                }
            }

            if (!$fileDate) {

                $fileDate = Carbon::createFromTimestamp(
                    $file->getMTime()
                );
            }

            $batch[] = [
                'path'        => $relativePath,
                'type'        => $type,
                'user_id'     => $userId,
                'company_id'  => $companyId,
                'uploaded_by' => $userId,
                'folder_id'   => $folder->id,
                'created_at'  => $fileDate,
                'updated_at'  => now(),
            ];

            if (count($batch) >= $batchSize) {

                DB::table('photos')->insertOrIgnore($batch);

                $inserted += count($batch);

                $this->info("Processed {$inserted} records");

                $batch = [];
            }
        }

        if (!empty($batch)) {

            DB::table('photos')->insertOrIgnore($batch);

            $inserted += count($batch);
        }

        $this->info("Completed. Total Processed: {$inserted}");

        // Recalculate company stats
        $this->recalculateCompanyStats();

        return Command::SUCCESS;
    }

    protected function recalculateCompanyStats()
    {
        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {

            $photoCount = DB::table('photos')
                ->where('company_id', $companyId)
                ->where('type', 'image')
                ->count();

            $storageMB = 0;

            $files = DB::table('photos')
                ->where('company_id', $companyId)
                ->pluck('path');

            foreach ($files as $path) {

                $fullPath = storage_path('app/public/' . $path);

                if (file_exists($fullPath)) {
                    $storageMB += filesize($fullPath);
                }
            }

            DB::table('companies')
                ->where('id', $companyId)
                ->update([
                    'total_photos'   => $photoCount,
                    'used_storage_mb'=> round($storageMB / 1024 / 1024, 2)
                ]);
        }
    }
}