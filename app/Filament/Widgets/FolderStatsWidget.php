<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Folder;
use App\Models\User;

class FolderStatsWidget extends StatsOverviewWidget
{
    public bool $showFolders = false;

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
        | HARD PERMISSION GATE
        |--------------------------------------------------------------------------
        |
        | Super Admin must give "total_folders" permission.
        | Without permission, card will not be displayed.
        |
        */

        if (! $authUser->canShow('total_folders')) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Eye OFF
        |--------------------------------------------------------------------------
        |
        | Don't perform the folder count until user clicks eye.
        |
        */

        if (! $this->showFolders) {

            return [
                Card::make('Total Folders', '•••')
                    ->description('Click eye icon to reveal')
                    ->descriptionIcon('heroicon-o-eye')
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                        'wire:click' => 'toggleFolders',
                        'wire:loading.class' => 'opacity-50',
                        'wire:target' => 'toggleFolders',
                    ]),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Get company IDs
        |--------------------------------------------------------------------------
        */

        $companyIds = $authUser->companies()
            ->pluck('companies.id');

        /*
        |--------------------------------------------------------------------------
        | MANAGER
        |--------------------------------------------------------------------------
        |
        | Manager sees folders of users created by this manager.
        |
        */

        if ($authUser->role === 'manager') {

            $userIds = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->pluck('id');
        }

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        |
        | Admin sees:
        |
        | 1. Users created directly by Admin
        | 2. Users created by Admin's Managers
        |
        */

        else {

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $userIds = User::where('role', 'user')
                ->where(function ($query) use ($authUser, $managerIds) {

                    $query->where('created_by', $authUser->id)
                        ->orWhereIn('created_by', $managerIds);

                })
                ->pluck('id');
        }

        /*
        |--------------------------------------------------------------------------
        | Count folders
        |--------------------------------------------------------------------------
        */

        $folderCount = $authUser->companies()->sum('total_folders');

        /*
        |--------------------------------------------------------------------------
        | Return Card
        |--------------------------------------------------------------------------
        */

        return [
            Card::make(
                'Total Folders',
                number_format($folderCount)
            )
                ->description('Folders created by your users')
                ->descriptionIcon('heroicon-o-eye-slash')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'toggleFolders',
                    'wire:loading.class' => 'opacity-50',
                    'wire:target' => 'toggleFolders',
                ]),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle visibility
    |--------------------------------------------------------------------------
    */

    public function toggleFolders(): void
    {
        $this->showFolders = ! $this->showFolders;
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