<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Services\Stats\StorageStatsService;

class StorageStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (!$user->canShow('total_storage')) {
            return [];
        }

        $stats = StorageStatsService::get($user);

        $usedMB = $stats['used_mb'];
        $maxMB  = $stats['max_mb'];

        $usedGB = round($usedMB / 1024, 2);
        $maxGB  = round($maxMB / 1024, 2);

        $usedDisplay = $usedGB >= 1 ? "{$usedGB} GB" : "{$usedMB} MB";
        $maxDisplay  = $maxGB >= 1 ? "{$maxGB} GB"  : "{$maxMB} MB";

        // Case 1: NO LIMIT ASSIGNED
        if ($maxMB <= 0) {
            return [
                Card::make('Storage Used', $usedDisplay)
                    ->description('Storage used by your users')
                    ->descriptionIcon('heroicon-o-server')
                    ->color('success'),
            ];
        }

        // Case 2: LIMIT EXISTS
        $percent = round(($usedMB / $maxMB) * 100, 1);

        $color = match (true) {
            $percent >= 90 => 'danger',
            $percent >= 75 => 'warning',
            default        => 'success',
        };

        return [
            Card::make('Storage Used', $usedDisplay)
                ->description("Used {$usedDisplay} of {$maxDisplay} ({$percent}%)")
                ->descriptionIcon(
                    $percent >= 90
                        ? 'heroicon-o-exclamation-triangle'
                        : 'heroicon-o-server'
                )
                ->color($color),
        ];
    }
}
