<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;

class UserList extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.user-list';
    protected static ?string $navigationLabel = 'User List';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        if ($user->hasRole('manager')) {
            return 'Managers';
        }

        return 'Admin';
    }

    public ?User $editingUser = null;
    public bool $showEditModal = false;

    public function openEdit(int $userId): void
    {
        $this->editingUser = User::findOrFail($userId);
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->editingUser->save();

        $this->showEditModal = false;

        Notification::make()
            ->title('User updated')
            ->success()
            ->send();
    }

    public function deleteUser(int $userId)
    {
        $user = User::findOrFail($userId);

        $user->delete();

        Notification::make()
            ->title('User deleted')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $currentUser = auth()->user();

        // ✅ Super Admin → all users
        if ($currentUser->hasRole('Super Admin')) {
            $users = User::where('role', 'user')->get();
        }

        // ✅ Admin → users created by admin + by their managers
        elseif ($currentUser->hasRole('admin')) {

            $adminId = $currentUser->id;

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $adminId)
                ->pluck('id');

            $users = User::where('role', 'user')
                ->where(function ($q) use ($adminId, $managerIds) {
                    $q->where('created_by', $adminId)
                    ->orWhereIn('created_by', $managerIds);
                })
                ->get();
        }

        // ✅ Manager → ONLY users created by that manager
        else {
            $users = User::where('role', 'user')
                ->where('created_by', $currentUser->id)
                ->get();
        }

        return [
            'users' => $users,
        ];
    }

    protected function getTableQuery()
    {
        $currentUser = auth()->user()->fresh();

        if ($currentUser->hasRole('Super Admin')) {
            return User::where('role', 'user');
        }

        if ($currentUser->hasRole('admin')) {
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->pluck('id');

            return User::where('role', 'user')
                ->where(function ($q) use ($currentUser, $managerIds) {
                    $q->where('created_by', $currentUser->id)
                    ->orWhereIn('created_by', $managerIds);
                });
        }

        // manager
        return User::where('role', 'user')
            ->where('created_by', $currentUser->id);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->searchable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime('M d, Y'),

            Tables\Columns\TextColumn::make('createdBy.name')
                ->label('Created By')
                ->sortable()
                ->searchable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->modalHeading('Edit User')
                ->form([
                    TextInput::make('name')
                        ->required(),

                    TextInput::make('email')
                        ->email()
                        ->required(),

                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) =>
                            filled($state) ? bcrypt($state) : null
                        )
                        ->dehydrated(fn ($state) => filled($state))
                        ->label('New Password')
                        ->helperText('Leave blank to keep current password'),
                ])
                ->successNotification(
                    Notification::make()
                        ->title('User updated')
                        ->success()
                ),

            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->successNotification(
                    Notification::make()
                        ->title('User deleted')
                        ->success()
                ),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('manager') ||
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('Super Admin')
        );
    }
}
