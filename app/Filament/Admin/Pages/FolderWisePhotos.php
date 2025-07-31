<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
        $this->selectedFolder = request()->get('folder');
        $user = Auth::user();

        // Get users created by current admin or manager
        $createdUserIds = \App\Models\User::where('created_by', $user->id)->pluck('id')->toArray();

        // Generate folder paths like "user_5", "user_6" etc.
        $allowedFolders = collect($createdUserIds)->map(fn($id) => "user_{$id}");

        if ($this->selectedFolder === null) {
            // Get all folders
            $allFolders = Storage::disk('public')->directories();

            // Filter only folders that match the allowed user folders
            $this->folders = collect($allFolders)
                ->filter(fn($folder) => $allowedFolders->contains(explode('/', $folder)[0]))
                ->values()
                ->toArray();

        } else {
            $folderPath = $this->selectedFolder;

            // Check if folder belongs to a user created by this manager/admin
            $baseFolder = explode('/', $folderPath)[0];
            if (! $allowedFolders->contains($baseFolder)) {
                abort(403, 'Unauthorized access to folder.');
            }

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
