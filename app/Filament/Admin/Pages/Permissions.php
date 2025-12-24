<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;

class Permissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.permissions';
    protected static ?string $navigationLabel = 'Permissions';
    protected static ?string $navigationGroup = 'Permission Management';
    protected static ?int $navigationSort = 6;

     public $companies;

    public function mount()
    {
        $this->companies = Company::all();
    }

    protected function getViewData(): array
    {
        return [
            'companies' => $this->companies,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }

}