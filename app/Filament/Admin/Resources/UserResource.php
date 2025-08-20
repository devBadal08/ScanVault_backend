<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Create Users';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?int $navigationSort = 1;
    // public static function getNavigationSort(): ?int
    // {
    //     return 1; // appear first in the Admin group
    // }

    public static function form(Form $form): Form
    {
        $currentUser = auth()->user();
        $adminMaxLimit = null;
        $availableLimit = null;

        if ($currentUser && $currentUser->hasRole('admin')) {
            $adminMaxLimit = $currentUser->max_limit ?? 0;

            $totalCreatedUsers = User::where('created_by', $currentUser->id)
                                    ->count();

            $availableLimit = max($adminMaxLimit - $totalCreatedUsers, 0);
        }

        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context) => $context === 'create')
                    ->maxLength(255),

                Select::make('role')
                    ->label('Role')
                    ->options(fn () => 
                        collect([
                            'manager' => 'Manager',
                            'user' => 'User',
                        ])
                    )
                    ->searchable()
                    ->disabled(fn (string $context) => $context === 'edit')
                    ->required()
                    ->reactive(),

                TextInput::make('max_limit')
                    ->label('Max Limit')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue($availableLimit)
                    ->helperText(
                        ($currentUser && $currentUser->hasRole('admin'))
                            ? "Total Max Limit: {$adminMaxLimit} | Available Max Limit: {$availableLimit}"
                            : null
                    )
                    ->disabled(fn ($get) => $get('role') === 'user'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = \Filament\Facades\Filament::auth()->user();

        // Only enforce for admin (skip Super Admin or adapt if needed)
        if ($currentUser && $currentUser->hasRole('admin')) {
            // Get IDs of users created by this admin (level 1)
            $directUserIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();

            // Count users created by those users (level 2, e.g., managers)
            $indirectCount = User::whereIn('created_by', $directUserIds)->count();

            // Count direct users (managers + users)
            $directCount = count($directUserIds);

            // Total users created (direct + indirect)
            $createdCount = $directCount + $indirectCount;
            $maxLimit = $currentUser->max_limit ?? 0;

            if ($createdCount >= $maxLimit) {
                // Use Filament's Notification
                \Filament\Notifications\Notification::make()
                    ->title('Limit Reached')
                    ->body('You have reached your maximum user creation limit.')
                    ->danger()
                    ->send();

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => ['You have reached your maximum user creation limit.'],
                ]);
            }
        }

        // Automatically attach who created this user
        $data['created_by'] = $currentUser->id;
        $data['assigned_to'] = $currentUser->id;
        $data['company_id'] = $currentUser->company_id ?? null; // optional if you have it
        // Hash the password manually
        $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);

        return $data;
    }

    public static function canCreate(): bool
    {
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('admin')) {
            $directUserIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();
            $indirectCount = User::whereIn('created_by', $directUserIds)->count();
            $createdCount = count($directUserIds) + $indirectCount;
            $maxLimit = $currentUser->max_limit ?? 0;
            return $createdCount < $maxLimit;
        }

        return true;
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('Super Admin')
        );
    }
}
