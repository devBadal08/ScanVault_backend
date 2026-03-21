<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Photo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserPhotoStats extends StatsOverviewWidget
{
    public bool $showPhotos = false;

    protected function getStats(): array
    {
        $user = Auth::user();

        // Eye OFF → masked
        if (! $this->showPhotos) {
            return [
                Card::make('Total Photos', '•••')
                    ->description('Click eye icon to reveal')
                    ->descriptionIcon('heroicon-o-eye')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'toggleMyPhotos',
                        'wire:loading.class' => 'opacity-50',
                        'wire:target' => 'toggleMyPhotos',
                    ]),
            ];
        }

        // Cache key per user
        $cacheKey = 'user_photo_count_' . $user->id;

        // Cache for 5 minutes
        $count = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return Photo::where('user_id', $user->id)->count();
        });

        return [
            Card::make(
                'Total Photos',
                number_format($count)
            )
            ->description('Photos uploaded by you')
            ->descriptionIcon('heroicon-o-eye-slash')
            ->color('info')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => 'toggleMyPhotos',
                'wire:loading.class' => 'opacity-50',
                'wire:target' => 'toggleMyPhotos',
            ]),
        ];
    }

    public function toggleMyPhotos(): void
    {
        $this->showPhotos = ! $this->showPhotos;
    }

    public static function canView(): bool
    {
        return Auth::user()?->role === 'user';
    }
}