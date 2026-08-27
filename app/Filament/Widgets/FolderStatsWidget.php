<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class FolderStatsWidget extends StatsOverviewWidget
{
    public bool $showFolders = false;
    public bool $showLifetimeFolders = false;

    protected function getStats(): array
    {
        $authUser = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | User Check
        |--------------------------------------------------------------------------
        */

        if (! $authUser) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Only Admin and Manager
        |--------------------------------------------------------------------------
        */

        if (! in_array($authUser->role, ['admin', 'manager'])) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Permission
        |--------------------------------------------------------------------------
        */

        if (! $authUser->canShow('total_folders')) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Current Folders
        |--------------------------------------------------------------------------
        */

        $folderCount = $authUser->companies()
            ->sum('total_folders');

        /*
        |--------------------------------------------------------------------------
        | Lifetime Folders
        |--------------------------------------------------------------------------
        */

        $lifetimeFolderCount = $authUser->companies()
            ->sum('lifetime_total_folders');

        /*
        |--------------------------------------------------------------------------
        | Total Folders Card
        |--------------------------------------------------------------------------
        */

        $totalFoldersCard = Card::make(
            'Total Folders',
            $this->showFolders
                ? number_format($folderCount)
                : '•••'
        )
            ->description(
                $this->showFolders
                    ? 'Folders currently available'
                    : 'Click eye icon to reveal'
            )
            ->descriptionIcon(
                $this->showFolders
                    ? 'heroicon-o-eye-slash'
                    : 'heroicon-o-eye'
            )
            ->color('success')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => 'toggleFolders',
                'wire:loading.class' => 'opacity-50',
                'wire:target' => 'toggleFolders',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Lifetime Total Folders Card
        |--------------------------------------------------------------------------
        */

        $lifetimeFoldersCard = Card::make(
            'Lifetime Total Folders',
            $this->showLifetimeFolders
                ? number_format($lifetimeFolderCount)
                : '•••'
        )
            ->description(
                $this->showLifetimeFolders
                    ? 'Total folders ever created'
                    : 'Click eye icon to reveal'
            )
            ->descriptionIcon(
                $this->showLifetimeFolders
                    ? 'heroicon-o-eye-slash'
                    : 'heroicon-o-eye'
            )
            ->color('info')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => 'toggleLifetimeFolders',
                'wire:loading.class' => 'opacity-50',
                'wire:target' => 'toggleLifetimeFolders',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Return Cards
        |--------------------------------------------------------------------------
        */

        return [
            $totalFoldersCard,
            $lifetimeFoldersCard,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Current Folders
    |--------------------------------------------------------------------------
    */

    public function toggleFolders(): void
    {
        $this->showFolders = ! $this->showFolders;
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Lifetime Folders
    |--------------------------------------------------------------------------
    */

    public function toggleLifetimeFolders(): void
    {
        $this->showLifetimeFolders = ! $this->showLifetimeFolders;
    }

    /*
    |--------------------------------------------------------------------------
    | Only Admin + Manager
    |--------------------------------------------------------------------------
    */

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && in_array($user->role, ['admin', 'manager'])
            && ! $user->hasRole('Super Admin');
    }
}