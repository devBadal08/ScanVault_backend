<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Folder;
use Carbon\Carbon;

class AdminUsersPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.admin-users-page';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?int $navigationSort = 7;
    protected static ?string $recordTitleAttribute = 'name';

    public $managers = [];
    public $adminUsers = [];
    public $users = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $items = [];
    public $globalSearch = '';
    public $globalResults = [];

    public $selectedManager = null;
    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    // pagination properties
    public int $perPage = 550; 
    public int $page = 1;     
    public int $total = 0;    

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
            if (!isset($item['created_at'])) continue;

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

    public function updatedGlobalSearch()
    {
        if (strlen($this->globalSearch) < 2) {
            $this->globalResults = [];
            return;
        }

        $query = strtolower($this->globalSearch);
        $results = [];

        // ✅ Get managers under admin
        $managerIds = User::where('role', 'manager')
            ->where('created_by', auth()->id())
            ->pluck('id');

        // ✅ Get all users created by admin AND managers
        $users = User::where(function($q) use ($managerIds) {
                $q->where('created_by', auth()->id())
                ->orWhereIn('created_by', $managerIds)
                ->orWhere('id', auth()->id());
            })
            ->get();

        foreach ($users as $user) {

            $basePath = (string) $user->id;

            if (!Storage::disk('public')->exists($basePath)) {
                continue;
            }

            // ✅ Include root + all sub folders
            $allFolders = collect([$basePath])
                            ->merge(Storage::disk('public')->allDirectories($basePath))
                            ->unique();

            foreach ($allFolders as $folder) {

                // ✅ Folder match
                if (str_contains(strtolower(basename($folder)), $query)) {
                    $results[] = [
                        'type'     => 'folder',
                        'name'     => basename($folder),
                        'user'     => $user->name,
                        'user_id'  => $user->id,
                        'path'     => $folder,
                    ];
                }

                // ✅ File match
                $files = Storage::disk('public')->files($folder);

                foreach ($files as $file) {
                    if (str_contains(strtolower(basename($file)), $query)) {

                        $results[] = [
                            'type'    => pathinfo($file, PATHINFO_EXTENSION),
                            'name'    => basename($file),
                            'user'    => $user->name,
                            'user_id' => $user->id,
                            'path'    => $file,
                        ];
                    }
                }
            }
        }

        // ✅ Limit + sort
        $this->globalResults = collect($results)
            ->sortByDesc('name')
            ->take(60)
            ->values()
            ->toArray();
    }

    public function mount(): void
    {
        $authUser = Auth::user();
        $managerId = request()->get('manager');
        $userId = request()->get('user');
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if ($authUser->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $adminId = $authUser->id;

        // 🔹 Managers and Admin Users
        $this->managers = User::where('role', 'manager')->where('created_by', $adminId)->get();
        $this->adminUsers = User::where('role', 'user')->where('created_by', $adminId)->get();

        // 🔹 Manager selection → show their users
        if ($managerId) {
            $this->selectedManager = $this->managers->firstWhere('id', $managerId);
            $this->users = User::where('role', 'user')->where('created_by', $managerId)->get();
        } else {
            $this->users = $this->adminUsers;
        }

        // 🔹 If a user is selected → folders, subfolders, images
        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId; // top-level storage folder

            // Top-level folders (if no folder selected)
            if (!$folder) {
                $rawFolders = collect(Storage::disk('public')->directories($baseUserPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                        'linked' => false,
                    ])->toArray();

                $this->folders = $this->groupByDate($rawFolders);

            } else {
                // Selected folder / subfolder
                $this->selectedFolder = $folder;
                $targetPath = $subfolder ?: $folder;
                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // 🔹 Physical subfolders
                $rawSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                        'linked' => false,
                    ])->toArray();

                // 🔹 Linked folders from DB
                $linkedFolders = [];
                $selectedFolderModel = Folder::where('user_id', $this->selectedUser->id)
                    ->where('name', basename($folder))
                    ->first();

                if ($selectedFolderModel) {
                    $linkedFolders = $selectedFolderModel->linkedFolders
                        ->map(fn($f) => [
                            'type' => 'folder',
                            'path' => $f->user_id . '/' . $f->name, // full storage path
                            'name' => $f->name,
                            'created_at' => $f->created_at->toDateTimeString(),
                            'linked' => true, // highlight linked
                        ])->toArray();
                }

                // Merge physical + linked folders
                $this->subfolders = collect($rawSubfolders)
                    ->merge($linkedFolders)
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                // 🔹 Fetch media files (images + videos)
                $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];
                $allMedia = collect(Storage::disk('public')->files($targetPath))
                    ->filter(fn($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowedExtensions))
                    ->map(fn($file) => [
                        'type' => match(strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                            'mp4' => 'video',
                            'pdf' => 'pdf',
                            default => 'image',
                        },
                        'path' => $file,
                        'name' => basename($file),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))
                                            ->toDateTimeString(),
                        'merged_from' => \App\Models\Photo::where('path', $file)->first()?->source_folder_id,
                    ])->values();

                $this->total = $allMedia->count();

                // Pagination
                $mediaPaged = $allMedia->forPage($this->page, $this->perPage)->values();

                // Merge folders + paged media
                $merged = collect($this->subfolders)->merge($mediaPaged)->values();
                $this->items = $this->groupByDate($merged->toArray());
                $this->images = $mediaPaged->toArray();
            }
        }
    }

    public function updatedPage()
    {
        $this->mount();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }
}