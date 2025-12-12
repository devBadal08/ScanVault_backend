<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CompanyUserResource\Pages;
use App\Filament\Admin\Resources\CompanyUserResource\RelationManagers;
use App\Models\CompanyUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Company;

class CompanyUserResource extends Resource
{
    protected static ?string $model = CompanyUser::class;

    protected static ?string $navigationGroup = 'User Company Access';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Assign User to Company';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(User::where('role', 'user')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::pluck('company_name', 'id'))
                    ->required()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('company.company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime('M d, Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCompanyUsers::route('/'),
            'create' => Pages\CreateCompanyUser::route('/create'),
            'edit' => Pages\EditCompanyUser::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Super Admin');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('user', function ($query) {
                $query->where('role', 'user');
            });
    }
}
