<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DeletePermissionResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DeletePermissionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Delete Permissions';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Manager Controls';
    protected static ?int $navigationSort = 11;

    /**
     * Show only managers created by logged-in admin
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'manager')
            ->where('created_by', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Manager Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                ToggleColumn::make('can_delete_photos')
                    ->label('Allow Photo Delete')
                    ->onColor('success')
                    ->offColor('danger'),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeletePermissions::route('/'),
        ];
    }

    /**
     * Only admin can access
     */
    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin';
    }
}