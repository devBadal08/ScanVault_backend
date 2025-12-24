<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\User;
use Filament\Notifications\Notification;

class ManagerList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Manager List';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.manager-list';

    protected function getTableQuery()
    {
        $currentUser = auth()->user();

        // Super Admin → all managers
        if ($currentUser->hasRole('Super Admin')) {
            return User::where('role', 'manager');
        }

        // Admin → only managers created by them
        return User::where('role', 'manager')
            ->where('created_by', $currentUser->id);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->searchable(),

            Tables\Columns\TextColumn::make('email')
                ->searchable(),

            Tables\Columns\TextColumn::make('max_limit')
                ->label('Max Limit')
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->modalHeading('Edit Manager')
                ->modalSubmitActionLabel('Save')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('email')
                        ->email()
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('max_limit')
                        ->label('Max limit')
                        ->numeric()
                        ->required()
                        ->reactive()
                        ->disabled(function (User $record) {
                            $currentUser = auth()->user();

                            if (!$currentUser->hasRole('admin')) {
                                return false;
                            }

                            $adminMaxLimit = $currentUser->max_limit ?? 0;

                            $directUsersCount = User::where('created_by', $currentUser->id)->count();

                            $allManagersLimit = User::where('role', 'manager')
                                ->where('created_by', $currentUser->id)
                                ->sum('max_limit');

                            $remaining = $adminMaxLimit - ($directUsersCount + $allManagersLimit);

                            // ✅ Disable if admin has no remaining limit
                            return $remaining <= 0;
                        })

                        ->helperText(function (User $record) {
                            $currentUser = auth()->user();

                            if (!$currentUser->hasRole('admin')) {
                                return null;
                            }

                            $adminMaxLimit = $currentUser->max_limit ?? 0;

                            $directUsersCount = User::where('created_by', $currentUser->id)->count();

                            $allManagersLimit = User::where('role', 'manager')
                                ->where('created_by', $currentUser->id)
                                ->sum('max_limit');

                            $remaining = $adminMaxLimit - ($directUsersCount + $allManagersLimit);

                            if ($remaining <= 0) {
                                return 'Your total user limit is fully used. You cannot change manager limits.';
                            }

                            return "Remaining limit you can assign: {$remaining}";
                        }),

                    \Filament\Forms\Components\TextInput::make('password')
                        ->password()
                        ->label('New Password')
                        ->revealable()
                        ->helperText('Leave blank to keep current password')
                        ->dehydrateStateUsing(fn ($state) =>
                            filled($state) ? bcrypt($state) : null
                        )
                        ->dehydrated(fn ($state) => filled($state)),
                ])
                ->before(function (array $data, User $record) {

                    $currentUser = auth()->user();

                    // Only admins need this validation
                    if (!$currentUser->hasRole('admin')) {
                        return;
                    }

                    $adminMaxLimit = $currentUser->max_limit ?? 0;

                    // Total users admin created directly
                    $directUsersCount = User::where('created_by', $currentUser->id)->count();

                    // Total limits already assigned to OTHER managers
                    $otherManagersLimit = User::where('role', 'manager')
                        ->where('created_by', $currentUser->id)
                        ->where('id', '!=', $record->id)
                        ->sum('max_limit');

                    // New total usage after update
                    $newTotalUsage =
                        $directUsersCount +
                        $otherManagersLimit +
                        ($data['max_limit'] ?? 0);

                    if ($newTotalUsage > $adminMaxLimit) {
                        Notification::make()
                            ->title('Limit exceeded')
                            ->body(
                                "You can assign max {$adminMaxLimit} users in total. " .
                                "This change would exceed your limit."
                            )
                            ->danger()
                            ->send();

                        // ❌ stop save
                        abort(403);
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->title('Manager updated')
                        ->success()
                ),

            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->after(function () {
                    Notification::make()
                        ->title('Manager deleted')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return []; // ❌ no bulk delete (optional)
    }

    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('Super Admin')
        );
    }
}
