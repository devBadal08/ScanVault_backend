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
    public $folders = [];      // top-level folders grouped by date
    public $subfolders = [];   // raw subfolder list (not grouped) - useful for links
    public $images = [];       // paginated image items (array)
    public $items = [];        // merged items (folders + images) grouped by date

    // pagination properties
    public int $perPage = 550; // images per page (adjust if needed)
    public int $page = 1;     // current page
    public int $total = 0;    // total images

    protected function groupByDate(array $items): array
    {
        $lastThreeDays = [];
        for ($i = 1; $i <= 3; $i++) {
            $lastThreeDays[] = now()->subDays($i)->format('d-m-Y');
        }

        $groups = array_merge(
            ['Today' => []],
            array_combine($lastThreeDays, array_fill(0, 3, [])),
            [
                'Last Week' => [],
                'Earlier this Month' => [],
                'Older' => [],
            ]
        );

        foreach ($items as $item) {
            // safeguard: item must have created_at
            if (!isset($item['created_at'])) {
                continue;
            }

            $created = Carbon::parse($item['created_at']);
            $createdDate = $created->format('d-m-Y');

            if ($created->isToday()) {
                $groups['Today'][] = $item;
            } elseif (in_array($createdDate, $lastThreeDays)) {
                $groups[$createdDate][] = $item;
            } elseif ($created->greaterThanOrEqualTo(now()->subWeek())) {
                $groups['Last Week'][] = $item;
            } elseif ($created->month === now()->month) {
                $groups['Earlier this Month'][] = $item;
            } else {
                $groups['Older'][] = $item;
            }
        }

        return array_filter($groups);
    }

    public function mount(): void
    {
        $authUser = Auth::user();
        $managerId = request()->get('manager');
        $userId = request()->get('user');
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        // ADMIN -> managers & adminUsers
        if ($authUser->role === 'admin') {
            $adminId = $authUser->id;
            $this->managers = \App\Models\User::where('role', 'manager')
                ->where('created_by', $adminId)
                ->get();
            $this->adminUsers = \App\Models\User::where('role', 'user')
                ->where('created_by', $adminId)
                ->get();

            if ($managerId) {
                $this->selectedManager = $this->managers->firstWhere('id', $managerId);
                $this->users = \App\Models\User::where('role', 'user')
                    ->where('created_by', $managerId)
                    ->get();
            }
        } elseif ($authUser->role === 'manager') {
            // MANAGER -> his users
            $this->selectedManager = $authUser;
            $this->users = \App\Models\User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get();
        }

        // If a user is selected
        if ($userId) {
            $this->selectedUser = \App\Models\User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId;

            // STEP 2: Show top-level folders grouped by date
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
            }

            // STEP 3 / STEP 4: inside a folder (either listing subfolders/images of $folder,
            // or if $subfolder present, listing deeper subfolders/images of $subfolder)
            else {
                // note: set selected folder(s)
                $this->selectedFolder = $folder;
                $targetPath = $subfolder ? $subfolder : $folder; // where to read items from
                if ($subfolder) {
                    $this->selectedSubfolder = $subfolder;
                }

                // --- subfolders inside targetPath ---
                $rawSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn($dir) => [
                        'type'       => 'folder',
                        'path'       => $dir,
                        'name'       => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                    ]);

                // store raw subfolders for other uses (links, downloads)
                $this->subfolders = $rawSubfolders->values()->toArray();

                // --- all images in targetPath (for pagination + merging) ---
                $allImages = collect(Storage::disk('public')->files($targetPath))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg','jpeg','png']))
                    ->map(fn($file) => [
                        'type'       => 'image',
                        'path'       => $file,
                        'name'       => basename($file),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))
                                            ->toDateTimeString(),
                    ])
                    ->values();

                // pagination values
                $this->total = $allImages->count();

                // images for current page
                $imagesPaged = $allImages
                    ->forPage($this->page, $this->perPage)
                    ->values();

                $this->images = $imagesPaged->toArray();

                // Separate folders and images
                $foldersSorted = $rawSubfolders->sortByDesc(fn($i) => $i['created_at'])->values();
                $imagesSorted  = $imagesPaged->sortByDesc(fn($i) => $i['created_at'])->values();

                // Merge with folders always first
                $merged = $foldersSorted->merge($imagesSorted)->values();

                // group merged items by date using same groupByDate helper
                $this->items = $this->groupByDate($merged->toArray());
            }
        }
    }

    // When page changes (Livewire property), reload mount
    public function updatedPage()
    {
        $this->mount();
    }
}
