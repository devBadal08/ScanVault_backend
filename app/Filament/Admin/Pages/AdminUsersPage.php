<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

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
    public int $perPage = 1; 
    public int $page = 1;     
    public int $total = 0;    
    public int $datesPerPage = 3;

    protected function groupByDate(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            // 👇 1. CHANGE THIS IF STATEMENT
            if (empty($item['created_at'])) {
                $groups['Older / Unknown Date'][] = $item; // Give it a fallback group!
                continue;
            }

            $created = Carbon::parse($item['created_at']);

            // Label logic
            if ($created->isToday()) {
                $label = 'Today';
            } elseif ($created->isYesterday()) {
                $label = 'Yesterday';
            } else {
                $label = $created->format('d-m-Y');
            }

            $groups[$label][] = $item;
        }

        // Sort sections by latest date first
        uksort($groups, function ($a, $b) {

            // 👇 2. ADD 'Older / Unknown Date' TO YOUR PRIORITY LIST
            $priority = [
                'Today' => 3,
                'Yesterday' => 2,
                'Older / Unknown Date' => -1, // Pushes it to the very bottom
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

        $this->total = count($grouped); // number of groups (correct)

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
        $query = trim($this->globalSearch);

        // Minimum 8 characters condition
        if (strlen($query) < 8) {
            $this->globalResults = [];
            return;
        }

        $authUser = Auth::user();

        // Get company IDs
        $companyIds = $authUser->companies()->pluck('companies.id');

        // Get users (only once)
        $users = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->select('id', 'name')
            ->get();

        if ($users->isEmpty()) {
            $this->globalResults = [];
            return;
        }

        // 🔥 SINGLE QUERY (IMPORTANT)
        $photos = \App\Models\Photo::whereIn('company_id', $companyIds)
            ->whereIn('user_id', $users->pluck('id'))
            ->where('path', 'LIKE', "%{$query}%")
            ->select('path', 'user_id') // reduce load
            ->get();

        // Map user names once
        $userMap = $users->keyBy('id');

        // Convert to folders
        $results = $photos->map(function ($photo) use ($userMap) {

            $parts = explode('/', $photo->path);

            return [
                'type' => 'folder',
                'name' => $parts[count($parts) - 2] ?? null,
                'user' => $userMap[$photo->user_id]->name ?? null,
                'user_id' => $photo->user_id,
                'path' => implode('/', array_slice($parts, 0, -1)),
            ];
        })
        ->filter(fn($f) => $f['name'])
        ->unique('path')
        ->values()
        ->toArray();

        $this->globalResults = $results;
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

                $photos = \App\Models\Photo::where('company_id', $activeCompanyId)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->select('path', 'created_at')
                    ->get();

                $folders = $photos->map(function ($photo) {
                    $parts = explode('/', $photo->path);

                    return [
                        'type' => 'folder',
                        'name' => $parts[2] ?? null,
                        'path' => implode('/', array_slice($parts, 0, 3)),
                        'created_at' => $photo->created_at,
                    ];
                })
                ->filter(fn($f) => $f['name'])
                ->unique('path')
                ->sortByDesc('created_at')
                ->values()
                ->toArray();

                $this->folders = $this->paginateDateGroups(
                    $this->groupByDate($folders)
                );
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
                $pathParts = explode('/', trim($folder, '/'));

                $folderCompanyId = (int) ($pathParts[0] ?? $activeCompanyId);
                $realOwnerId     = (int) ($pathParts[1] ?? $userId);
                $folderName      = $pathParts[2] ?? basename($folder);

                $subfolderPath = $subfolder ? trim($subfolder, '/') : null;

                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolderPath;

                $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";

                $targetPath = $subfolderPath
                    ? "{$basePath}/{$subfolderPath}"
                    : $basePath;

                // ✅ GET ALL FILES FROM DB
                $photos = \App\Models\Photo::where('company_id', $activeCompanyId)
                    ->where('user_id', $realOwnerId)
                    ->where('path', 'LIKE', "{$targetPath}/%")
                    ->orderBy('created_at', 'desc')
                    ->get();

                // ✅ SUBFOLDERS FROM DB
                $this->subfolders = $photos->map(function ($photo) use ($targetPath) {
                    $relative = str_replace($targetPath . '/', '', $photo->path);
                    $parts = explode('/', $relative);

                    if (count($parts) > 1) {
                        return [
                            'type' => 'folder',
                            'name' => $parts[0],
                            'path' => $targetPath . '/' . $parts[0],
                            'created_at' => $photo->created_at,
                        ];
                    }

                    return null;
                })
                ->filter()
                ->unique('path')
                ->values()
                ->toArray();

                // ✅ FILES FROM DB
                $mediaAll = $photos->filter(function ($photo) use ($targetPath) {
                    $relative = str_replace($targetPath . '/', '', $photo->path);
                    return count(explode('/', $relative)) === 1;
                })->map(function ($photo) {
                    return [
                        'type' => $photo->type,
                        'path' => $photo->path,
                        'name' => basename($photo->path),
                        'created_at' => $photo->created_at,
                    ];
                });

                // ✅ MERGE
                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                ]);

                $combined = $folderItems->merge($mediaAll)->toArray();

                // ✅ PAGINATION SAME
                $grouped = $this->groupByDate($combined);

                $flat = collect($grouped)->flatten(1)->values();

                $paged = $flat->slice(
                    ($this->page - 1) * $this->perPage,
                    $this->perPage
                )->values();

                $this->items = $this->groupByDate($paged->toArray());
                $this->images = $paged->toArray();
                $this->total = count($combined);
            }
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}