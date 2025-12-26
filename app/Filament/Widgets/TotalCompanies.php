<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class TotalCompanies extends BaseWidget
{
    protected array|string|int $columnSpan = 'full';

    protected function getStats(): array
    {
        $cards = [];
        $currentUser = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN VIEW
        |--------------------------------------------------------------------------
        */
        if ($currentUser?->hasRole('Super Admin')) {
            $cards[] = Card::make('Total Companies', Company::count())
                ->description('Registered in system')
                ->descriptionIcon('heroicon-o-building-office')
                ->chart([7, 10, 12, 15, 20, 25, 30])
                ->color('success');

            $cards[] = Card::make('Total Admins', User::where('role', 'admin')->count())
                ->description('All admins in system')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary');

            $cards[] = Card::make('Total Managers', User::where('role', 'manager')->count())
                ->description('All managers in system')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning');

            $cards[] = Card::make('Total Users', User::where('role', 'user')->count())
                ->description('All users in system')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');

            // ✅ NEW: Global storage usage for Super Admin
            $totalSize = 0;

            $companyIds = Company::pluck('id');

            foreach ($companyIds as $companyId) {

                $companyPath = storage_path("app/public/{$companyId}");

                if (!is_dir($companyPath)) {
                    continue;
                }

                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($companyPath, FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    if ($file->isFile()) {
                        $totalSize += $file->getSize();
                    }
                }
            }

            $totalSizeMB = round($totalSize / (1024 ** 2), 2);
            $totalSizeGB = round($totalSize / (1024 ** 3), 2);
            $displaySize = "{$totalSizeGB} GB (~{$totalSizeMB} MB)";

            $cards[] = Card::make('Total Storage Used (All Users)', $displaySize)
                ->description('Combined storage of all users')
                ->descriptionIcon('heroicon-o-server')
                ->color('success');

            // ✅ NEW: Global total photo count (all users)
            $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $totalPhotos = 0;

            $companyIds = Company::pluck('id');

            foreach ($companyIds as $companyId) {

                $companyPath = storage_path("app/public/{$companyId}");

                if (!is_dir($companyPath)) {
                    continue;
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
            }

            $cards[] = Card::make('Total Photos (All Users)', number_format($totalPhotos))
                ->description('All image files uploaded in system')
                ->descriptionIcon('heroicon-o-photo')
                ->color('info');

        }
        /*
        |--------------------------------------------------------------------------
        | NORMAL ADMIN / MANAGER VIEW
        |--------------------------------------------------------------------------
        */
        else {
            if ($currentUser->canShow('total_managers')) {
                $cards[] = Card::make(
                    'Total Managers',
                    User::where('role', 'manager')->where('created_by', $currentUser->id)->count()
                )
                    ->description('Managers created by you')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('warning');
            }

            if ($currentUser->canShow('total_users')) {

                $managerIds = User::where('role', 'manager')
                    ->where('created_by', $currentUser->id)
                    ->pluck('id')
                    ->toArray();

                $totalUsers = User::where('role', 'user')
                    ->where(function ($query) use ($currentUser, $managerIds) {
                        $query->where('created_by', $currentUser->id)
                            ->orWhereIn('created_by', $managerIds);
                    })
                    ->count();

                $cards[] = Card::make('Total Users', $totalUsers)
                    ->description('Users created by you and your managers')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info');
            }

            // ✅ NEW: Add per-admin storage usage including all managers' users
            $companyId = $currentUser->company_id;

            $totalSize = 0;

            if ($currentUser->hasRole('admin')) {

                $companyPath = storage_path("app/public/{$companyId}");

                if (is_dir($companyPath)) {
                    foreach (new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($companyPath, FilesystemIterator::SKIP_DOTS)
                    ) as $file) {
                        if ($file->isFile()) {
                            $totalSize += $file->getSize();
                        }
                    }
                }
            }

            if ($currentUser->hasRole('manager')) {

                // users created by this manager
                $userIds = User::where('created_by', $currentUser->id)
                    ->pluck('id')
                    ->toArray();

                // optional: include manager’s own uploads
                $userIds[] = $currentUser->id;

                foreach ($userIds as $uid) {

                    $userPath = storage_path("app/public/{$companyId}/{$uid}");

                    if (!is_dir($userPath)) continue;

                    foreach (new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($userPath, FilesystemIterator::SKIP_DOTS)
                    ) as $file) {
                        if ($file->isFile()) {
                            $totalSize += $file->getSize();
                        }
                    }
                }
            }

            // Step 5: Convert bytes to MB/GB for display
            $totalSizeMB = round($totalSize / (1024 ** 2), 2);
            $totalSizeGB = round($totalSize / (1024 ** 3), 2);

            $displaySize = ($totalSizeGB >= 1)
                ? "{$totalSizeGB} GB (~{$totalSizeMB} MB)"
                : "{$totalSizeMB} MB";

            if ($currentUser->canShow('total_storage')) {
                // Get the user's max storage (in MB) and convert it for display
                $maxStorageMB = $currentUser->max_storage ?? 0;
                $maxStorageGB = round($maxStorageMB / 1024, 2);

                // Used storage display
                $usedStorageDisplay = ($totalSizeGB >= 1)
                    ? "{$totalSizeGB} GB (~{$totalSizeMB} MB)"
                    : "{$totalSizeMB} MB";

                // Max storage display
                $maxStorageDisplay = ($maxStorageGB >= 1)
                    ? "{$maxStorageGB} GB (~{$maxStorageMB} MB)"
                    : "{$maxStorageMB} MB";

                // Progress or ratio (e.g., used vs max)
                $percentUsed = $maxStorageMB > 0 ? round(($totalSizeMB / $maxStorageMB) * 100, 1) : 0;

                $desc = $maxStorageMB > 0
                    ? "Used: {$usedStorageDisplay} ({$percentUsed}% used)"
                    : "No storage limit assigned.";

                $color = $percentUsed >= 85 ? 'danger' : ($percentUsed >= 70 ? 'warning' : 'success');

                $cards[] = Card::make(
                    'Storage Used',
                    $totalSizeGB >= 1
                        ? "{$totalSizeGB} GB"
                        : "{$totalSizeMB} MB"
                )
                ->description(
                    $maxStorageMB > 0
                        ? "Limit: {$maxStorageDisplay} ({$percentUsed}% used)"
                        : "No storage limit assigned"
                )
                ->descriptionIcon('heroicon-o-server')
                ->color($color)
                ->chart(
                    $maxStorageMB > 0
                        ? [$percentUsed, 100 - $percentUsed]
                        : [$totalSizeMB, 0]
                );
            }

            // ✅ NEW: Add Total Photos card for Admins and Managers
            if ($currentUser->canShow('total_photos')) {

                $imageExtensions = ['jpg', 'jpeg', 'png'];
                $totalPhotos = 0;
                $companyId = $currentUser->company_id;

                // ================= ADMIN =================
                if ($currentUser->hasRole('admin')) {

                    $companyPath = storage_path("app/public/{$companyId}");

                    if (is_dir($companyPath)) {
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
                    }
                }

                // ================= MANAGER =================
                if ($currentUser->hasRole('manager')) {

                    // users created by this manager
                    $userIds = User::where('created_by', $currentUser->id)
                        ->pluck('id')
                        ->toArray();

                    // optional: include manager’s own uploads
                    $userIds[] = $currentUser->id;

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
                                in_array(strtolower($file->getExtension()), $imageExtensions)
                            ) {
                                $totalPhotos++;
                            }
                        }
                    }
                }

                $cards[] = Card::make('Total Photos', number_format($totalPhotos))
                    ->description(
                        $currentUser->hasRole('admin')
                            ? 'All photos in your company'
                            : 'Photos uploaded by your users'
                    )
                    ->descriptionIcon('heroicon-o-photo')
                    ->color('info');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL LIMIT CARD (Admin only)
        |--------------------------------------------------------------------------
        */
        if (!$currentUser->hasRole('Super Admin') && $currentUser?->canShow('total_limit')) {
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->pluck('id')
                ->toArray();

            $directlyCreated = User::where('created_by', $currentUser->id)->count();
            $assignedLimitToManagers = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->sum('max_limit');

            $createdCount = $directlyCreated + $assignedLimitToManagers;
            $maxLimit = $currentUser->max_limit ?? 0;

            if ($createdCount >= $maxLimit) {
                $cards[] = Card::make('Total Limit', $maxLimit)
                    ->description('Limit reached! Cannot create more.')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->chart([$createdCount, 0]);
            } else {
                $cards[] = Card::make('Total Limit', $maxLimit)
                    ->description("You've used {$createdCount} of {$maxLimit}")
                    ->descriptionIcon('heroicon-o-adjustments-horizontal')
                    ->color('success');
            }
        }

        return $cards;
    }
}
