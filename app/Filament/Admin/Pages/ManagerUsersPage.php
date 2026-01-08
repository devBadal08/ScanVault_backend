<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Folder;
use App\Models\Photo;

class ManagerUsersPage extends Page
{
    protected static string $view = 'filament.admin.pages.manager-users-page';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        if ($user && $user->role === 'manager') {
            return 'User Photos';
        }

        return 'Manager Photos';
    }

    public $managerUsers = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $users = [];
    public $items = [];
    public $globalSearch = '';
    public $globalResults = [];
    public bool $isSearching = false;

    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

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

    public function mountedFolderPermissionsCheck($fullPath)
    {
        if (is_dir($fullPath)) {
            @chmod($fullPath, 0755);
        }
    }

    public function updatedGlobalSearch()
    {
        if (strlen($this->globalSearch) < 3) {
            $this->globalResults = [];
            $this->isSearching = false;
            return;
        }

        $this->isSearching = true;

        $query = strtolower($this->globalSearch);
        $results = [];

        $authUser = Auth::user();
        $companyId = $authUser->companies()->first()?->id;

        if (!$companyId) {
            return;
        }

        if ($authUser->role === 'manager') {
            $users = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->whereHas('companies', fn ($q) => $q->where('company_id', $companyId))
                ->get();
        } else {
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $users = User::whereHas('companies', fn ($q) => $q->where('company_id', $companyId))
                ->where(function ($q) use ($authUser, $managerIds) {
                    $q->where('created_by', $authUser->id)
                    ->orWhereIn('created_by', $managerIds);
                })
                ->get();
        }

        foreach ($users as $user) {
            $basePaths = [];

            $basePaths[] = "{$companyId}/{$user->id}";

            $creator = User::find($user->created_by);
            if ($creator && $creator->role === 'manager') {
                $basePaths[] = "{$companyId}/{$creator->id}/{$user->id}";
            }

            foreach ($basePaths as $basePath) {
                if (!Storage::disk('public')->exists($basePath)) {
                    continue;
                }

                $allFolders = collect([$basePath])
                    ->merge(Storage::disk('public')->allDirectories($basePath))
                    ->unique();

                foreach ($allFolders as $folder) {
                    if (str_contains(strtolower(basename($folder)), $query)) {
                        $results[] = [
                            'type'    => 'folder',
                            'name'    => basename($folder),
                            'user'    => $user->name,
                            'user_id' => $user->id,
                            'path'    => $folder,
                        ];
                    }

                    foreach (Storage::disk('public')->allFiles($folder) as $file) {
                        if (str_contains(strtolower(basename($file)), $query)) {
                            $results[] = [
                                'type'    => pathinfo($file, PATHINFO_EXTENSION),
                                'name'    => basename($file),
                                'user'    => $user->name,
                                'user_id' => $user->id,
                                'path'    => $file,
                                'parent'  => dirname($file),
                            ];
                        }
                    }
                }
            }
        }

        $this->globalResults = collect($results)
            ->unique('path')
            ->sortByDesc('name')
            ->take(60)
            ->values()
            ->toArray();
    }

    protected function getUserPhotoCount(int $companyId, int $userId): int
    {
        $path = storage_path("app/public/{$companyId}/{$userId}");

        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $extensions = ['jpg', 'jpeg', 'png'];

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        ) as $file) {
            if (
                $file->isFile() &&
                in_array(strtolower($file->getExtension()), $extensions)
            ) {
                $count++;
            }
        }

        return $count;
    }

    public function mount(): void
    {
        if ($this->isSearching) {
            return;
        }

        $authUser = Auth::user();

        $userId = request()->get('user');

        $companyId = request()->get('company_id')
            ?? $authUser->companies()->first()?->id;

        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            // Default: if not passed, load the admin's own company
            if (!$companyId) {
                abort(403, 'Company not found');
            }
        }

        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if (!in_array($authUser->role, ['manager', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $companyId = $authUser->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Company not found for this user');
        }

        if ($authUser->role === 'manager') {
            $this->managerUsers = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get()
                ->map(function ($user) use ($companyId) {
                    $user->photo_count = $this->getUserPhotoCount($companyId, $user->id);
                    return $user;
                });
        } else {

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $this->managerUsers = User::where('role', 'user')
                ->whereIn('created_by', $managerIds)
                ->get()
                ->map(function ($user) use ($companyId) {
                    $user->photo_count = $this->getUserPhotoCount($companyId, $user->id);
                    return $user;
                });
        }

        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = "{$companyId}/{$userId}";

            if (!$folder) {

                $rawFolders = collect(Storage::disk('public')->directories($baseUserPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                        'linked' => false,
                        'owner_id' => $this->selectedUser->id,
                    ])->toArray();

                // 🔹 Linked folders (from folder_links)
                // $linkedFolders = Folder::whereIn('id', function ($q) use ($userId) {
                //     $q->select('target_folder_id')
                //     ->from('folder_links')
                //     ->whereIn('source_folder_id', function ($sq) use ($userId) {
                //         $sq->select('id')
                //             ->from('folders')
                //             ->where('user_id', $userId);
                //     });
                // })
                // ->where('company_id', $companyId)
                // ->get()
                // ->map(function ($folder) {
                //     return [
                //         'type' => 'folder',
                //         'path' => "{$folder->company_id}/{$folder->user_id}/{$folder->name}",
                //         'name' => $folder->name,
                //         'created_at' => $folder->created_at->toDateTimeString(),
                //         'linked' => true,
                //         'owner_id' => $folder->user_id,
                //     ];
                // })
                // ->toArray();

                $mergedFolders = collect($rawFolders)
                    //->merge($linkedFolders)
                    ->unique('path')
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                $this->folders = $this->groupByDate($mergedFolders);

            } else {

                // Normalize folder and subfolder to names only
                $folderName = basename($folder);
                $subfolderName = $subfolder ? basename($subfolder) : null;

                $this->selectedFolder = $folderName;
                if ($subfolderName) {
                    $this->selectedSubfolder = $subfolderName;
                }

                // 🔹 Resolve real owner of folder (important for linked folders)
                $realOwnerId = $userId;

                $selectedFolderModel = Folder::where('name', $folderName)
                    ->where('user_id', $realOwnerId)
                    ->first();

                $isLinkedFolder = false;

                if ($selectedFolderModel) {
                    $isLinkedFolder = \DB::table('folder_links')
                        ->where('target_folder_id', $selectedFolderModel->id)
                        ->exists();
                }

                $linkedSubfolderModel = null;

                if ($subfolder) {
                    $linkedSubfolderModel = Folder::where('name', $subfolderName)
                        ->where('company_id', $companyId)
                        ->first();
                }

                if ($selectedFolderModel) {
                    $link = \DB::table('folder_links')
                        ->where('target_folder_id', $selectedFolderModel->id)
                        ->first();

                    if ($link) {
                        $sourceFolder = Folder::find($link->source_folder_id);
                        if ($sourceFolder) {
                            $realOwnerId = $sourceFolder->user_id;
                        }
                    }
                }

                // Build correct relative storage path
                if ($subfolder && $linkedSubfolderModel) {
                    // Linked folder root
                    $currentRootPath = "{$companyId}/{$linkedSubfolderModel->user_id}/{$linkedSubfolderModel->name}";
                    $targetPath = $currentRootPath;
                } elseif ($subfolder) {
                    // Normal subfolder
                    $currentRootPath = "{$companyId}/{$realOwnerId}/{$folderName}";
                    $targetPath = "{$currentRootPath}/{$subfolderName}";
                } else {
                    // Normal folder
                    $currentRootPath = "{$companyId}/{$realOwnerId}/{$folderName}";
                    $targetPath = $currentRootPath;
                }

                // ✅ permission fix
                $this->mountedFolderPermissionsCheck(storage_path("app/public/{$targetPath}"));

                $rawSubfolders = [];

                $rawSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn ($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(
                            Storage::disk('public')->lastModified($dir)
                        )->toDateTimeString(),
                        'linked' => false,
                    ])
                    ->toArray();

                // Load folders linked FROM this folder (mounted links)
                $mountedLinkedFolders = [];

                // Do NOT mount links inside linked folders
                if (!$isLinkedFolder && !$subfolder) {

                    $currentFolder = Folder::where('name', $folderName)
                        ->where('company_id', $companyId)
                        ->where('user_id', $realOwnerId)
                        ->first();

                    if ($currentFolder) {
                        $mountedLinkedFolders = Folder::whereIn('id', function ($q) use ($currentFolder) {
                                $q->select('target_folder_id')
                                ->from('folder_links')
                                ->where('source_folder_id', $currentFolder->id);
                            })
                            ->get()
                            ->map(function ($folder) {
                                return [
                                    'type' => 'folder',
                                    'path' => "{$folder->company_id}/{$folder->user_id}/{$folder->name}",
                                    'name' => $folder->name,
                                    'created_at' => $folder->created_at->toDateTimeString(),
                                    'linked' => true,
                                    'owner_id' => $folder->user_id,
                                ];
                            })
                            ->toArray();
                    }
                }

                $this->subfolders = collect($rawSubfolders)
                    ->merge($mountedLinkedFolders)
                    ->unique('path')
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

                $allMedia = collect(Storage::disk('public')->files($targetPath))
                    ->filter(fn($file) =>
                        in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowedExtensions)
                    )
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
                    ])->values();

                $this->total = $allMedia->count();

                $mediaPaged = $allMedia->forPage($this->page, $this->perPage)->values();

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
        $user = auth()->user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}