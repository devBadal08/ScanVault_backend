<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PhotoDeleteHistoryResource\Pages;
use App\Filament\Admin\Resources\PhotoDeleteHistoryResource\RelationManagers;
use App\Models\PhotoDeleteHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotoDeleteHistoryResource extends Resource
{
    protected static ?string $model = PhotoDeleteHistory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'History';
    protected static ?string $navigationLabel = 'Photo Delete History';
    protected static ?int $navigationSort = 10;

     /**
     * Show only delete histories of users created by logged-in admin
     */

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.company_name')
                    ->label('Company'),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Deleted By'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),

                Tables\Columns\TextColumn::make('photo_path')
                    ->label('Photo Path')
                    ->limit(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Deleted At')
                    ->dateTime('M d, Y'),
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
            'index' => Pages\PhotoDeleteHistoryCompanies::route('/'), // ✅ THIS LINE FIX
            'company' => Pages\ListPhotoDeleteHistories::route('/{company}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'Super Admin';
    }
}
