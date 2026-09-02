<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;

class PhotoStatsWidget extends StatsOverviewWidget
{
    public bool $showPhotos = false;
    public bool $showLifetimePhotos = false;

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        // Permission
        if (! $user->canShow('total_photos')) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Get values
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'manager') {

            $count = User::where('role', 'user')
                ->where('created_by', $user->id)
                ->sum('total_photos');

        } else {

            $count = $user->companies()
                ->sum('total_photos');
        }

        $lifetimeCount = $user->companies()
            ->sum('lifetime_total_photos');

        /*
        |--------------------------------------------------------------------------
        | Total Photos Card
        |--------------------------------------------------------------------------
        */

        $totalPhotosCard = Card::make(
            'Total Photos',
            $this->showPhotos
                ? number_format($count)
                : '•••'
        )
            ->description(
                $this->showPhotos
                    ? 'Photos currently stored'
                    : 'Click eye icon to reveal'
            )
            ->descriptionIcon(
                $this->showPhotos
                    ? 'heroicon-o-eye-slash'
                    : 'heroicon-o-eye'
            )
            ->color('info')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => 'togglePhotos',
                'wire:loading.class' => 'opacity-50',
                'wire:target' => 'togglePhotos',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Lifetime Total Photos Card
        |--------------------------------------------------------------------------
        */

        $lifetimePhotosCard = Card::make(
            'Lifetime Total Photos',
            $this->showLifetimePhotos
                ? number_format($lifetimeCount)
                : '•••'
        )
            ->description(
                $this->showLifetimePhotos
                    ? 'Total photos ever uploaded'
                    : 'Click eye icon to reveal'
            )
            ->descriptionIcon(
                $this->showLifetimePhotos
                    ? 'heroicon-o-eye-slash'
                    : 'heroicon-o-eye'
            )
            ->color('success')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => 'toggleLifetimePhotos',
                'wire:loading.class' => 'opacity-50',
                'wire:target' => 'toggleLifetimePhotos',
            ]);

        return [
            $totalPhotosCard,
            $lifetimePhotosCard,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Current Photos
    |--------------------------------------------------------------------------
    */

    public function togglePhotos(): void
    {
        $this->showPhotos = ! $this->showPhotos;
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Lifetime Photos
    |--------------------------------------------------------------------------
    */

    public function toggleLifetimePhotos(): void
    {
        $this->showLifetimePhotos = ! $this->showLifetimePhotos;
    }

    public static function canView(): bool
    {
        return auth()->check()
            && ! auth()->user()->hasRole('Super Admin');
    }
}