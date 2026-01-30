<?php

namespace App\Services\Stats;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class StorageStatsService
{
    public static function get($user): array
    {
        $totalBytes = 0;

        // SUPER ADMIN (optional)
        if ($user->hasRole('Super Admin')) {
            return [
                'used_mb' => 0,
                'max_mb'  => 0,
            ];
        }

        $companyId = $user->company_id;

        // ADMIN
        if ($user->hasRole('admin')) {
            $companyPath = storage_path("app/public/{$companyId}");

            if (is_dir($companyPath)) {
                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($companyPath, FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    if ($file->isFile()) {
                        $totalBytes += $file->getSize();
                    }
                }
            }

            return [
                'used_mb' => round($totalBytes / (1024 * 1024), 2),
                'max_mb'  => (float) ($user->max_storage ?? 0),
            ];
        }

        // MANAGER
        if ($user->hasRole('manager')) {

            $userIds = \App\Models\User::where('created_by', $user->id)
                ->pluck('id')
                ->toArray();

            // include manager’s own uploads
            $userIds[] = $user->id;

            foreach ($userIds as $uid) {
                $userPath = storage_path("app/public/{$companyId}/{$uid}");

                if (!is_dir($userPath)) {
                    continue;
                }

                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($userPath, FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    if ($file->isFile()) {
                        $totalBytes += $file->getSize();
                    }
                }
            }

            return [
                'used_mb' => round($totalBytes / (1024 * 1024), 2),
                'max_mb'  => (float) ($user->max_storage ?? 0),
            ];
        }

        return [
            'used_mb' => 0,
            'max_mb'  => 0,
        ];
    }
}
