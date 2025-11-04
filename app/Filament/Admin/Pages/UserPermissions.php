<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\User;
use App\Models\UserPermission;
use Filament\Notifications\Notification;

class UserPermissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.admin.pages.user-permissions';

    public $company;
    public $users;
    public $selectedUser;
    public $permissions = [];

    protected $rules = [
        'permissions.show_total_users' => 'boolean',
        'permissions.show_total_managers' => 'boolean',
        'permissions.show_total_admins' => 'boolean',
        'permissions.show_total_limit' => 'boolean',
        'permissions.show_total_storage' => 'boolean', // ✅ new rule
    ];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount()
    {
        $companyId = request()->query('company');
        if ($companyId) {
            $this->company = Company::findOrFail($companyId);
            $this->users = User::where('company_id', $companyId)
                ->whereIn('role', ['admin', 'manager'])
                ->get();
        }
    }

    public function selectUser($userId)
    {
        $this->selectedUser = User::findOrFail($userId);
        $permissionModel = UserPermission::firstOrCreate([
            'company_id' => $this->company->id,
            'user_id' => $userId
        ]);

        $this->permissions = [
            'show_total_users' => $permissionModel->show_total_users,
            'show_total_managers' => $permissionModel->show_total_managers,
            'show_total_admins' => $permissionModel->show_total_admins,
            'show_total_limit' => $permissionModel->show_total_limit,
            'show_total_storage' => $permissionModel->show_total_storage, // ✅ load value
        ];
    }

    public function savePermissions()
    {
        if ($this->selectedUser) {
            UserPermission::updateOrCreate(
                [
                    'company_id' => $this->company->id,
                    'user_id' => $this->selectedUser->id
                ],
                [
                    'show_total_users' => (bool)($this->permissions['show_total_users'] ?? false),
                    'show_total_managers' => (bool)($this->permissions['show_total_managers'] ?? false),
                    'show_total_admins' => (bool)($this->permissions['show_total_admins'] ?? false),
                    'show_total_limit' => (bool)($this->permissions['show_total_limit'] ?? false),
                    'show_total_storage' => (bool)($this->permissions['show_total_storage'] ?? false), // ✅ save new
                ]
            );

            Notification::make()
                ->title('Permissions updated successfully!')
                ->success()
                ->send();
        }
    }
}
