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

        // OFF state → no service call
        if (! $user->canShowTotalStorage()) {
            return [
                Card::make('Storage Used', '•••')
                    ->description('Click eye icon to reveal')
                    ->descriptionIcon('heroicon-o-eye')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'toggleStorage',
                    ]),
            ];
        }

        // ON state → load heavy service
        $stats = StorageStatsService::get($user);

        $usedMB = $stats['used_mb'];
        $maxMB  = $stats['max_mb'];

        $usedGB = round($usedMB / 1024, 2);
        $maxGB  = round($maxMB / 1024, 2);

        $usedDisplay = $usedGB >= 1 ? "{$usedGB} GB" : "{$usedMB} MB";
        $maxDisplay  = $maxGB >= 1 ? "{$maxGB} GB"  : "{$maxMB} MB";

        // NO LIMIT
        if ($maxMB <= 0) {
            return [
                Card::make('Storage Used', $usedDisplay)
                    ->description('Storage used by your users')
                    ->descriptionIcon('heroicon-o-eye-slash')
                    ->color('success')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'toggleStorage',
                    ]),
            ];
        }

        // LIMIT EXISTS
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
                        : 'heroicon-o-eye-slash'
                )
                ->color($color)
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'toggleStorage',
                ]),
        ];
    }

    public function toggleStorage(): void
    {
        $user = auth()->user();

        $user->update([
            'show_total_storage' => ! $user->show_total_storage,
        ]);
    }
}
