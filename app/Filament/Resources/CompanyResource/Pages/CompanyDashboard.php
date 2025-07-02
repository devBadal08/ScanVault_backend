<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CompanyDashboard extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = CompanyResource::class;
    protected static string $view = 'filament.resources.company-resource.pages.company-dashboard';

    public $company;
    public $totalAdmins;
    public $totalManagers;
    public $totalUsers;

    // ✅ public properties for form binding
    public $name;
    public $email;
    public $password;
    public $role;

    public function mount($record): void
    {
        $this->company = Company::findOrFail($record);
        $this->refreshCounts();
        $this->form->fill([]); // clear form state
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->statePath('name'),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->statePath('email'),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->statePath('password'),

            Forms\Components\Select::make('role')
                ->label('Role')
                ->options([
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ])
                ->required()
                ->statePath('role'),
        ];
    }

    public function createUser()
    {
        $this->form->validate(); // will validate based on above schema

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'company_id' => $this->company->id,
            'role' => $this->role,
        ]);

        $this->reset(['name', 'email', 'password', 'role']);
        $this->refreshCounts();

        session()->flash('success', 'User created successfully!');
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
