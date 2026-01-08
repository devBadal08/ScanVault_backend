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
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static string $view = 'filament.admin.pages.admin-users-page';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'User Photos';
    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'name';

    public $adminUsers = [];
    public $users = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $items = [];
    public $globalSearch = '';
    public $globalResults = [];

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
        if (strlen($this->globalSearch) < 3) {
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
        $companyId = Auth::user()->companies()->first()?->id;

        $users = User::whereHas('companies', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->get();

        $companyId = Auth::user()->companies()->first()?->id;

        foreach ($users as $user) {

            // build paths PER USER
            $basePaths = [];

            // admin-created user
            $basePaths[] = "{$companyId}/{$user->id}";

            // manager-created user
            $creator = User::find($user->created_by);
            if ($creator && $creator->role === 'manager') {
                $basePaths[] = "{$companyId}/{$creator->id}/{$user->id}";
            }

            foreach ($basePaths as $basePath) {

                if (!Storage::disk('public')->exists($basePath)) {
                    continue;
                }

                // root + all subfolders
                $allFolders = collect([$basePath])
                    ->merge(Storage::disk('public')->allDirectories($basePath))
                    ->unique();

                foreach ($allFolders as $folder) {

                    // folder name match
                    if (str_contains(strtolower(basename($folder)), $query)) {
                        $results[] = [
                            'type'    => 'folder',
                            'name'    => basename($folder),
                            'user'    => $user->name,
                            'user_id' => $user->id,
                            'path'    => $folder,
                        ];
                    }

                    // recursive file match
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

        // ✅ Limit + sort
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
        logger()->info('AdminUsersPage mount started', [
            'user_param' => request()->get('user'),
            'folder_param' => request()->get('folder'),
            'subfolder_param' => request()->get('subfolder'),
        ]);

        $authUser = Auth::user();
        $managerId = trim(request()->get('manager'));
        $userId = trim(request()->get('user'));

        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        $folderName = $folder ? basename($folder) : null;
        $subfolderName = $subfolder ? basename($subfolder) : null;

        if ($authUser->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $adminId = $authUser->id;

        // Managers and Admin Users
        $companyId = $authUser->companies()->first()?->id;

        $this->users = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->whereHas('companies', fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function ($user) use ($companyId) {
                $user->photo_count = $this->getUserPhotoCount($companyId, $user->id);
                return $user;
            });

        // 🔹 If a user is selected → folders, subfolders, images
        if ($userId) {
            $this->selectedUser = User::find($userId);
            // ✅ SHARED FOLDERS FROM DATABASE
            $sharedFolders = \App\Models\FolderShare::with('folder')
                ->where('shared_with', $userId)
                ->get()
                ->map(function ($share) {
                    $folder = $share->folder;
                    if (!$folder) return null;

                    $cleanName = trim($folder->name);

                    return [
                        'type' => 'folder',
                        'folder_id' => $folder->id,
                        'path' => "{$folder->company_id}/{$folder->user_id}/{$cleanName}",
                        'name' => $cleanName,
                        'created_at' => $share->created_at->toDateTimeString(),
                        'linked' => true,
                    ];
                })
                ->filter()
                ->toArray();

            if (!$this->selectedUser) return;

            logger()->info('Shared folders from DB', [
                'count' => count($sharedFolders),
                'folders' => $sharedFolders,
            ]);

            //$companyId = $authUser->company_id;

            $baseUserPath = "{$companyId}/{$userId}";

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

                    // MERGE SHARED FOLDERS
                    $rawFolders = collect($rawFolders)
                        ->merge($sharedFolders)
                        ->unique('path')
                        ->values()
                        ->toArray();

                $this->folders = $this->groupByDate($rawFolders);

                logger()->info('Top level folders after merge', [
                    'folders' => $this->folders,
                ]);

            } else {
                // Selected folder / subfolder
                $this->selectedFolder = $folder;

                $isLinkedFolder = collect($sharedFolders)
                    ->contains(fn ($f) => trim($f['path'], '/') === trim($folder, '/'));

                logger()->info('Linked folder detection', [
                    'clicked_folder' => $folder,
                    'shared_paths' => collect($sharedFolders)->pluck('path')->toArray(),
                    'is_linked' => $isLinkedFolder,
                ]);

                $realOwnerId = $userId;

                $selectedFolderModel = Folder::where('name', $folderName)
                    ->where('company_id', $companyId)
                    ->first();

                $isLinkedFolder = false;

                if ($selectedFolderModel) {
                    $link = \DB::table('folder_links')
                        ->where('target_folder_id', $selectedFolderModel->id)
                        ->first();

                    if ($link) {
                        $sourceFolder = Folder::find($link->source_folder_id);
                        if ($sourceFolder) {
                            $realOwnerId = $sourceFolder->user_id;
                            $isLinkedFolder = true;
                        }
                    }
                }

                if ($subfolderName) {
                    $currentRootPath = "{$companyId}/{$realOwnerId}/{$folderName}";
                    $targetPath = "{$currentRootPath}/{$subfolderName}";
                } else {
                    $currentRootPath = "{$companyId}/{$realOwnerId}/{$folderName}";
                    $targetPath = $currentRootPath;
                }

                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // Physical subfolders
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
                $pathParts = explode('/', trim($folder, '/'));
                $folderCompanyId = $pathParts[0] ?? null;
                $folderUserId    = $pathParts[1] ?? null;
                $folderName      = end($pathParts);

                $selectedFolderModel = Folder::where('company_id', $folderCompanyId)
                    ->where('user_id', $folderUserId)
                    ->whereRaw('TRIM(name) = ?', [$folderName])
                    ->first();

                $linkedFolders = collect();

                if ($selectedFolderModel) {
                    $linkedFolders = $selectedFolderModel->linkedFolders
                        ->map(fn ($f) => [
                            'type' => 'folder',
                            'path' => "{$f->company_id}/{$f->user_id}/{$f->name}",
                            'name' => $f->name,
                            'created_at' => $f->created_at->toDateTimeString(),
                            'linked' => true,
                        ]);
                }

                $reverseLinkedFolders = collect();

                if ($selectedFolderModel) {
                    $reverseLinkedFolders = \DB::table('folder_links')
                        ->join('folders as f', 'f.id', '=', 'folder_links.source_folder_id')
                        ->where('folder_links.target_folder_id', $selectedFolderModel->id)
                        ->select(
                            'f.company_id',
                            'f.user_id',
                            'f.name',
                            'folder_links.created_at'
                        )
                        ->get()
                        ->map(fn ($f) => [
                            'type' => 'folder',
                            'path' => "{$f->company_id}/{$f->user_id}/{$f->name}",
                            'name' => $f->name,
                            'created_at' => $f->created_at,
                            'linked' => true,
                        ]);
                }

                // Merge physical + linked folders
                $this->subfolders = collect($rawSubfolders)
                    ->merge($linkedFolders)
                    ->merge($reverseLinkedFolders)
                    ->unique('path')
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                // Fetch media files (images + videos)
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

                logger()->info('Target path check', [
                    'targetPath' => $targetPath,
                    'exists' => Storage::disk('public')->exists($targetPath),
                ]);

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