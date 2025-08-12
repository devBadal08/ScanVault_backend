<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FolderWisePhotos extends Page
{
    protected static string $view = 'filament.admin.pages.folder-wise-photos';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Folder Wise Photos';
    protected static ?int $navigationSort = 6;

    public $selectedManager = null;
    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    public $managers = [];
    public $users = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];

    public function mount(): void
    {
        $authUser = Auth::user();
        $managerId = request()->get('manager');
        $userId = request()->get('user');
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if ($authUser->role === 'admin') {
            $adminId = $authUser->id;
            $this->managers = \App\Models\User::where('role', 'manager')->where('created_by', $adminId)->get();

            if ($managerId) {
                $this->selectedManager = $this->managers->firstWhere('id', $managerId);
                $this->users = \App\Models\User::where('role', 'user')->where('created_by', $managerId)->get();
            }

        } elseif ($authUser->role === 'manager') {
            $this->selectedManager = $authUser;
            $this->users = \App\Models\User::where('role', 'user')->where('created_by', $authUser->id)->get();
        }

        if ($userId) {
            $this->selectedUser = \App\Models\User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId;

            if (!$folder) {
                $this->folders = Storage::disk('public')->directories($baseUserPath);

            } elseif (!$subfolder) {
                $this->selectedFolder = $folder;

                $this->subfolders = Storage::disk('public')->directories($folder);
                $this->images = collect(Storage::disk('public')->files($folder))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    ->values()
                    ->toArray();

            } else {
                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolder;

                // ✅ Fetch deeper subfolders here
                $this->subfolders = Storage::disk('public')->directories($subfolder);
                
                $this->images = collect(Storage::disk('public')->files($subfolder))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    ->values()
                    ->toArray();
            }
        }
    }
}
