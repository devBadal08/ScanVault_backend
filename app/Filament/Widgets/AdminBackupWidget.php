<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use App\Jobs\GenerateCompanyBackup;

class AdminBackupWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-backup-widget';
    protected int|string|array $columnSpan = 'full';

    public int $totalFiles = 0;
    public int $processedFiles = 0;
    public bool $isProcessing = false;

    public function downloadAll()
    {
        // prevent duplicate jobs
        if (Cache::get('company_backup_status') === 'processing') {
            return;
        }

        dispatch(new GenerateCompanyBackup());

        $this->isProcessing = true;
    }

    public function refreshProgress()
    {
        $this->totalFiles = Cache::get('company_backup_total', 0);
        $this->processedFiles = Cache::get('company_backup_processed', 0);

        if (Cache::get('company_backup_status') === 'ready') {
            $this->isProcessing = false;

            // start browser download
            $this->dispatch('start-download');
        }
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }
}
