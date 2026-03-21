<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Folder;
use Carbon\Carbon;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use App\Models\MediaFile;

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
    public string $globalSearch = '';
    public array $globalResults = [];
    public bool $isSearching = false;

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

            $priority = [
                'Today' => 3,
                'Yesterday' => 2,
            ];

            $aPriority = $priority[$a] ?? 1;
            $bPriority = $priority[$b] ?? 1;

            if ($aPriority !== $bPriority) {
                return $bPriority <=> $aPriority;
            }

            // If both are real dates
            if (!isset($priority[$a]) && !isset($priority[$b])) {
                return Carbon::createFromFormat('d-m-Y', $b)
                    ->timestamp <=> Carbon::createFromFormat('d-m-Y', $a)->timestamp;
            }

            return 0;
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

    protected function resolveFolderContext(string $path): array
    {
        $parts = explode('/', trim($path, '/'));

        return [
            'company_id' => $parts[0] ?? null,
            'user_id'    => $parts[1] ?? null,
            'folder'     => $parts[2] ?? null,
        ];
    }

    public function searchGlobal(): void
    {
        $query = trim(strtolower($this->globalSearch));

        if ($query === '') {
            $this->globalResults = [];
            return;
        }

        $results = [];

        $authUser = Auth::user();
        $companyIds = $authUser->companies()->pluck('companies.id');

        // ✅ ONLY admin-created users
        $users = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->select('id', 'name')
            ->get();

        foreach ($users as $user) {
            foreach ($companyIds as $companyId) {

                $basePath = "{$companyId}/{$user->id}";

                $results = array_merge(
                    $results,
                    Folder::where('company_id', $companyId)
                        ->where('name', $query)
                        ->get()
                        ->map(function ($folder) use ($user) {
                            return [
                                'type' => 'folder',
                                'name' => $folder->name,
                                'user' => $user->name,
                                'user_id' => $user->id,
                                'path' => $folder->path,
                            ];
                        })
                        ->toArray()
                );
            }
        }

        $this->globalResults = collect($results)
            ->unique('path')
            ->values()
            ->toArray();
    }

    protected function getUserPhotoCount(int $companyId, int $userId): int
    {
        return \App\Models\Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('type', 'image')
            ->count();
    }

    public function mount(): void
    {
        $this->page = (int) request()->get('page', 1);

        // logger()->info('AdminUsersPage mount started', [
        //     'user_param' => request()->get('user'),
        //     'folder_param' => request()->get('folder'),
        //     'subfolder_param' => request()->get('subfolder'),
        // ]);

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

        $photoCounts = cache()->remember(
            "photo_counts_{$activeCompanyId}",
            60,
            function () use ($activeCompanyId) {
                return \App\Models\Photo::select('user_id', DB::raw('COUNT(*) as total'))
                    ->where('company_id', $activeCompanyId)
                    ->where('type', 'image')
                    ->groupBy('user_id')
                    ->pluck('total', 'user_id');
            }
        );

        $this->users = User::select('id','name','profile_photo')
            ->where('role', 'user')
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
            ->map(function ($user) use ($photoCounts) {
                $user->photo_count = $photoCounts[$user->id] ?? 0;
                return $user;
            });

        // 🔹 If a user is selected → folders, subfolders, images
        if ($userId) {
            $this->selectedUser = User::select('id','name')->find($userId);
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

            // logger()->info('Shared folders from DB', [
            //     'count' => count($sharedFolders),
            //     'folders' => $sharedFolders,
            // ]);

            // Top-level folders (if no folder selected)
            if (!$folder) {

                $allFolders = Folder::where('company_id', $activeCompanyId)
                    ->where('user_id', $userId)
                    ->whereNull('parent_id')
                    ->orderByDesc('created_at')
                    ->skip(($this->page - 1) * $this->perPage)
                    ->take($this->perPage)
                    ->get()
                    ->map(function ($folder) {
                        return [
                            'type' => 'folder',
                            'path' => $folder->path,
                            'name' => $folder->name,
                            'created_at' => $folder->created_at,
                            'linked' => false,
                        ];
                    });

                // merge shared folders
                $allFolders = $allFolders->merge($sharedFolders);

                // ✅ IMPORTANT: sort by latest first
                // $allFolders = $allFolders
                //     ->sortByDesc(fn ($f) => $f['created_at'])
                //     ->values();

                // // pagination AFTER sorting
                // $this->total = $allFolders->count();

                // $pagedFolders = $allFolders->slice(
                //     ($this->page - 1) * $this->perPage,
                //     $this->perPage
                // )->values();

                $this->total = Folder::where('company_id', $activeCompanyId)
                    ->where('user_id', $userId)
                    ->whereNull('parent_id')
                    ->count();

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

                $isLinkedFolder = collect($sharedFolders)
                    ->contains(fn ($f) => trim($f['path'], '/') === trim($folder, '/'));

                // Always resolve owner from DB
                $selectedFolderModel = Folder::with('linkedFolders')
                    ->where('path', trim($folder, '/'))
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
                $currentFolderPath = $subfolderPath
                    ? "{$folderCompanyId}/{$realOwnerId}/{$folderName}/{$subfolderPath}"
                    : "{$folderCompanyId}/{$realOwnerId}/{$folderName}";

                $currentFolder = Folder::select('id','path','user_id')
                    ->where('path', trim($currentFolderPath, '/'))
                    ->first();

                $parentFolder = $currentFolder ?? $selectedFolderModel;

                $allSubfolders = Folder::where('parent_id', $parentFolder->id)
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get()
                    ->map(function ($folder) {
                        return [
                            'type' => 'folder',
                            'path' => $folder->path,
                            'name' => $folder->name,
                            'created_at' => $folder->created_at,
                            'linked' => false,
                        ];
                    })
                    ->toArray();

                $mediaFiles = collect();

                if ($currentFolder) {

                    $this->total = \App\Models\Photo::where('folder_id', $currentFolder->id)->count();

                    $mediaFiles = \App\Models\Photo::where('folder_id', $currentFolder->id)
                        ->orderBy('created_at', 'desc')
                        ->skip(($this->page - 1) * $this->perPage)
                        ->take($this->perPage)
                        ->get();
                }

                // 🔹 Linked folders from DB
                $pathParts = explode('/', trim($folder, '/'));
                $folderCompanyId = $pathParts[0] ?? null;
                $folderUserId    = $pathParts[1] ?? null;
                $folderName      = end($pathParts);

                //$selectedFolderModel = Folder::where('path', trim($folder, '/'))->first();

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

                $mediaAll = $mediaFiles->map(function ($file) {
                    return [
                        'type' => $file->type,
                        'path' => $file->path,
                        'name' => basename($file->path),
                        'created_at' => $file->created_at,
                    ];
                });

                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                    'linked' => $folder['linked'] ?? false,
                ]);

                //$grouped = $this->groupByDate($combined);

                // paginate AFTER grouping (flattened)
                // $flat = collect($grouped)->flatten(1)->values();
                // $paged = $flat->slice(
                //     ($this->page - 1) * $this->perPage,
                //     $this->perPage
                // )->values();

                // $this->items = $this->groupByDate($paged->toArray());
                // $this->images = $paged->toArray();
                $combined = $folderItems->merge($mediaAll)->take(200)->toArray();
                $this->items = $this->groupByDate($combined);
            }
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}