<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateOrEditUser extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Managers';
    protected static ?string $navigationLabel = 'Create Users';
    protected static string $view = 'filament.admin.pages.create-or-edit-user';
    protected static ?int $navigationSort = 3;

    public int $totalUsers = 0;
    public ?int $remainingLimit = null;

    public $name;
    public $email;
    public $password;
    public $role;

    public bool $limitReached = false;

    public function mount(): void
    {
        $this->calculateLimit();

        $this->form->fill([]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->rule(
                    Rule::unique('users', 'email')
                )
                ->validationMessages([
                    'unique' => 'This email already exists. Please enter a different email.',
                ])
                ->live(onBlur: true),
            Forms\Components\TextInput::make('password')->password()
                ->revealable()
                ->minLength(6)
                ->maxLength(255)
                ->rule('regex:/^(?=.*[A-Za-z])(?=.*\d).+$/')
                ->helperText('Password must be at least 6 characters and contain both letters and numbers.'),
        ];
    }

    public function saveUser()
    {

        if ($this->limitReached) {
            Notification::make()
                ->title('User limit reached')
                ->body('Your user creation limit is reached. Please contact admin.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered. Please use another email.',
            ]);
        }

        $currentUser = Filament::auth()->user();

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

        $companyIds = $currentUser->companies()->pluck('companies.id')->toArray();
        if (!empty($companyIds)) {
            $user->companies()->syncWithoutDetaching($companyIds);
        }

        Notification::make()
            ->title('User Created')
            ->success()
            ->send();

        // ✅ FULL reset (this fixes your issue)
        $this->resetExcept('remainingLimit', 'limitReached');
        $this->form->fill([]);
        $this->reset(['name', 'email', 'password']);
        $this->resetErrorBag();
        $this->resetValidation();

        // Recalculate limit
        $this->calculateLimit();

        // Re-render
        $this->dispatch('$refresh');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['manager']);
    }

    protected function calculateLimit(): void
    {
        $currentUser = Filament::auth()->user();

        $createdUsersCount = User::where('created_by', $currentUser->id)
            ->where('role', 'user')
            ->count();

        $maxLimit = $currentUser->max_limit;

        if (!is_null($maxLimit)) {
            $this->remainingLimit = max($maxLimit - $createdUsersCount, 0);
            $this->limitReached = $this->remainingLimit <= 0;
        } else {
            $this->remainingLimit = null;
            $this->limitReached = false;
        }
    }
}
