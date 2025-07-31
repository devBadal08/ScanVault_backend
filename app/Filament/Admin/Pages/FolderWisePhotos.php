<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Photo;

class FolderWisePhotos extends Page
{
    protected static string $view = 'filament.admin.pages.folder-wise-photos';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Folder Wise Photos';
    protected static ?int $navigationSort = 6;

    public ?string $selectedFolder = null;
    public array $folders = [];
    public array $images = [];
    public array $subfolders = [];

    public function mount(): void
    {
        $managerId = Auth::id(); // Get currently logged-in manager/admin ID

        // Step 1: Get user IDs under this manager
        $userIds = User::where('created_by', $managerId)->pluck('id');

        // Step 2: Get unique folder paths from photos of these users
        $photoPaths = Photo::whereIn('user_id', $userIds)->pluck('path');

        // Step 3: Extract unique folder names from photo paths
        $folderPaths = $photoPaths
            ->map(function ($path) {
                return dirname($path);
            })
            ->unique()
            ->values()
            ->toArray();

        // Step 4: Assign to folders property for Filament to show
        $this->folders = $folderPaths;

        // Step 5: Handle selected folder view
        $this->selectedFolder = request()->get('folder');

        if ($this->selectedFolder !== null) {
            $folderPath = $this->selectedFolder;

            $this->subfolders = collect(Storage::disk('public')->directories($folderPath))
                ->values()
                ->toArray();

            if (Storage::disk('public')->exists($folderPath)) {
                $this->images = collect(Storage::disk('public')->files($folderPath))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                    ->values()
                    ->toArray();
            }
        }
    }
}
