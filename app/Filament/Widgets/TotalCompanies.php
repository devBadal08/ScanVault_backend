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
        return [
            Card::make('Total Companies', Company::count())
                ->description('Registered in system')
                ->descriptionIcon('heroicon-o-building-office')
                ->chart([7, 10, 12, 15, 20, 25, 30]) // fake trend data
                ->color('success')
                ->extraAttributes(['class' => 'shadow-lg rounded-xl']), // make text slightly larger

            Card::make('Total Admins', User::where('role', 'admin')->count())
                ->description('Users with Admin role')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Card::make('Total Managers', User::where('role', 'manager')->count())
                ->description('Users with Manager role')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning'),

            Card::make('Total Users', User::where('role', 'user')->count())
                ->description('Regular Users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
