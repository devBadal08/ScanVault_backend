<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.manager-dashboard';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'manager';
    }
}
