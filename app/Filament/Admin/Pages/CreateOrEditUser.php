<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateOrEditUser extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Managers';
    protected static ?string $navigationLabel = 'Manage Users';
    protected static string $view = 'filament.admin.pages.create-or-edit-user';
    protected static ?int $navigationSort = 3;

    public bool $showFormPage = false;
    public ?int $editingUserId = null;
    public $users;
    public int $totalUsers = 0;

    public $name;
    public $email;
    public $password;
    public $role;

    public function mount(): void
    {
        $this->loadUsers();
        $this->form->fill([]);
    }

    protected function loadUsers()
    {
        $currentUser = auth()->user();
        if ($currentUser?->hasRole('manager')) {
            $this->users = User::where('role', 'user')
                ->where('created_by', $currentUser->id)
                ->get();
        } elseif ($currentUser?->hasRole('Super Admin')) {
            $this->users = User::where('role', 'user')->get();
        } else {
            $this->users = collect();
        }
        $this->totalUsers = $this->users->count();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('email')->email()->required(),
            Forms\Components\TextInput::make('password')->password()->nullable()->minLength(6),
            Forms\Components\Select::make('role')
                ->options([
                    'user' => 'User',
                ])
                ->required(),
        ];
    }

    public function createNewUserPage()
    {
        $this->showFormPage = true;
        $this->editingUserId = null;
        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
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
        ]);
    }

    public function goBack()
    {
        $this->showFormPage = false;
        $this->editingUserId = null;
        $this->loadUsers();
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
            ]);
            Notification::make()->title('User Updated')->success()->send();
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'created_by' => auth()->id(),
            ]);
            $user->assignRole('user');
            Notification::make()->title('User Created')->success()->send();
        }

        $this->goBack();
    }

    protected function getViewData(): array
    {
        return [
            'users' => $this->users,
            'showFormPage' => $this->showFormPage,
            'editingUserId' => $this->editingUserId,
            'form' => $this->form,
        ];
    }
}
