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
            $cards[] = Card::make('Total Companies', Company::count())
                ->description('Registered in system')
                ->descriptionIcon('heroicon-o-building-office')
                ->chart([7, 10, 12, 15, 20, 25, 30])
                ->color('success')
                ->extraAttributes(['class' => 'shadow-lg rounded-xl']);

            $cards[] = Card::make('Total Admins', User::where('role', 'admin')->count())
                ->description('Users with Admin role')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary');

            $cards[] = Card::make('Total Managers', User::where('role', 'manager')->count())
                ->description('All managers')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning');

            $cards[] = Card::make('Total Users', User::where('role', 'user')->count())
                ->description('All users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        if ($currentUser?->hasRole('admin') || $currentUser?->hasRole('manager')) {
            $createdManagers = User::where('role', 'manager')->where('created_by', $currentUser->id)->count();
            $createdUsers = User::where('role', 'user')->where('created_by', $currentUser->id)->count();
            $totalCreated = User::where('created_by', $currentUser->id)->count();

            $maxLimit = $currentUser->max_limit ?? 0;
            $remaining = max($maxLimit - $totalCreated, 0);

            if ($currentUser->hasRole('admin')) {
                $cards[] = Card::make('Total Managers', $createdManagers)
                    ->description('Managers created by you')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('warning');
            }

            $cards[] = Card::make('Total Users', $createdUsers)
                ->description('Users created by you')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');

            $percentUsed = ($maxLimit > 0) ? ($totalCreated / $maxLimit) * 100 : 0;

            if ($totalCreated >= $maxLimit) {
                $cards[] = Card::make('Total Limit', $maxLimit)
                    ->description('Limit reached! Cannot create more.')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('danger') // red
                    ->chart([$totalCreated, 0]);
            } else {
                $cards[] = Card::make('Total Limit', $maxLimit)
                    ->description("You’ve used {$totalCreated} of {$maxLimit}")
                    ->descriptionIcon('heroicon-o-adjustments-horizontal')
                    ->color('success');
            }
        }
        return $cards;
    }
}
