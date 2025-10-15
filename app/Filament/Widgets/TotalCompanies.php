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
            // Super Admin sees ALL totals, ignoring permissions
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

        } else {
            // Normal admin only sees counts they have permission for
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

        // total_limit card for Admins (not for Super Admin)
        if (!$currentUser->hasRole('Super Admin') && $currentUser?->canShow('total_limit')) {
            // Get manager IDs created by this admin
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->pluck('id')
                ->toArray();

            // Count direct creations by admin (managers + users)
            $directlyCreated = User::where('created_by', $currentUser->id)->count();

            // ✅ Sum of limits assigned to managers
            $assignedLimitToManagers = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->sum('max_limit');

            // ✅ Total usage = direct users + indirect users + assigned manager limits
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
