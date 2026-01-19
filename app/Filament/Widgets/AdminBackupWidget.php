<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class AdminBackupWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-backup-widget';
    protected int|string|array $columnSpan = 'full';

    public function downloadAll()
    {
        return redirect()->route('company.download.all');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadAllData')
                ->label('Download All Company Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Download Company Data')
                ->modalDescription('This will download all files of your company as a ZIP.')
                ->modalSubmitActionLabel('Download')
                ->action(function () {
                    // Optional UX feedback
                    Notification::make()
                        ->title('Preparing download…')
                        ->success()
                        ->send();

                    // 🔑 Redirect browser to download route
                    return redirect()->route('company.download.all');
                }),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }
}
