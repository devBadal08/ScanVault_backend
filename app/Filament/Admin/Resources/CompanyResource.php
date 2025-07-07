<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Navigation\NavigationItem;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Create Companies';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('company_name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Company Name'),
                TextInput::make('admin_name')
                    ->required()
                    ->label('Admin Name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->label('Company Name')->sortable()->searchable(),
                TextColumn::make('admin_name')->label('Admin Name')->sortable()->searchable(),
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

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Create Companies')
                ->url(static::getUrl('create'))
                ->icon('heroicon-o-plus-circle')
                ->group('Administration'),

            NavigationItem::make('Company List')
                ->url(static::getUrl('company-list'))
                ->icon('heroicon-o-document')
                ->group('Administration'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
            'company-list' => Pages\CompanyList::route('/company-list'),
            'dashboard' => Pages\CompanyDashboard::route('/{record}/dashboard'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }
}
