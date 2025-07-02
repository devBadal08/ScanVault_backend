<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $pluralLabel = 'Users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;

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
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->confirmed()
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state)),
                TextInput::make('password_confirmation')
                    ->password()
                    ->required(),

                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->required()
                    ->label('Roles'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                BadgeColumn::make('roles.name')
                    ->label('Roles')
                    ->colors([
                        'primary',
                        'success' => 'Admin',
                        'warning' => 'Super Admin',
                    ])
                    ->separator(', '),
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
    
    //Restrict everything to Super Admin
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }
}
