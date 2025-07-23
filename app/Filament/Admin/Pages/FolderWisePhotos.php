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
            // List only top-level folders in "uploads"
            $allFolders = Storage::disk('public')->directories('uploads');

            // Remove "uploads/" prefix
            $this->folders = array_map(fn ($dir) => str_replace('uploads/', '', $dir), $allFolders);
        } else {
            $folderPath = 'uploads/' . $this->selectedFolder;

            // List subfolders inside the selected folder
            $this->subfolders = Storage::disk('public')->directories($folderPath);
            $this->subfolders = array_map(
                fn ($dir) => str_replace('uploads/', '', $dir),
                $this->subfolders
            );
            // List image files in this folder
            if (Storage::disk('public')->exists($folderPath)) {
                $this->images = collect(Storage::disk('public')->files($folderPath))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                    ->map(fn ($file) => basename($file))
                    ->toArray();
            }
        }
    }
}
