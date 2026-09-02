<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Photo;
use App\Models\Company;
use App\Models\Folder;
use App\Models\User;
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

        $adminCompanyId = $authUser->companies()->first()?->id;

        if (!$adminCompanyId) {
            abort(403, 'Company not found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Admin company + child companies
        |--------------------------------------------------------------------------
        */

        $companyIds = collect([
            $adminCompanyId,
        ])
            ->merge(
                Company::where('parent_id', $adminCompanyId)->pluck('id')
            )
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Users created directly by admin
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Get managers created by this admin
        |--------------------------------------------------------------------------
        */

        $managerIds = User::where('role', 'manager')
            ->where('created_by', $authUser->id)
            ->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Get all users created by:
        | 1. Admin directly
        | 2. Managers created by this admin
        |--------------------------------------------------------------------------
        */

        $backupUserIds = User::where('role', 'user')
            ->where(function ($query) use ($authUser, $managerIds) {

                $query->where('created_by', $authUser->id)
                    ->orWhereIn('created_by', $managerIds);

            })
            ->pluck('id');

        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate   = Carbon::parse($this->endDate)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $deletedCount = 0;
        $deletedSizeMB = 0;

        /*
        |--------------------------------------------------------------------------
        | Store folders affected by deleted photos
        |--------------------------------------------------------------------------
        |
        | Example photo:
        |
        | 26/125/test_22-8/photo.jpg
        |
        | Folder:
        |
        | 26/125/test_22-8
        |
        */

        $affectedFolders = [];

        $deletedPhotosByUser = [];
        $deletedFoldersByUser = [];

        /*
        |--------------------------------------------------------------------------
        | Delete matching photos
        |--------------------------------------------------------------------------
        */

        Photo::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('user_id', $backupUserIds)
            ->whereNotNull('backed_up_at')
            ->whereBetween('created_at', [
                $startDate,
                $endDate,
            ])
            ->orderBy('id')
            ->chunkById(500, function ($photos) use (
                &$deletedCount,
                &$deletedSizeMB,
                &$affectedFolders
            ) {

                foreach ($photos as $photo) {

                    /*
                    |--------------------------------------------------------------------------
                    | Find folder path before deleting photo
                    |--------------------------------------------------------------------------
                    */

                    if ($photo->path) {

                        $parts = explode(
                            '/',
                            trim($photo->path, '/')
                        );

                        if (count($parts) >= 3) {

                            $folderPath = implode(
                                '/',
                                array_slice($parts, 0, -1)
                            );

                            $affectedFolders[$folderPath] = true;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Delete physical file
                    |--------------------------------------------------------------------------
                    */

                    if ($photo->path) {

                        try {

                            if (Storage::disk('public')->exists($photo->path)) {

                                $sizeBytes = Storage::disk('public')
                                    ->size($photo->path);

                                $deletedSizeMB +=
                                    $sizeBytes / (1024 * 1024);

                                Storage::disk('public')
                                    ->delete($photo->path);
                            }

                        } catch (\Throwable $e) {

                            \Log::warning(
                                'Unable to delete photo file.',
                                [
                                    'photo_id' => $photo->id,
                                    'path' => $photo->path,
                                    'error' => $e->getMessage(),
                                ]
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Delete photo database record
                    |--------------------------------------------------------------------------
                    */

                    $photo->delete();

                    $deletedCount++;

                    $deletedPhotosByUser[$photo->user_id] =
                        ($deletedPhotosByUser[$photo->user_id] ?? 0) + 1;
                }
            });

        /*
        |--------------------------------------------------------------------------
        | Remove empty folders
        |--------------------------------------------------------------------------
        */

        $deletedFolderCount = 0;

        foreach (array_keys($affectedFolders) as $folderPath) {

            /*
            |--------------------------------------------------------------------------
            | Check this folder and its parents
            |--------------------------------------------------------------------------
            */

            $currentPath = trim($folderPath, '/');

            while ($currentPath) {

                /*
                |--------------------------------------------------------------------------
                | Check whether any photo remains inside this folder
                |--------------------------------------------------------------------------
                */

                $hasRemainingPhotos = Photo::query()
                    ->whereIn('company_id', $companyIds)
                    ->whereIn('user_id', $backupUserIds)
                    ->where('path', 'LIKE', $currentPath . '/%')
                    ->exists();

                /*
                |--------------------------------------------------------------------------
                | If photos still exist, this folder must remain
                |--------------------------------------------------------------------------
                */

                if ($hasRemainingPhotos) {
                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Find folder
                |--------------------------------------------------------------------------
                */

                $folder = Folder::query()
                    ->whereIn('company_id', $companyIds)
                    ->whereIn('user_id', $backupUserIds)
                    ->where('path', $currentPath)
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Delete folder
                |--------------------------------------------------------------------------
                */

                if ($folder) {

                    $folder->delete();

                    $deletedFolderCount++;

                    $deletedFoldersByUser[$folder->user_id] =
                        ($deletedFoldersByUser[$folder->user_id] ?? 0) + 1;

                    /*
                    |--------------------------------------------------------------------------
                    | Delete physical directory
                    |--------------------------------------------------------------------------
                    */

                    if (Storage::disk('public')->exists($currentPath)) {
                        Storage::disk('public')
                            ->deleteDirectory($currentPath);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Move to parent folder
                |--------------------------------------------------------------------------
                */

                $parentPath = dirname($currentPath);

                /*
                |--------------------------------------------------------------------------
                | Stop when we reach company/user root
                |--------------------------------------------------------------------------
                */

                if ($parentPath === '.' || substr_count($parentPath, '/') < 2) {
                    break;
                }

                $currentPath = $parentPath;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Update company statistics
        |--------------------------------------------------------------------------
        */

        $company = Company::find($adminCompanyId);

        if ($company) {

            $company->total_photos = max(
                0,
                $company->total_photos - $deletedCount
            );

            $company->used_storage_mb = max(
                0,
                $company->used_storage_mb - $deletedSizeMB
            );

            $company->save();
        }

        /*
        |--------------------------------------------------------------------------
        | Update user statistics
        |--------------------------------------------------------------------------
        */

        $affectedUserIds = array_unique(array_merge(
            array_keys($deletedPhotosByUser),
            array_keys($deletedFoldersByUser)
        ));

        foreach ($affectedUserIds as $userId) {

            $user = User::find($userId);

            if (!$user) {
                continue;
            }

            $user->total_photos = Photo::where('user_id', $userId)
                ->where('type', 'image')
                ->count();

            $user->total_folders = Folder::where('user_id', $userId)
                ->count();

            $user->save();
        }

        /*
        |--------------------------------------------------------------------------
        | Reset
        |--------------------------------------------------------------------------
        */

        $this->startDate = null;
        $this->endDate = null;

        /*
        |--------------------------------------------------------------------------
        | Success notification
        |--------------------------------------------------------------------------
        */

        \Filament\Notifications\Notification::make()
            ->title('Photos Deleted')
            ->body(
                $deletedCount .
                ' photos and ' .
                $deletedFolderCount .
                ' empty folders were permanently deleted.'
            )
            ->success()
            ->send();
    }
}