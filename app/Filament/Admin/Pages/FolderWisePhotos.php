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

        if ($this->selectedFolder === null) {
            // Get all top-level folders under "uploads" and sort by date
            $allFolders = Storage::disk('public')->directories('uploads');

            // Remove "uploads/" prefix and sort descending by date
            $this->folders = collect($allFolders)
                ->map(fn ($dir) => str_replace('uploads/', '', $dir))
                ->sortByDesc(function ($folder) {
                    return strtotime($folder); // assumes folder names are date strings
                })
                ->values()
                ->toArray();
        } else {
            $folderPath = 'uploads/' . $this->selectedFolder;

            // Subfolders inside selected folder
            $this->subfolders = collect(Storage::disk('public')->directories($folderPath))
                ->map(fn ($dir) => str_replace('uploads/', '', $dir))
                ->values()
                ->toArray();

            // Image files in selected folder
            if (Storage::disk('public')->exists($folderPath)) {
                $this->images = collect(Storage::disk('public')->files($folderPath))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                    ->map(fn ($file) => str_replace('uploads/', '', $file))
                    ->values()
                    ->toArray();
            }
        }
    }
}
