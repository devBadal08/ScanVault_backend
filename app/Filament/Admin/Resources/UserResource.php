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
use App\Models\Company;

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

            // direct + indirect users
            $directUserIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();
            $indirectCount = User::whereIn('created_by', $directUserIds)->count();
            $directCount = count($directUserIds);

            // ✅ manager limits already assigned
            $assignedLimitToManagers = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->sum('max_limit');

            // ✅ used = direct users + indirect users + assigned manager limits
            $used = $directCount + $indirectCount + $assignedLimitToManagers;

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
                    ->unique(ignoreRecord: true)
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

                Select::make('company_id')
                    ->label('Company')
                    ->options(Company::pluck('company_name', 'id'))
                    ->searchable()
                    ->visible(fn () => auth()->user()?->hasRole('Super Admin')) // only SA sees it
                    ->required(fn () => auth()->user()?->hasRole('Super Admin')),

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
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('admin')) {
            $directUserIds = User::where('created_by', $currentUser->id)->pluck('id')->toArray();
            $indirectCount = User::whereIn('created_by', $directUserIds)->count();

            // ✅ manager limits already assigned
            $assignedLimitToManagers = User::where('role', 'manager')
                ->where('created_by', $currentUser->id)
                ->sum('max_limit');

            $createdCount = count($directUserIds) + $indirectCount + $assignedLimitToManagers;
            $maxLimit = $currentUser->max_limit ?? 0;

            return $createdCount < $maxLimit; // ❌ disables button if limit reached
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

    // public static function afterCreate(User $user, array $data): void
    // {
    //     $currentUser = auth()->user();
    //     $user = $this->record; // just created user

    //     if ($user->role) {
    //         $user->syncRoles([$user->role]);
    //     }

    //     if ($currentUser->hasRole('Super Admin')) {
    //         $companyId = $this->data['company_id'] ?? null;
    //         if ($companyId) {
    //             $user->companies()->syncWithoutDetaching([$companyId]);
    //         }
    //     } else {
    //         // Admin or Manager → assign same companies as creator
    //         $companyIds = $currentUser->companies()->pluck('companies.id')->toArray();
    //         if (!empty($companyIds)) {
    //             $user->companies()->syncWithoutDetaching($companyIds);
    //         }
    //     }
    // }
}
