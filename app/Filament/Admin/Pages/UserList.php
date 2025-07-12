<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\User;

class UserList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.user-list';
    protected static ?string $navigationLabel = 'User List';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?int $navigationSort = 2;
    public static function getNavigationSort(): ?int
    {
        return 2; // appear second in the Admin group
    }

    // public $totalManagers;
    // public $totalUsers;

    // public function mount(): void
    // {
    //     $queryManagers = User::where('role', 'manager');
    //     $queryUsers = User::where('role', 'user');

    //     if (auth()->user()?->hasRole('admin')) {
    //         $queryManagers->where('created_by', auth()->id());
    //         $queryUsers->where('created_by', auth()->id());
    //     }

    //     $this->totalManagers = User::where('role', 'manager')->count();
    //     $this->totalUsers = User::where('role', 'user')->count();
    // }

    protected function getViewData(): array
    {
        $queryManagers = User::query()->where('role', 'manager');
        $queryUsers = User::query()->where('role', 'user');

        if (auth()->user()?->hasRole('admin')) {
            $queryManagers->where('created_by', auth()->id());
            $queryUsers->where('created_by', auth()->id());
        }

        return [
            'totals' => [
                'managers' => $queryManagers->count(),
                'users' => $queryUsers->count(),
            ],
        ];
    }

    // public static function canAccess(): bool
    // {
    //     return auth()->check() && (
    //         auth()->user()->hasRole('admin') ||
    //         auth()->user()->hasRole('Super Admin')
    //     );
    // }
}
