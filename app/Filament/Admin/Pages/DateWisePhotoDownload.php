<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class DateWisePhotoDownload extends Page
{
    protected static string $view = 'filament.admin.pages.date-wise-photo-download';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Backup & Delete';

    protected static ?int $navigationSort = 12;

    private const BACKUP_PASSWORD = 'Tech@strota2026';

    public static function getNavigationLabel(): string
    {
        return 'Backup Photos';
    }

    public function getTitle(): string
    {
        return 'Backup All Photos';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public bool $passwordVerified = false;

    public string $backupPassword = '';

    public ?string $passwordError = null;

    public function verifyPassword(): void
    {
        $this->passwordError = null;

        if ($this->backupPassword !== self::BACKUP_PASSWORD) {

            $this->passwordError = 'Incorrect password.';

            $this->backupPassword = '';

            return;
        }

        $this->passwordVerified = true;

        $this->backupPassword = '';
    }
}