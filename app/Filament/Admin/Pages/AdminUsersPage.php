<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminUsersPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.admin-users-page';

    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?int $navigationSort = 7;

    public $managers = [];
    public $adminUsers = [];

    public function mount(): void
    {
        $authUser = Auth::user();

        if ($authUser->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        // ✅ Managers created by this admin
        $this->managers = User::where('role', 'manager')
            ->where('created_by', $authUser->id)
            ->get();

        // ✅ Users directly created by this admin
        $this->adminUsers = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->get();
    }
}
