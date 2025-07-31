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
            $allFolders = Storage::disk('public')->directories();

            $this->folders = collect($allFolders)
                ->sortByDesc(function ($folder) {
                    return strtotime(basename($folder)); // Optional sorting by date string
                })
                ->values()
                ->toArray();
        } else {
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
