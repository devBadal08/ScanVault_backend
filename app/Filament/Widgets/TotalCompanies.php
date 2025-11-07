<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Company;
use App\Models\User;

class TotalCompanies extends BaseWidget
{
    protected array|string|int $columnSpan = 'full';

    protected function getStats(): array
    {
        $cards = [];
        $currentUser = auth()->user();

        // ----------------------------------------------------------------------
        // SUPER ADMIN VIEW
        // ----------------------------------------------------------------------
        if ($currentUser?->hasRole('Super Admin')) {
            $cards[] = Card::make('Total Companies', Company::count())
                ->description('Registered in system')
                ->descriptionIcon('heroicon-o-building-office')
                ->chart([7, 10, 12, 15, 20, 25, 30])
                ->color('success')
                ->extraAttributes([
                    'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                ]);

            $cards[] = Card::make('Total Admins', User::where('role', 'admin')->count())
                ->description('All admins in system')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                ]);

            $cards[] = Card::make('Total Managers', User::where('role', 'manager')->count())
                ->description('All managers in system')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                ]);

            $cards[] = Card::make('Total Users', User::where('role', 'user')->count())
                ->description('All users in system')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->extraAttributes([
                    'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                ]);

            // ✅ Total Storage Used (All Users)
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
                ->color('success')
                ->extraAttributes([
                    'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                ]);

            // ✅ Total Photos (All Users)
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
                ->color('info')
                ->extraAttributes([
                    'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                ]);
        }

        // ----------------------------------------------------------------------
        // NORMAL ADMIN / MANAGER VIEW
        // ----------------------------------------------------------------------
        else {
            if ($currentUser->canShow('total_managers')) {
                $cards[] = Card::make(
                    'Total Managers',
                    User::where('role', 'manager')->where('created_by', $currentUser->id)->count()
                )
                    ->description('Managers created by you')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('warning')
                    ->extraAttributes([
                        'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                    ]);
            }

            if ($currentUser->canShow('total_users')) {
                $cards[] = Card::make(
                    'Total Users',
                    User::where('role', 'user')->where('created_by', $currentUser->id)->count()
                )
                    ->description('Users created by you')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info')
                    ->extraAttributes([
                        'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                    ]);
            }

            // ✅ Calculate storage usage
            $totalSize = 0;
            $userIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->pluck('id')
                ->toArray();

            if (!empty($managerIds)) {
                $managerUserIds = User::whereIn('created_by', $managerIds)->pluck('id')->toArray();
                $userIds = array_merge($userIds, $managerUserIds);
            }

            $userIds[] = $currentUser->id;

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

            $totalSizeMB = round($totalSize / (1024 ** 2), 2);
            $totalSizeGB = round($totalSize / (1024 ** 3), 2);
            $displaySize = ($totalSizeGB >= 1)
                ? "{$totalSizeGB} GB (≈{$totalSizeMB} MB)"
                : "{$totalSizeMB} MB";

            if ($currentUser->canShow('total_storage')) {
                $maxStorageMB = $currentUser->max_storage ?? 0;
                $maxStorageGB = round($maxStorageMB / 1024, 2);

                $percentUsed = $maxStorageMB > 0 ? round(($totalSizeMB / $maxStorageMB) * 100, 1) : 0;
                $color = $percentUsed >= 85 ? 'danger' : ($percentUsed >= 70 ? 'warning' : 'success');

                $desc = $maxStorageMB > 0
                    ? "Used: {$displaySize} ({$percentUsed}% used)"
                    : "No storage limit assigned.";

                $cards[] = Card::make('Your Storage', $maxStorageGB >= 1 ? "{$maxStorageGB} GB" : "{$maxStorageMB} MB")
                    ->description($desc)
                    ->descriptionIcon('heroicon-o-server')
                    ->color($color)
                    ->chart([$percentUsed, 100 - $percentUsed])
                    ->extraAttributes([
                        'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                    ]);
            }

            // ✅ Total Photos (Admin + Manager)
            if ($currentUser->canShow('total_photos')) {
                $imageExtensions = ['jpg', 'jpeg', 'png'];
                $totalPhotos = 0;

                $userIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();
                $managerIds = User::where('role', 'manager')
                    ->where('created_by', $currentUser->id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($managerIds)) {
                    $managerUserIds = User::whereIn('created_by', $managerIds)->pluck('id')->toArray();
                    $userIds = array_merge($userIds, $managerUserIds);
                }

                $userIds[] = $currentUser->id;

                foreach ($userIds as $uid) {
                    $folderPath = storage_path("app/public/{$uid}");
                    if (is_dir($folderPath)) {
                        foreach (new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS)
                        ) as $file) {
                            if (in_array(strtolower($file->getExtension()), $imageExtensions)) {
                                $totalPhotos++;
                            }
                        }
                    }
                }

                $cards[] = Card::make('Total Photos', number_format($totalPhotos))
                    ->description('All photos uploaded by you')
                    ->descriptionIcon('heroicon-o-photo')
                    ->color('info')
                    ->extraAttributes([
                        'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                    ]);
            }
        }

        // ----------------------------------------------------------------------
        // TOTAL LIMIT CARD
        // ----------------------------------------------------------------------
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
                    ->chart([$createdCount, 0])
                    ->extraAttributes([
                        'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                    ]);
            } else {
                $cards[] = Card::make('Total Limit', $maxLimit)
                    ->description("You’ve used {$createdCount} of {$maxLimit}")
                    ->descriptionIcon('heroicon-o-adjustments-horizontal')
                    ->color('success')
                    ->extraAttributes([
                        'class' => 'text-center items-center justify-center rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg hover:scale-[1.03] hover:bg-gray-50'
                    ]);
            }
        }

        return $cards;
    }
}
