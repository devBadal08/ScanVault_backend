<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateUser extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Managers';
    protected static ?string $navigationLabel = 'Create User';
    protected static string $view = 'filament.admin.pages.create-user';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('Super Admin'));
    }

    public $name;
    public $email;
    public $password;
    public $role;

    public function mount(): void
    {
        $this->form->fill([]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique('users', 'email'),

            Forms\Components\TextInput::make('password')
                ->password()
                ->required()
                ->minLength(6),

            Forms\Components\Select::make('role')
                ->options([
                    'user' => 'User',
                ])
                ->required(),
        ];
    }

    public function create()
    {
        // Filament automatically validates based on your getFormSchema
        $data = $this->form->getState();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'created_by' => auth()->id(),
        ]);

        $user->assignRole($data['role']);

        $this->form->fill([]); // reset the form

        Notification::make()
            ->title('User Created')
            ->body('The user has been created successfully.')
            ->success()
            ->send();
    }
}
