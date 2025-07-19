<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class FolderWisePhotos extends Page
{
    protected static string $view = 'filament.admin.pages.folder-wise-photos';

    public ?string $selectedFolder = null;
    public array $folders = [];
    public array $images = [];

    public function mount(): void
    {
        $this->selectedFolder = request()->get('folder');

        // Use Laravel's storage disk correctly
        $uploadPath = Storage::disk('public')->path('uploads');

        // Ensure base upload directory exists
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        if ($this->selectedFolder === null) {
            // List all folders inside /storage/app/public/uploads
            $this->folders = collect(File::directories($uploadPath))
                ->map(fn ($dir) => basename($dir))
                ->toArray();
        } else {
            // List image files from selected folder inside uploads
            $folderPath = $uploadPath . '/' . $this->selectedFolder;

            if (File::exists($folderPath)) {
                $this->images = collect(File::files($folderPath))
                    ->filter(fn ($file) => in_array($file->getExtension(), ['jpg', 'jpeg', 'png', 'gif']))
                    ->map(fn ($file) => $file->getFilename())
                    ->toArray();
            }
        }
    }
}
