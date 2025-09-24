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
    public $users;

    public $showFormPage = false;
    public $editingUserId = null;

    // Form fields
    public $name;
    public $email;
    public $password;
    public $role;
    public $max_limit;

    public function mount($record): void
    {
        $this->company = Company::findOrFail($record);
        $this->loadUsers();
        $this->refreshCounts();
    }

    public function loadUsers()
    {
        $this->users = $this->company->users()->get();
    }

    public function refreshCounts()
    {
        $this->totalAdmins = $this->company->users()->where('role', 'admin')->count();
        $this->totalManagers = $this->company->users()->where('role', 'manager')->count();
        $this->totalUsers = $this->company->users()->where('role', 'user')->count();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->required(fn () => !$this->editingUserId),

            Forms\Components\Select::make('role')
                ->label('Role')
                ->options([
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ])
                ->disabled(fn () => $this->editingUserId) // lock role on edit
                ->required(),

            Forms\Components\TextInput::make('max_limit')
                ->label('Max Limit')
                ->numeric()
                ->required(),
        ];
    }

    // --- ACTIONS ---

    public function createNewUserPage()
    {
        $this->editingUserId = null;
        $this->showFormPage = true;

        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'max_limit' => '',
        ]);
    }

    public function editUserPage($userId)
    {
        $this->editingUserId = $userId;
        $this->showFormPage = true;

        $user = User::findOrFail($userId);
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->role,
            'max_limit' => $user->max_limit,
        ]);
    }

    public function goBack()
    {
        $this->showFormPage = false;
        $this->editingUserId = null;
    }

    public function saveUser()
    {
        $data = $this->form->getState();

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
                'max_limit' => $data['max_limit'],
            ]);
        } else {
            $currentUser = auth()->user();
            $createdCount = User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;

            if (in_array($currentUser->role, ['admin', 'manager']) && $createdCount >= $maxLimit) {
                Notification::make()
                    ->title('Limit Reached')
                    ->body('You have reached your max user creation limit.')
                    ->danger()
                    ->send();
                return;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $this->company->id,
                'role' => $data['role'],
                'max_limit' => $data['max_limit'],
                'created_by' => auth()->id(),
            ]);
            $user->assignRole($data['role']);
        }

        $this->showFormPage = false;
        $this->editingUserId = null;
        $this->loadUsers();
        $this->refreshCounts();

        Notification::make()
            ->title($this->editingUserId ? 'User Updated' : 'User Created')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'company' => $this->company,
            'users' => $this->users,
            'totalAdmins' => $this->totalAdmins,
            'totalManagers' => $this->totalManagers,
            'totalUsers' => $this->totalUsers,
            'form' => $this->form,
            'showFormPage' => $this->showFormPage,
            'editingUserId' => $this->editingUserId,
        ];
    }
}
