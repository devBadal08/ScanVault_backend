<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Folder;
use Carbon\Carbon;
use App\Models\Company;

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
    public int $perPage = 30; 
    public int $page = 1;     
    public int $total = 0;    
    public int $datesPerPage = 3;

    protected function groupByDate(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            if (empty($item['created_at'])) {
                continue;
            }

            $created = Carbon::parse($item['created_at']);

            // Label logic
            if ($created->isToday()) {
                $label = 'Today';
            } elseif ($created->isYesterday()) {
                $label = 'Yesterday';
            } else {
                // 👇 EXACT DATE GROUPING
                $label = $created->format('d-m-Y');
            }

            $groups[$label][] = $item;
        }

        // Sort sections by latest date first
        uksort($groups, function ($a, $b) {
            if (in_array($a, ['Today', 'Yesterday']) || in_array($b, ['Today', 'Yesterday'])) {
                return 0;
            }

            return Carbon::createFromFormat('d-m-Y', $b)
                ->timestamp <=> Carbon::createFromFormat('d-m-Y', $a)->timestamp;
        });

        return $groups;
    }

    protected function paginateDateGroups(array $grouped): array
    {
        $keys = array_keys($grouped);

        $pagedKeys = array_slice(
            $keys,
            ($this->page - 1) * $this->datesPerPage,
            $this->datesPerPage
        );

        $result = [];

        foreach ($pagedKeys as $key) {
            $result[$key] = $grouped[$key];
        }

        $this->total = count($keys); // total date groups

        return $result;
    }

    protected function getMediaDate(string $filePath): Carbon
    {
        $absolutePath = storage_path('app/public/' . $filePath);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        // 1️⃣ EXIF first
        if (in_array($extension, ['jpg','jpeg','png']) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($absolutePath);
            if (!empty($exif['DateTimeOriginal'])) {
                return Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
            }
        }

        // 2️⃣ Filename timestamp (milliseconds)
        if (preg_match('/_(\d{13})\./', $filePath, $matches)) {
            return Carbon::createFromTimestampMs((int) $matches[1]);
        }

        // 3️⃣ Filesystem fallback
        return Carbon::createFromTimestamp(
            Storage::disk('public')->lastModified($filePath)
        );
    }

    protected function getFolderDate(string $folderPath): Carbon
    {
        $latestDate = null;

        // scan files recursively
        foreach (Storage::disk('public')->files($folderPath) as $file) {
            $date = $this->getMediaDate($file);

            if (!$latestDate || $date->gt($latestDate)) {
                $latestDate = $date;
            }
        }

        // if folder has media → use media date
        if ($latestDate) {
            return $latestDate;
        }

        // fallback → filesystem date
        return Carbon::createFromTimestamp(
            Storage::disk('public')->lastModified($folderPath)
        );
    }

    protected function resolveFolderContext(string $path): array
    {
        $parts = explode('/', trim($path, '/'));

        return [
            'company_id' => $parts[0] ?? null,
            'user_id'    => $parts[1] ?? null,
            'folder'     => $parts[2] ?? null,
        ];
    }

    public function updatedGlobalSearch()
    {
        $query = trim(strtolower($this->globalSearch));

        // reset if empty or too short
        if (strlen($query) < 2) {
            $this->globalResults = [];
            return;
        }

        // 1️⃣ Resolve company scope once
        $companyIds = Auth::user()->companies()->pluck('companies.id');

        if ($companyIds->isEmpty()) {
            $this->globalResults = [];
            return;
        }

        // 2️⃣ Resolve users once
        $authUser = Auth::user();

        // Admin → users created by admin + their managers
        $managerIds = User::where('role', 'manager')
            ->where('created_by', $authUser->id)
            ->pluck('id');

        $users = User::where('role', 'user')
            ->where(function ($q) use ($authUser, $managerIds) {
                $q->where('created_by', $authUser->id)
                ->orWhereIn('created_by', $managerIds);
            })
            ->select('id', 'name', 'created_by')
            ->get();

        if ($users->isEmpty()) {
            $this->globalResults = [];
            return;
        }

        $results = [];

        // 3️⃣ Build base paths once per user
        foreach ($users as $user) {
            foreach ($companyIds as $companyId) {

                $basePath = "{$companyId}/{$user->id}";

                if (!Storage::disk('public')->exists($basePath)) {
                    continue;
                }

                // 4️⃣ Scan folders first (cheap)
                $directories = Storage::disk('public')->allDirectories($basePath);

                foreach ($directories as $dir) {
                    $folderName = strtolower(basename($dir));

                    if (str_contains($folderName, $query)) {
                        $results[] = [
                            'type'    => 'folder',
                            'name'    => basename($dir),
                            'user'    => $user->name,
                            'user_id' => $user->id,
                            'path'    => $dir,
                        ];
                    }
                }

                // 5️⃣ Scan files once (expensive, but controlled)
                $files = Storage::disk('public')->allFiles($basePath);

                foreach ($files as $file) {
                    $fileName = strtolower(basename($file));

                    if (!str_contains($fileName, $query)) {
                        continue;
                    }

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

        // 6️⃣ Normalize, deduplicate, sort, limit
        $this->globalResults = collect($results)
            ->unique('path')
            ->sortBy(fn ($r) => $r['name'])
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
        $this->page = (int) request()->get('page', 1);

        logger()->info('AdminUsersPage mount started', [
            'user_param' => request()->get('user'),
            'folder_param' => request()->get('folder'),
            'subfolder_param' => request()->get('subfolder'),
        ]);

        $authUser = Auth::user();
        $activeCompanyId = $authUser->companies()->first()?->id;

        $managerId = trim(request()->get('manager'));
        $userId = trim(request()->get('user'));

        $folder = request()->get('folder');
        $pathParts = $folder ? explode('/', trim($folder, '/')) : [];

        $folderCompanyId = isset($pathParts[0]) ? (int) $pathParts[0] : null;
        $realOwnerId     = isset($pathParts[1]) ? (int) $pathParts[1] : null;
        $folderName      = $pathParts[2] ?? null;

        $subfolder = request()->get('subfolder');

        $folderName = $folder ? basename($folder) : null;
        $subfolderPath = $subfolder ? trim($subfolder, '/') : null;

        if ($authUser->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $adminId = $authUser->id;

        // Managers and Admin Users
        $companyIds = collect([
            $authUser->companies()->first()?->id, // main company
        ])->merge(
            Company::where('parent_id', $authUser->companies()->first()?->id)->pluck('id')
        )->filter()->values();

        $this->users = User::where('role', 'user')
            ->whereHas('companies', function ($q) use ($activeCompanyId) {
                $q->where('company_id', $activeCompanyId);
            })
            ->where(function ($q) use ($activeCompanyId) {
                $q->whereNull('created_by') // admin/system users
                ->orWhereDoesntHave('creator', function ($c) use ($activeCompanyId) {
                    $c->where('role', 'manager')
                        ->whereHas('companies', function ($qc) use ($activeCompanyId) {
                            $qc->where('company_id', $activeCompanyId);
                        });
                });
            })
            ->get()
            ->map(function ($user) use ($activeCompanyId) {
                $user->photo_count = $this->getUserPhotoCount(
                    $activeCompanyId,
                    $user->id
                );
                return $user;
            });

        // 🔹 If a user is selected → folders, subfolders, images
        if ($userId) {
            $this->selectedUser = User::find($userId);
            // ✅ SHARED FOLDERS FROM DATABASE
            $sharedFolders = \App\Models\FolderShare::with('folder')
                ->where('shared_with', $userId)
                ->whereHas('folder', function ($q) use ($activeCompanyId) {
                    $q->where('company_id', $activeCompanyId);
                })
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

            $baseUserPaths = collect([
                "{$activeCompanyId}/{$userId}"
            ])->filter(fn ($path) => Storage::disk('public')->exists($path));

            // Top-level folders (if no folder selected)
            if (!$folder) {

                $allFolders = collect();

                foreach ($baseUserPaths as $path) {
                    foreach (Storage::disk('public')->directories($path) as $dir) {
                        $allFolders->push([
                            'type' => 'folder',
                            'path' => $dir,
                            'name' => basename($dir),
                            'created_at' => Carbon::createFromTimestamp(
                                Storage::disk('public')->lastModified($dir)
                            )->toDateTimeString(),
                            'linked' => false,
                        ]);
                    }
                }

                // merge shared folders
                $allFolders = $allFolders->merge($sharedFolders);

                // ✅ IMPORTANT: sort by latest first
                $allFolders = $allFolders
                    ->sortByDesc(fn ($f) => $f['created_at'])
                    ->values();

                // pagination AFTER sorting
                $this->total = $allFolders->count();

                $pagedFolders = $allFolders->slice(
                    ($this->page - 1) * $this->perPage,
                    $this->perPage
                )->values();

                // group only the paged items
                $grouped = $this->groupByDate($allFolders->toArray());

                // only 3 date sections on first page
                $this->folders = $this->paginateDateGroups($grouped);
            } else {
                // Selected folder / subfolder
                $this->selectedFolder = $folder;

                // 🔥 ALWAYS resolve folder from DB (never trust URL)
                $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";

                // ✅ FINAL TARGET PATH
                $targetPath = $subfolderPath
                    ? "{$basePath}/{$subfolderPath}"
                    : $basePath;

                logger()->info('Resolved admin target path', [
                    'targetPath' => $targetPath,
                    'exists' => Storage::disk('public')->exists($targetPath),
                ]);

                $isLinkedFolder = collect($sharedFolders)
                    ->contains(fn ($f) => trim($f['path'], '/') === trim($folder, '/'));

                logger()->info('Linked folder detection', [
                    'clicked_folder' => $folder,
                    'shared_paths' => collect($sharedFolders)->pluck('path')->toArray(),
                    'is_linked' => $isLinkedFolder,
                ]);

                // 🔥 Always resolve owner from DB
                $selectedFolderModel = Folder::where('company_id', $folderCompanyId)
                    ->where('user_id', $realOwnerId)
                    ->where('name', $folderName)
                    ->first();

                if (!$selectedFolderModel) {
                    logger()->warning('Folder not found in DB', compact('folder'));
                    return;
                }

                $realOwnerId = $selectedFolderModel->user_id;

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

                // $currentRootPath = trim($folder, '/');

                // if ($subfolderName) {
                //     $currentRootPath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
                //     $targetPath = $subfolderName
                //         ? "{$currentRootPath}/{$subfolderName}"
                //         : $currentRootPath;
                // } else {
                //     $currentRootPath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
                //     $targetPath = $currentRootPath;
                // }

                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // Physical subfolders
                $allSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn ($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(
                            Storage::disk('public')->lastModified($dir)
                        )->toDateTimeString(),
                        'linked' => false,
                    ])
                    ->values()
                    ->toArray();

                // 🔹 Linked folders from DB
                $pathParts = explode('/', trim($folder, '/'));
                $folderCompanyId = $pathParts[0] ?? null;
                $folderUserId    = $pathParts[1] ?? null;
                $folderName      = end($pathParts);

                $selectedFolderModel = Folder::where('company_id', $folderCompanyId)
                    ->where('user_id', $realOwnerId)
                    ->where('name', $folderName)
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
                $this->subfolders = collect($allSubfolders)
                    ->merge($linkedFolders)
                    ->merge($reverseLinkedFolders)
                    ->unique('path')
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                // Fetch media files (images + videos)
                $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

                $allFiles = Storage::disk('public')->files($targetPath);

                // ✅ FILTER FIRST (this was missing)
                $filteredFiles = array_values(array_filter($allFiles, function ($file) use ($allowedExtensions) {
                    return in_array(
                        strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                        $allowedExtensions
                    );
                }));

                // ✅ THEN MAP
                $mediaAll = collect($filteredFiles)->map(fn ($file) => [
                    'type' => match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                        'mp4' => 'video',
                        'pdf' => 'pdf',
                        default => 'image',
                    },
                    'path' => $file,
                    'name' => basename($file),
                    'created_at' => $this->getMediaDate($file)->toDateTimeString(),
                ]);

                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                    'linked' => $folder['linked'] ?? false,
                ]);

                $combined = $folderItems->merge($mediaAll)->toArray();
                $grouped = $this->groupByDate($combined);
                $this->total = $mediaAll->count();

                // paginate AFTER grouping (flattened)
                $flat = collect($grouped)->flatten(1)->values();
                $paged = $flat->slice(
                    ($this->page - 1) * $this->perPage,
                    $this->perPage
                )->values();

                $this->items = $this->groupByDate($paged->toArray());
                $this->images = $paged->toArray();
            }
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }
}