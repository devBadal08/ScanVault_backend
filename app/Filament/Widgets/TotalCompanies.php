<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Company;
use App\Models\User;

class TotalCompanies extends BaseWidget
{
    protected function getStats(): array
    {
        $cards = [];

        if (auth()->user()?->hasRole('Super Admin')) {
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

        if (auth()->user()?->hasRole('admin')) {
            $cards[] = Card::make('Total Managers', User::where('role', 'manager')->where('created_by', auth()->id())->count())
                ->description('Managers created by you')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning');

            $cards[] = Card::make('Total Users', User::where('role', 'user')->where('created_by', auth()->id())->count())
                ->description('Users created by you')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        if (auth()->user()?->hasRole('manager')) {
            $cards[] = Card::make('Total Users', User::where('role', 'user')->where('created_by', auth()->id())->count())
                ->description('Users created by you')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        return $cards;
    }
}
