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

    public static function shouldRegisterNavigation(): bool
    {
        return true; // ensures it appears in the menu
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        if ($user->hasRole('Super Admin')) {
            //Super Admin can see everything
            $totalManagers = User::where('role', 'manager')->count();
            $totalUsers = User::where('role', 'user')->count();
        } else {
            //Admin can only see their own + their managers' users
            $adminId = $user->id;

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $adminId)
                ->pluck('id');

            $totalManagers = $managerIds->count();

            $totalUsers = User::where('role', 'user')
                ->where(function ($query) use ($adminId, $managerIds) {
                    $query->where('created_by', $adminId)
                        ->orWhereIn('created_by', $managerIds);
                })->count();
        }

        return [
            'totals' => [
                'managers' => $totalManagers,
                'users' => $totalUsers,
            ],
        ];
    }

     public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('Super Admin')
        );
    }
}
