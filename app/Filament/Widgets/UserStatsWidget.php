<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Services\Stats\UserStatsService;

class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user  = auth()->user();
        $stats = UserStatsService::get($user);

        $cards = [];

        // Super Admin
        if ($user->hasRole('Super Admin')) {

            $cards[] = Card::make(
                'Total Companies',
                $stats['total_companies'] ?? 0
            )
            ->description('Registered in system')
            ->descriptionIcon('heroicon-o-building-office')
            ->color('success');

            $cards[] = Card::make(
                'Total Admins',
                $stats['total_admins'] ?? 0
            )
            ->description('All admins in system')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('primary');

            $cards[] = Card::make(
                'Total Managers',
                $stats['total_managers'] ?? 0
            )
            ->description('All managers in system')
            ->descriptionIcon('heroicon-m-user')
            ->color('warning');

            $cards[] = Card::make(
                'Total Users',
                $stats['total_users'] ?? 0
            )
            ->description('All users in system')
            ->descriptionIcon('heroicon-m-users')
            ->color('info');

            $storageMB = $stats['total_storage_mb'] ?? 0;
            $storageGB = round($storageMB / 1024, 2);

            $cards[] = Card::make(
                'Total Storage Used',
                $storageGB >= 1 ? "{$storageGB} GB" : "{$storageMB} MB"
            )
            ->description('Combined storage of all users')
            ->descriptionIcon('heroicon-o-server')
            ->color('success');

            $cards[] = Card::make(
                'Total Photos',
                number_format($stats['total_photos'] ?? 0)
            )
            ->description('All image files uploaded in system')
            ->descriptionIcon('heroicon-o-photo')
            ->color('info');
        }

        // Admin
        if ($user->hasRole('admin')) {

            if ($user->canShow('total_admins')) {
                $cards[] = Card::make(
                    'Total Admins',
                    $stats['total_admins'] ?? 0
                );
            }

            if ($user->canShow('total_managers')) {
                $cards[] = Card::make('Total Managers', $stats['total_managers'] ?? 0)
                    ->description('Managers created by you')
                    ->descriptionIcon('heroicon-m-user')
                        ->color('warning');
            }

            if ($user->canShow('total_users')) {
                $cards[] = Card::make('Total Users', $stats['total_users'] ?? 0)
                    ->description('Users created by you and your managers')
                        ->descriptionIcon('heroicon-m-users')
                        ->color('info');
            }

            if ($user->canShow('total_limit') && isset($stats['limit'])) {

                $limit = $stats['limit'];

                $cards[] = Card::make(
                    'Total Limit',
                    $limit['max']
                )
                ->description($limit['description'])
                ->descriptionIcon(
                    $limit['reached']
                        ? 'heroicon-o-exclamation-triangle'
                        : 'heroicon-o-adjustments-horizontal'
                )
                ->color($limit['color'])
                ->chart([
                    $limit['used'],
                    max(0, $limit['max'] - $limit['used']),
                ]);
            }
        }

        // Manager
        if ($user->hasRole('manager')) {

            if ($user->canShow('total_users')) {
                $cards[] = Card::make('Total Users', $stats['total_users'] ?? 0)
                    ->description('Users created by you')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info');
            }

            if ($user->canShow('total_limit') && isset($stats['limit'])) {

                $limit = $stats['limit'];

                $cards[] = Card::make(
                    'Total Limit',
                    $limit['max']
                )
                ->description($limit['description'])
                ->descriptionIcon(
                    $limit['reached']
                        ? 'heroicon-o-exclamation-triangle'
                        : 'heroicon-o-adjustments-horizontal'
                )
                ->color($limit['color'])
                ->chart([
                    $limit['used'],
                    max(0, $limit['max'] - $limit['used']),
                ]);
            }
        }

        return $cards;
    }
}
