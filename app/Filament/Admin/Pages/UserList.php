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

    public $totalManagers;
    public $totalUsers;

    public function mount(): void
    {
        $this->totalManagers = User::where('role', 'manager')->count();
        $this->totalUsers = User::where('role', 'user')->count();
    }

    protected function getViewData(): array
    {
        return [
            'totals' => [
                'managers' => $this->totalManagers,
                'users' => $this->totalUsers,
            ]
        ];
    }
}
