<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Services\Stats\PhotoStatsService;

class PhotoStatsWidget extends StatsOverviewWidget
{
    public bool $showPhotos = false; // 👈 resets on reload

    protected function getStats(): array
    {
        $user = auth()->user();

        // 1️⃣ HARD PERMISSION GATE
        if (! $user->canShow('total_photos')) {
            return [];
        }

        // Eye OFF → masked (NO heavy calculation)
        if (! $this->showPhotos) {
            return [
                Card::make('Total Photos', '•••')
                    ->description('Click eye icon to reveal')
                    ->descriptionIcon('heroicon-o-eye')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'togglePhotos',
                        'wire:loading.class' => 'opacity-50',
                        'wire:target' => 'togglePhotos',
                    ]),
            ];
        }

        // Eye ON → heavy calculation
        $count = $user->companies()->sum('total_photos');

        return [
            Card::make('Total Photos', number_format($count))
                ->description('Photos uploaded by your users')
                ->descriptionIcon('heroicon-o-eye-slash')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'togglePhotos',
                    'wire:loading.class' => 'opacity-50',
                    'wire:target' => 'togglePhotos',
                ]),
        ];
    }

    /** Toggle visibility (local state only) */
    public function togglePhotos(): void
    {
        $this->showPhotos = ! $this->showPhotos;
    }

    public static function canView(): bool
    {
        return auth()->check() && ! auth()->user()->hasRole('Super Admin');
    }
}