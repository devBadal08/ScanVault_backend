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

    protected function getViewData(): array
    {
        $user = auth()->user();

        if ($user->hasRole('Super Admin')) {

            $managers = User::where('role', 'manager')->get();
            $users = User::where('role', 'user')->get();

        } else {

            $adminId = $user->id;

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $adminId)
                ->pluck('id');

            $managers = User::whereIn('id', $managerIds)->get();

            $users = User::where('role', 'user')
                ->where(function ($q) use ($adminId, $managerIds) {
                    $q->where('created_by', $adminId)
                    ->orWhereIn('created_by', $managerIds);
                })
                ->get();
        }

        return [
            'managers' => $managers,
            'users' => $users,
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
