<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Services\Stats\PhotoStatsService;

class PhotoStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (!$user->canShow('total_photos')) {
            return [];
        }

        $count = PhotoStatsService::get($user);

        return [
            Card::make('Total Photos', number_format($count))
                ->description('Photos uploaded by your users')
                ->descriptionIcon('heroicon-o-photo')
                ->color('info'),
        ];
    }
}
