<?php

namespace App\Services\Stats;

use App\Models\User;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class PhotoStatsService
{
    public static function get($user): int
    {
        $imageExtensions = ['jpg', 'jpeg', 'png'];
        $totalPhotos = 0;

        // ================= SUPER ADMIN =================
        if ($user->hasRole('Super Admin')) {
            // You already handle this elsewhere (global)
            return 0;
        }

        $companyId = $user->company_id;

        // ================= ADMIN =================
        if ($user->hasRole('admin')) {
            $companyPath = storage_path("app/public/{$companyId}");

            if (!is_dir($companyPath)) {
                return 0;
            }

            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($companyPath, FilesystemIterator::SKIP_DOTS)
            ) as $file) {
                if (
                    $file->isFile() &&
                    in_array(strtolower($file->getExtension()), $imageExtensions)
                ) {
                    $totalPhotos++;
                }
            }

            return $totalPhotos;
        }

        // ================= MANAGER =================
        if ($user->hasRole('manager')) {

            $companyId = $user->company_id;

            // users created by this manager
            $userIds = \App\Models\User::where('created_by', $user->id)
                ->pluck('id')
                ->toArray();

            // include manager's own uploads if any
            $userIds[] = $user->id;

            foreach ($userIds as $uid) {

                $userPath = storage_path("app/public/{$companyId}/{$uid}");

                if (!is_dir($userPath)) {
                    continue;
                }

                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($userPath, FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    if (
                        $file->isFile() &&
                        in_array(strtolower($file->getExtension()), ['jpg','jpeg','png'])
                    ) {
                        $totalPhotos++;
                    }
                }
            }

            return $totalPhotos;
        }
        return 0;
    }
}
