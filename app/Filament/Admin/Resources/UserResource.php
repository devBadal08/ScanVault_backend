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
                    ->required(),
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
        $currentUser = auth()->user();

        // Only enforce for admin (skip Super Admin or adapt if needed)
        if ($currentUser && $currentUser->hasRole('admin')) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
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
        $data['company_id'] = $currentUser->company_id ?? null; // optional if you have it
        // Hash the password manually
        $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);

        return $data;
    }

    public static function canCreate(): bool
    {
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('admin')) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;
            return $createdCount < $maxLimit;
        }

        return true;
    }

    // public static function canViewAny(): bool
    // {
    //     return auth()->check() && (
    //         auth()->user()->hasRole('admin') ||
    //         auth()->user()->hasRole('Super Admin')
    //     );
    // }
}
