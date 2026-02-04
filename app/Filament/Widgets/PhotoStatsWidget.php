<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Actions\Action;
use App\Services\Stats\PhotoStatsService;

class PhotoStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user->canShowTotalPhotos()) {
            return [
                Card::make('Total Photos', '•••')
                    ->description('Click eye icon to reveal')
                    ->descriptionIcon('heroicon-o-eye')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'togglePhotos',
                    ]),
            ];
        }

        $count = PhotoStatsService::get($user);

        return [
            Card::make('Total Photos', number_format($count))
                ->description('Photos uploaded by your users')
                ->descriptionIcon('heroicon-o-eye-slash')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'togglePhotos',
                ])
                ->color('info'),
        ];
    }

    /** Livewire action */
    public function togglePhotos(): void
    {
        $user = auth()->user();

        $user->update([
            'show_total_photos' => ! $user->show_total_photos,
        ]);
    }
}
