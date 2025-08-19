<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ManagerUsersPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.manager-users-page';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Manager Users';
    protected static ?int $navigationSort = 8;

    public $managerUsers = [];

    public function mount(): void
    {
        $authUser = Auth::user();

        if (!in_array($authUser->role, ['manager', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        // ✅ Users created by this manager
        $this->managerUsers = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->get();
    }
}
