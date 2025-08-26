<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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

    public $adminUsers = [];
    public $managers = [];
    public $users = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];

    // pagination properties
    public int $perPage = 550; // images per page
    public int $page = 1;     // current page
    public int $total = 0;    // total images

    protected function groupByDate(array $folders): array
    {
        // Generate last 3 dates before today
        $lastThreeDays = [];
        for ($i = 1; $i <= 3; $i++) {
            $lastThreeDays[] = now()->subDays($i)->format('d-m-Y');
        }

        $groups = array_merge(
            ['Today' => []],
            array_combine($lastThreeDays, array_fill(0, 3, [])), // Yesterday, 2 days ago, 3 days ago
            [
                'Last Week' => [],
                'Earlier this Month' => [],
                'Older' => [],
            ]
        );

        foreach ($folders as $folder) {
            $created = Carbon::parse($folder['created_at']);
            $createdDate = $created->format('d-m-Y');

            if ($created->isToday()) {
                $groups['Today'][] = $folder;
            } elseif (in_array($createdDate, $lastThreeDays)) {
                $groups[$createdDate][] = $folder;
            } elseif ($created->greaterThanOrEqualTo(now()->subWeek())) {
                $groups['Last Week'][] = $folder;
            } elseif ($created->month === now()->month) {
                $groups['Earlier this Month'][] = $folder;
            } else {
                $groups['Older'][] = $folder;
            }
        }

        // remove empty groups
        return array_filter($groups);
    }

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
            $this->adminUsers = \App\Models\User::where('role', 'user')->where('created_by', $adminId)->get();

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
                $rawFolders = collect(Storage::disk('public')->directories($baseUserPath))
                    ->map(fn($dir) => [
                        'path'       => $dir,
                        'name'       => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                        ->toDateTimeString(),
                    ])
                    ->toArray();

                $this->folders = $this->groupByDate($rawFolders);
            } elseif (!$subfolder) {
                $this->selectedFolder = $folder;

                $this->subfolders = collect(Storage::disk('public')->directories($folder))
                ->map(fn($dir) => [
                    'path'       => $dir,
                    'name'       => basename($dir),
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                    ->toDateTimeString(),
                ])
                ->toArray();
                // ✅ Paginate images here
                $allImages = collect(Storage::disk('public')->files($folder))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    ->values();

                $this->total = $allImages->count();
                $this->images = $allImages
                    ->forPage($this->page, $this->perPage)
                    ->toArray();

            } else {
                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolder;

                // ✅ Fetch deeper subfolders
                $this->subfolders = collect(Storage::disk('public')->directories($subfolder))
                ->map(fn($dir) => [
                    'path'       => $dir,
                    'name'       => basename($dir),
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                    ->toDateTimeString(),
                ])
                ->toArray();

                // ✅ Paginate images here
                $allImages = collect(Storage::disk('public')->files($subfolder))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    ->values();

                $this->total = $allImages->count();
                $this->images = $allImages
                    ->forPage($this->page, $this->perPage)
                    ->toArray();
            }
        }
    }

    // 👇 When page changes, reload images
    public function updatedPage()
    {
        $this->mount(); // re-run mount to refresh images
    }
}