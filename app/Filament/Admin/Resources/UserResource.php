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
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use App\Models\Company;
use Illuminate\Validation\Rule;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Create Users';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?int $navigationSort = 1;

    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }

    public static function form(Form $form): Form
    {
        $currentUser = auth()->user()->fresh();
        $adminMaxLimit = null;
        $availableLimit = null;

        if ($currentUser && $currentUser->hasRole('admin')) {
            $adminMaxLimit = $currentUser->max_limit ?? 0;

            // 1. Users created directly by admin
            $adminUserCount = User::where('created_by', $currentUser->id)
                ->where('role', 'user')
                ->count();

            // 2. Managers created by admin
            $managerIds = User::where('created_by', $currentUser->id)
                ->where('role', 'manager')
                ->pluck('id');

            // 3. Users created by managers
            $managerUserCount = $managerIds->isEmpty()
                ? 0
                : User::whereIn('created_by', $managerIds)
                    ->where('role', 'user')
                    ->count();

            // 4. Manager max limits already allocated
            $assignedLimitToManagers = User::where('created_by', $currentUser->id)
                ->where('role', 'manager')
                ->sum('max_limit');

            // 5. Total used
            $used = $adminUserCount
                + $managerUserCount
                + $assignedLimitToManagers;

            $availableLimit = max($adminMaxLimit - $used, 0);
        }

        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(table: User::class, column: 'email', ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'This email already exists. Please enter a different email.',
                    ])
                    ->maxLength(255),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context) => $context === 'create')
                    ->minLength(6)
                    ->maxLength(255)
                    ->rule('regex:/^(?=.*[A-Za-z])(?=.*\d).+$/')
                    ->helperText('Password must be at least 6 characters and contain both letters and numbers.'),

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
            'index' => \App\Filament\Admin\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Admin\Resources\UserResource\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Admin\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('Super Admin')
        );
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('admin')
        );
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = auth()->user();

        // Who created
        $data['created_by'] = $currentUser->id;
        $data['assigned_to'] = $currentUser->id;

        // Super Admin needs to select company manually
        if ($currentUser->hasRole('Super Admin')) {
            if (empty($data['company_id'])) {
                throw ValidationException::withMessages([
                    'company_id' => ['Please select a company.'],
                ]);
            }
        } else {
            // Prevent write to users table
            unset($data['company_id']);
        }

        // Hash password
        $data['password'] = \Hash::make($data['password']);

        return $data;
    }
}
