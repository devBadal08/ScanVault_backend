<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
                ->color('success')
                ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => "\$dispatch('setStatusFilter', { filter: 'processed' })",
                ]);

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
            $directory = storage_path('app/public');
            $totalSize = 0;

            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            ) as $file) {
                $totalSize += $file->getSize();
            }

            $totalSizeMB = round($totalSize / (1024 ** 2), 2);
            $totalSizeGB = round($totalSize / (1024 ** 3), 2);
            $displaySize = "{$totalSizeGB} GB (≈{$totalSizeMB} MB)";

            $cards[] = Card::make('Total Storage Used (All Users)', $displaySize)
                ->description('Combined storage of all users')
                ->descriptionIcon('heroicon-o-server')
                ->color('success');

            // ✅ NEW: Global total photo count (all users)
            $imageExtensions = ['jpg', 'jpeg', 'png'];
            $totalPhotos = 0;

            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            ) as $file) {
                if (in_array(strtolower($file->getExtension()), $imageExtensions)) {
                    $totalPhotos++;
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
                $cards[] = Card::make(
                    'Total Users',
                    User::where('role', 'user')->where('created_by', $currentUser->id)->count()
                )
                    ->description('Users created by you')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info');
            }

            // ✅ NEW: Add per-admin storage usage including all managers' users
            $totalSize = 0;

            // Step 1: Get all direct users created by this admin/manager
            $userIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();

            // Step 2: Also include users created by managers under this admin
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->pluck('id')
                ->toArray();

            if (!empty($managerIds)) {
                $managerUserIds = User::whereIn('created_by', $managerIds)->pluck('id')->toArray();
                $userIds = array_merge($userIds, $managerUserIds);
            }

            // Step 3: Optionally include the admin's own folder (if exists)
            $userIds[] = $currentUser->id;

            // Step 4: Calculate total folder size for all collected user IDs
            foreach ($userIds as $uid) {
                $folderPath = storage_path("app/public/{$uid}");
                if (is_dir($folderPath)) {
                    foreach (new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS)
                    ) as $file) {
                        $totalSize += $file->getSize();
                    }
                }
            }

            // Step 5: Convert bytes to MB/GB for display
            $totalSizeMB = round($totalSize / (1024 ** 2), 2);
            $totalSizeGB = round($totalSize / (1024 ** 3), 2);

            $displaySize = ($totalSizeGB >= 1)
                ? "{$totalSizeGB} GB (≈{$totalSizeMB} MB)"
                : "{$totalSizeMB} MB";

            if ($totalSize > 0) {
                $cards[] = Card::make('Your Storage Used', $displaySize)
                    ->description('Total storage used by you')
                    ->descriptionIcon('heroicon-o-server')
                    ->color('success');
            } else {
                $cards[] = Card::make('Your Storage Used', '0 MB')
                    ->description('No files uploaded yet')
                    ->descriptionIcon('heroicon-o-server')
                    ->color('gray');
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
                    ->description("You’ve used {$createdCount} of {$maxLimit}")
                    ->descriptionIcon('heroicon-o-adjustments-horizontal')
                    ->color('success');
            }
        }

        return $cards;
    }
}
