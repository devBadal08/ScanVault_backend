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

        if ($currentUser?->hasRole('Super Admin')) {
            // Super Admin sees all
            $cards[] = Card::make('Total Companies', Company::count())
                ->description('Registered in system')
                ->descriptionIcon('heroicon-o-building-office')
                ->chart([7, 10, 12, 15, 20, 25, 30])
                ->color('success');

            if ($currentUser->canShow('total_admins')) {
                $cards[] = Card::make('Total Admins', User::where('role', 'admin')->count())
                    ->description('Users with Admin role')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('primary');
            }

            if ($currentUser->canShow('total_managers')) {
                $cards[] = Card::make('Total Managers', User::where('role', 'manager')->count())
                    ->description('All managers')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('warning');
            }

            if ($currentUser->canShow('total_users')) {
                $cards[] = Card::make('Total Users', User::where('role', 'user')->count())
                    ->description('All users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info');
            }
        } else {
            // Normal admin: only counts their own created managers & users
            if ($currentUser->canShow('total_managers')) {
                $cards[] = Card::make('Total Managers', User::where('role', 'manager')->where('created_by', $currentUser->id)->count())
                    ->description('Managers created by you')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('warning');
            }

            if ($currentUser->canShow('total_users')) {
                $cards[] = Card::make('Total Users', User::where('role', 'user')->where('created_by', $currentUser->id)->count())
                    ->description('Users created by you')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info');
            }
        }

        // total_limit card (shown for both)
        if ($currentUser?->canShow('total_limit')) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
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
