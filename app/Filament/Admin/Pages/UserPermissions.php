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
        'permissions.show_total_storage' => 'boolean',
        'permissions.show_total_photos' => 'boolean',
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

    public function selectUser($id)
    {
        // Reset UI first
        $this->reset('permissions');

        // Now set selected user
        $this->selectedUser = User::find($id);

        // Fetch previous permissions
        $record = UserPermission::where('company_id', $this->company->id)
                    ->where('user_id', $id)
                    ->first();

        // Load from DB or set fresh defaults
        $this->permissions = [
            'show_total_users'    => $record->show_total_users    ?? false,
            'show_total_managers' => $record->show_total_managers ?? false,
            'show_total_admins'   => $record->show_total_admins   ?? false,
            'show_total_limit'    => $record->show_total_limit    ?? false,
            'show_total_storage'  => $record->show_total_storage  ?? false,
            'show_total_photos'   => $record->show_total_photos   ?? false,
        ];
    }

    public function savePermissions()
    {
        UserPermission::updateOrCreate(
            [
                'company_id' => $this->company->id,
                'user_id' => $this->selectedUser->id,
            ],
            [
                'show_total_users'    => $this->permissions['show_total_users'] ?? false,
                'show_total_managers' => $this->permissions['show_total_managers'] ?? false,
                'show_total_admins'   => $this->permissions['show_total_admins'] ?? false,
                'show_total_limit'    => $this->permissions['show_total_limit'] ?? false,
                'show_total_storage'  => $this->permissions['show_total_storage'] ?? false,
                'show_total_photos'   => $this->permissions['show_total_photos'] ?? false,
            ]
        );

        $this->selectUser($this->selectedUser->id);

        Notification::make()
            ->title('Permissions updated successfully!')
            ->success()
            ->send();
    }
}
