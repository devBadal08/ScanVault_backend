<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Photo;
use App\Models\Folder;

class FixFolderStructure extends Command
{
    protected $signature = 'folders:fix-structure';
    protected $description = 'Fix old folders (company_id, path, parent_id) using photos table';

    public function handle()
    {
        $this->info('Starting folder structure fix...');

        Photo::chunk(500, function ($photos) {

            foreach ($photos as $photo) {

                if (!$photo->path) continue;

                $parts = explode('/', trim($photo->path, '/'));

                if (count($parts) < 3) continue;

                $companyId = $photo->company_id;
                $userId    = $photo->user_id;

                $currentPath = '';
                $parentId = null;

                foreach ($parts as $index => $part) {

                    // Skip file name
                    if ($index === count($parts) - 1) break;

                    $currentPath .= ($currentPath ? '/' : '') . $part;

                    // ✅ Ensure correct full path with company_id
                    if (!str_starts_with($currentPath, $companyId)) {
                        $currentPath = $companyId . '/' . implode('/', array_slice($parts, 1, $index));
                    }

                    $folder = Folder::where('path', $currentPath)->first();

                    if (!$folder) {

                        $folder = Folder::create([
                            'name' => $part,
                            'path' => $currentPath,
                            'company_id' => $companyId,
                            'user_id' => $userId,
                            'parent_id' => $parentId,
                        ]);

                        $this->line("Created: {$currentPath}");
                    } else {

                        // ✅ Fix missing data
                        $folder->update([
                            'company_id' => $companyId,
                            'user_id' => $userId,
                            'parent_id' => $parentId,
                        ]);
                    }

                    $parentId = $folder->id;
                }
            }
        });

        $this->info('Folder structure fixed successfully.');
        return 0;
    }
}