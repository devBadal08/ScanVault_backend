<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Models\Company;
use App\Models\User;

class CompanyDashboard extends Page
{
    protected static string $resource = CompanyResource::class;
    protected static string $view = 'filament.admin.resources.company-resource.pages.company-dashboard';

    public $company;
    public $totalAdmins;
    public $totalManagers;
    public $totalUsers;

    public $name;
    public $email;
    public $password;
    public $role;
    public $max_limit;

    public bool $showForm = false;
    public ?int $editingUserId = null;

    public function mount($record): void
    {
        $this->company = Company::findOrFail($record);
        $this->refreshCounts();

        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'max_limit' => '',
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->label('Name')->required(),
            Forms\Components\TextInput::make('email')->label('Email')->email()->required(),
            Forms\Components\TextInput::make('password')->label('Password')->password(),
            Forms\Components\Select::make('role')
                ->label('Role')
                ->options([
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ])
                ->required(),
            Forms\Components\TextInput::make('max_limit')->label('Max Limit')->numeric()->required(),
        ];
    }

    public function startCreatingUser()
    {
        $this->editingUserId = null;
        $this->showForm = true;

        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'max_limit' => '',
        ]);
    }

    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $userId;
        $this->showForm = true;

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->role,
            'max_limit' => $user->max_limit,
        ]);
    }

    public function createOrUpdateUser()
    {
        $data = $this->form->getState();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'role' => 'required|string',
            'max_limit' => 'required|integer|min:1',
        ]);

        $currentUser = auth()->user();
        if (in_array($currentUser->role, ['admin', 'manager'])) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
            if (is_null($this->editingUserId) && $createdCount >= $currentUser->max_limit) {
                Notification::make()
                    ->title('Limit Reached')
                    ->body('You have reached your max user creation limit.')
                    ->danger()
                    ->send();
                return;
            }
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'max_limit' => $data['max_limit'],
                'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
            ]);
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $this->company->id,
                'role' => $data['role'],
                'max_limit' => $data['max_limit'],
                'created_by' => $currentUser->id,
            ]);
        }

        $user->syncRoles([$data['role']]);
        $this->form->fill([]);
        $this->editingUserId = null;
        $this->showForm = false;

        $this->refreshCounts();

        Notification::make()
            ->title('Saved')
            ->body('The user has been saved successfully.')
            ->success()
            ->send();
    }

    protected function refreshCounts()
    {
        $this->totalAdmins = $this->company->users()->where('role', 'admin')->count();
        $this->totalManagers = $this->company->users()->where('role', 'manager')->count();
        $this->totalUsers = $this->company->users()->where('role', 'user')->count();
    }

    protected function getViewData(): array
    {
        return [
            'company' => $this->company,
            'form' => $this->form,
            'totalAdmins' => $this->totalAdmins,
            'totalManagers' => $this->totalManagers,
            'totalUsers' => $this->totalUsers,
            'showForm' => $this->showForm,
        ];
    }
}
