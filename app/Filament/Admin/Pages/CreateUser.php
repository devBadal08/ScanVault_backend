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

    public $remainingSlots = null; // public for blade

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('Super Admin'));
    }

    public function mount(): void
    {
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('manager')) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;
            $this->remainingSlots = max($maxLimit - $createdCount, 0);
        }

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
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('manager')) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;

            if ($createdCount >= $maxLimit) {
                Notification::make()
                    ->title('Limit Reached')
                    ->body('You have reached your maximum user creation limit.')
                    ->danger()
                    ->send();
                return;
            }

            if (($createdCount / $maxLimit) * 100 >= 90) {
                Notification::make()
                    ->title('Almost at Limit')
                    ->body("You've used 90% of your user creation limit. Slots left: " . ($maxLimit - $createdCount))
                    ->warning()
                    ->send();
            }
        }

        $data = $this->form->getState();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'created_by' => $currentUser->id,
        ]);

        $user->assignRole($data['role']);

        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => ''
        ]);

        Notification::make()
            ->title('User Created')
            ->body('The user has been created successfully.')
            ->success()
            ->send();

        // Refresh remaining
        $this->mount();
    }

    protected function getViewData(): array
    {
        return [
            'remainingSlots' => $this->remainingSlots,
        ];
    }
}
