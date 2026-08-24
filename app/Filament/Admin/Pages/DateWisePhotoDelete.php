<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DateWisePhotoDelete extends Page
{
    protected static string $view =
        'filament.admin.pages.date-wise-photo-delete';

    protected static ?string $navigationIcon =
        'heroicon-o-trash';

    protected static ?string $navigationGroup =
        'Backup & Delete';

    protected static ?int $navigationSort = 13;

    private const DELETE_PASSWORD = 'Tech@strota2026';

    public bool $passwordVerified = false;

    public string $deletePassword = '';

    public ?string $passwordError = null;

    /*
    |--------------------------------------------------------------------------
    | Delete Preview
    |--------------------------------------------------------------------------
    */

    public bool $showPreview = false;

    public int $totalUsers = 0;

    public int $totalFolders = 0;

    public int $totalPhotos = 0;

    public string $totalSize = '0 MB';

    public ?string $startDate = null;

    public ?string $endDate = null;

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public static function getNavigationLabel(): string
    {
        return 'Delete Photos';
    }

    public function getTitle(): string
    {
        return 'Delete Photos';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Password
    |--------------------------------------------------------------------------
    */

    public function verifyPassword(): void
    {
        $this->passwordError = null;

        if ($this->deletePassword !== self::DELETE_PASSWORD) {

            $this->passwordError = 'Incorrect password.';

            $this->deletePassword = '';

            return;
        }

        $this->passwordVerified = true;

        $this->deletePassword = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    */

    public function previewDelete(): void
    {
        if (!$this->startDate || !$this->endDate) {
            return;
        }

        if ($this->startDate > $this->endDate) {
            return;
        }

        /*
         * We will add the actual database query
         * in the next step.
         */

        $this->showPreview = true;

        // Temporary values for UI testing
        $this->totalUsers = 0;
        $this->totalFolders = 0;
        $this->totalPhotos = 0;
        $this->totalSize = '0 MB';
    }

    /*
    |--------------------------------------------------------------------------
    | Permanent Delete
    |--------------------------------------------------------------------------
    */

    public function deletePermanently(): void
    {
        if (!$this->passwordVerified) {
            abort(403);
        }

        if (!$this->startDate || !$this->endDate) {

            \Filament\Notifications\Notification::make()
                ->title('Select Date Range')
                ->body('Please select both start date and end date.')
                ->danger()
                ->send();

            return;
        }

        if ($this->startDate > $this->endDate) {

            \Filament\Notifications\Notification::make()
                ->title('Invalid Date Range')
                ->body('End date cannot be before start date.')
                ->danger()
                ->send();

            return;
        }

        $authUser = Auth::user();

        if (!$authUser) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | Get admin company
        |--------------------------------------------------------------------------
        */

        $companyId = $authUser->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Company not found.');
        }

        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate   = Carbon::parse($this->endDate)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Find matching photos
        |--------------------------------------------------------------------------
        */

        $deletedCount = 0;

        Photo::query()
            ->where('company_id', $companyId)
            ->whereNotNull('backed_up_at')
            ->whereBetween('created_at', [
                $startDate,
                $endDate,
            ])
            ->orderBy('id')
            ->chunkById(500, function ($photos) use (&$deletedCount) {

                foreach ($photos as $photo) {

                    // Delete physical file
                    if ($photo->path) {
                        if (Storage::disk('public')->exists($photo->path)) {
                            Storage::disk('public')->delete($photo->path);
                        }
                    }

                    // Delete database record
                    $photo->delete();

                    $deletedCount++;
                }
            });

        /*
        |--------------------------------------------------------------------------
        | Reset
        |--------------------------------------------------------------------------
        */

        $this->startDate = null;
        $this->endDate = null;

        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        \Filament\Notifications\Notification::make()
            ->title('Photos Deleted')
            ->body(
                $deletedCount . ' photos were permanently deleted.'
            )
            ->success()
            ->send();
    }
}