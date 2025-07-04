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

    // ✅ public properties for form binding
    public $name;
    public $email;
    public $password;
    public $role;
    public $max_limit;

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
                ->required(),

            Forms\Components\Select::make('role')
                ->label('Role')
                ->options([
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ])
                ->required(),

            Forms\Components\TextInput::make('max_limit')
                ->label('Max Limit')
                ->numeric()
                ->required(),
        ];
    }

    public function createUser()
    {
        $data = $this->form->getState();

        $currentUser = auth()->user();
        $createdCount = User::where('created_by', $currentUser->id)->count();

        // ✅ Only apply limit for admin or manager
        if (in_array($currentUser->role, ['admin', 'manager'])) {
            if ($createdCount >= $currentUser->max_limit) {
                Notification::make()
                    ->title('Limit Reached')
                    ->body('You have reached your max user creation limit.')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Laravel validate can be used if needed
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'max_limit' => 'required|integer|min:1',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company_id' => $this->company->id,
            'role' => $data['role'],
            'max_limit' => $data['max_limit'],
            'created_by' => $currentUser->id,
        ]);

        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'max_limit' => '',
        ]);
        $this->refreshCounts();

        Notification::make()
        ->title('User Created')
        ->body('The user has been created successfully.')
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
        ];
    }
}
