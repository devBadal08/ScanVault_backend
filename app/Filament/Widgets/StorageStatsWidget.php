<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class StorageStatsWidget extends StatsOverviewWidget
{
    public bool $showStorage = false;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Permission Gate
        if (! $user->canShow('total_storage')) {
            return [];
        }

        // Eye OFF → masked
        if (! $this->showStorage) {
            return [
                Card::make('Storage Used', '•••')
                    ->description('Click eye icon to reveal')
                    ->descriptionIcon('heroicon-o-eye')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'toggleStorage',
                        'wire:loading.class' => 'opacity-50',
                        'wire:target' => 'toggleStorage',
                    ]),
            ];
        }

        // ✅ Get storage directly from DB
        $companies = $user->companies();

        $usedMB = $companies->sum('used_storage_mb');

        // 👉 If you have max per user
        $maxMB = $user->max_storage ?? 0;

        // 👉 OR if you store per company
        // $maxMB = $companies->sum('max_storage_mb');

        // Convert display
        $usedGB = round($usedMB / 1024, 2);
        $maxGB  = round($maxMB / 1024, 2);

        $usedDisplay = $usedGB >= 1 ? "{$usedGB} GB" : round($usedMB, 2) . " MB";
        $maxDisplay  = $maxGB >= 1 ? "{$maxGB} GB"  : round($maxMB, 2) . " MB";

        // No limit case
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

        // Percentage
        $percent = $maxMB > 0 ? round(($usedMB / $maxMB) * 100, 1) : 0;

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
        $this->showStorage = ! $this->showStorage;
    }

    public static function canView(): bool
    {
        return auth()->check() && ! auth()->user()->hasRole('Super Admin');
    }
}