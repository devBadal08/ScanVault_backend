<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;

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
    public ?int $remainingLimit = null;

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
        $currentUser = Filament::auth()->user();
        $adminId = $currentUser->id;

        $managerIds = []; // ✅ Define default empty array

        if ($currentUser?->hasRole('Super Admin')) {
            $this->users = User::where('role', 'user')->get();
        } else {
            $managerIds = User::where('created_by', $adminId)
                            ->where('role', 'manager')
                            ->pluck('id')
                            ->toArray();

            $this->users = User::where('role', 'user')
                ->where(function($query) use ($adminId, $managerIds) {
                    $query->where('created_by', $adminId)
                        ->orWhereIn('created_by', $managerIds);
                })
                ->get();
        }

        // ✅ $managerIds is always defined now
        $allCreatedUserIds = User::where(function ($query) use ($adminId, $managerIds) {
            $query->where('created_by', $adminId)
                ->orWhereIn('created_by', $managerIds);
        })
        ->whereIn('role', ['user', 'manager'])
        ->count();

        $this->totalUsers = $allCreatedUserIds;

        $maxLimit = $currentUser->max_limit;
        if (!is_null($maxLimit)) {
            $this->remainingLimit = max($maxLimit - $this->totalUsers, 0);
        } else {
            $this->remainingLimit = null;
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('email')->email()->required(),
            Forms\Components\TextInput::make('password')->password()
                ->revealable()
                ->minLength(6)
                ->maxLength(255)
                ->rule('regex:/^(?=.*[A-Za-z])(?=.*\d).+$/')
                ->helperText('Password must be at least 6 characters and contain both letters and numbers.'),
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
        $currentUser = Filament::auth()->user();

        if (!$this->editingUserId && !is_null($this->remainingLimit) && $this->remainingLimit <= 0) {
            Notification::make()
                ->title('User creation limit reached.')
                ->danger()
                ->send();

            return;
        }

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
                'created_by' => $currentUser->id,
                'assigned_to'=> $currentUser->id,
                'company_id' => $currentUser->company_id,
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
            'remainingLimit' => $this->remainingLimit,
            'totalUsers' => $this->totalUsers,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('manager') ||
            auth()->user()->hasRole('Super Admin')
        );
    }
}
