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
use App\Models\PhotoDeleteHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    public int $perPage = 30;
    public int $page = 1;
    public int $total = 0;
    public int $datesPerPage = 3;

    public function canDeletePhotos(): bool
    {
        $user = Auth::user();

        return (bool) $user->can_delete_photos;
    }

    public function deletePhoto($path)
    {
        if (!$this->canDeletePhotos()) {
            abort(403);
        }

        $userId = $this->selectedUser->id ?? null;
        $companyId = auth()->user()->companies()->first()?->id;

        if (Storage::disk('public')->exists($path)) {

            // ✅ Get file size BEFORE delete
            $fileSizeMB = Storage::disk('public')->size($path) / (1024 * 1024);

            PhotoDeleteHistory::create([
                'deleted_by' => auth()->id(),
                'user_id' => $userId,
                'company_id' => $companyId,
                'photo_path' => $path,
            ]);

            // ✅ Update company storage
            $company = \App\Models\Company::find($companyId);

            if ($company) {
                $company->used_storage_mb = max(
                    0,
                    $company->used_storage_mb - $fileSizeMB
                );

                $company->total_photos = max(
                    0,
                    $company->total_photos - 1
                );

                $company->save();
            }

            // ✅ Delete from DB
            Photo::where('path', $path)->delete();

            // ✅ Delete file
            Storage::disk('public')->delete($path);

            // ✅ CLEAR CACHE
            $targetPath = dirname($path);
            Cache::forget("files_{$targetPath}");
        }

        // Remove image from Livewire state instantly
        foreach ($this->items as $date => $group) {
            $this->items[$date] = array_filter($group, function ($item) use ($path) {
                return $item['path'] !== $path;
            });

            if (empty($this->items[$date])) {
                unset($this->items[$date]);
            }
        }
    }

    public function bulkDeleteMedia($items)
    {
        if (!$this->canDeletePhotos()) {
            abort(403);
        }

        $userId = $this->selectedUser->id ?? null;

        foreach ($items as $item) {

            $path = $item['path'];
            $type = $item['type'] ?? 'file';

            if ($type === 'folder') {

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->deleteDirectory($path);
                }

                // ✅ Remove folder instantly from UI
                foreach ($this->folders as $group => $folderGroup) {
                    $this->folders[$group] = array_filter($folderGroup, function ($f) use ($path) {
                        return $f['path'] !== $path;
                    });

                    if (empty($this->folders[$group])) {
                        unset($this->folders[$group]);
                    }
                }

                foreach ($this->items as $group => $itemGroup) {
                    $this->items[$group] = array_filter($itemGroup, function ($i) use ($path) {
                        return $i['path'] !== $path;
                    });

                    if (empty($this->items[$group])) {
                        unset($this->items[$group]);
                    }
                }

            } else {
                $companyId = auth()->user()->companies()->first()?->id;

                if (Storage::disk('public')->exists($path)) {

                    $fileSizeMB = Storage::disk('public')->size($path) / (1024 * 1024);

                    PhotoDeleteHistory::create([
                        'deleted_by' => auth()->id(),
                        'user_id' => $userId,
                        'company_id' => $companyId,
                        'photo_path' => $path,
                    ]);

                    // ✅ Update company storage
                    $company = \App\Models\Company::find($companyId);

                    if ($company) {
                        $company->used_storage_mb = max(
                            0,
                            $company->used_storage_mb - $fileSizeMB
                        );

                        $company->total_photos = max(
                            0,
                            $company->total_photos - 1
                        );

                        $company->save();
                    }

                    Photo::where('path', $path)->delete();
                    Storage::disk('public')->delete($path);

                    $targetPath = dirname($path);
                    Cache::forget("files_{$targetPath}");
                }

                // ✅ Remove file instantly
                foreach ($this->items as $group => $itemGroup) {
                    $this->items[$group] = array_filter($itemGroup, function ($i) use ($path) {
                        return $i['path'] !== $path;
                    });

                    if (empty($this->items[$group])) {
                        unset($this->items[$group]);
                    }
                }
            }
        }
    }

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

        // total DATE groups (important for pagination UI)
        $this->total = count($keys);

        return $result;
    }

    protected function getMediaDate(string $filePath): Carbon
    {
        // Use filename timestamp if available
        if (preg_match('/_(\d{13})\./', $filePath, $matches)) {
            return Carbon::createFromTimestampMs((int) $matches[1]);
        }

        // fallback only
        return Carbon::createFromTimestamp(
            Storage::disk('public')->lastModified($filePath)
        );
    }

    protected function getFolderDate(string $folderPath): Carbon
    {
        return Carbon::createFromTimestamp(
            Storage::disk('public')->lastModified($folderPath)
        );
    }

    public function mountedFolderPermissionsCheck($fullPath)
    {
        if (is_dir($fullPath)) {
            @chmod($fullPath, 0755);
        }
    }

    public function searchGlobal(): void
    {
        $query = trim(strtolower($this->globalSearch));

        if (strlen($query) < 6) {
            $this->globalResults = [];
            return;
        }

        $this->isSearching = true;
        $results = [];

        $authUser = Auth::user();

        // ✅ Get users under manager
        if ($authUser->role === 'manager') {

            $users = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->select('id', 'name')
                ->get();

        } else {

            // ✅ Admin → get managers first
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            // ✅ Then get users under those managers
            $users = User::where('role', 'user')
                ->whereIn('created_by', $managerIds)
                ->select('id', 'name')
                ->get();
        }

        if ($users->isEmpty()) {
            $this->globalResults = [];
            $this->isSearching = false;
            return;
        }

        $companyIds = $authUser->companies()->pluck('companies.id');

        // =========================================================
        // ✅ STEP 1: SEARCH MAIN FOLDERS (STRONG MATCH)
        // =========================================================

        $mainFolders = [];

        foreach ($users as $user) {
            foreach ($companyIds as $companyId) {

                $mainFolders = array_merge(
                    $mainFolders,
                    Folder::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->where(function ($q) use ($query) {
                            $cleanQuery = str_replace(' ', '', $query);

                            $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                            ->orWhereRaw('REPLACE(LOWER(name), " ", "") LIKE ?', ["%{$cleanQuery}%"]);
                        })
                        ->get()
                        ->map(function ($folder) use ($user) {
                            return [
                                'type' => 'folder',
                                'name' => trim($folder->name),
                                'user' => $user->name,
                                'user_id' => $user->id,
                                'folder' => trim($folder->path),
                                'subfolder' => null,
                            ];
                        })
                        ->toArray()
                );
            }
        }

        // ✅ If main folder found → RETURN ONLY THAT (better UX)
        if (!empty($mainFolders)) {
            $this->globalResults = collect($mainFolders)
                ->unique(fn($item) => strtolower($item['folder']))
                ->values()
                ->toArray();

            $this->isSearching = false;
            return;
        }

        // =========================================================
        // ✅ STEP 2: SEARCH SUBFOLDERS (FROM PHOTOS PATH)
        // =========================================================

        $photoResults = Photo::whereIn('company_id', $companyIds)
            ->whereIn('user_id', $users->pluck('id'))
            ->whereRaw('LOWER(path) LIKE ?', ["%{$query}%"])
            ->select('path', 'user_id')
            ->get()

            // ✅ GROUP BY SUBFOLDER (IMPORTANT)
            ->groupBy(function ($photo) {
                $parts = explode('/', $photo->path);
                return implode('/', array_slice($parts, 0, -1));
            })

            ->map(function ($group) use ($users) {

                $photo = $group->first();
                $parts = explode('/', $photo->path);

                $subPath = array_slice($parts, 3, -1);

                return [
                    'type' => 'folder',
                    'name' => trim($parts[count($parts) - 2] ?? null),
                    'user' => $users->firstWhere('id', $photo->user_id)?->name,
                    'user_id' => $photo->user_id,

                    'folder' => trim(implode('/', array_slice($parts, 0, 3))),
                    'subfolder' => !empty($subPath)
                        ? trim(implode('/', $subPath))
                        : null,
                ];
            })

            ->filter(fn($f) => $f['name'])

            ->unique(function ($item) {
                return strtolower(trim($item['folder'])) . '|' .
                    strtolower(trim($item['subfolder'] ?? ''));
            })

            ->values()
            ->toArray();

        // =========================================================
        // ✅ FINAL RESULT
        // =========================================================

        $this->globalResults = $photoResults;
        $this->isSearching = false;
    }

    protected function getUserPhotoCount(int $companyId, int $userId): int
    {
        return Photo::query()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                ->orWhereNull('company_id');
            })
            ->where('user_id', $userId)
            ->where('type', 'image')
            ->count();
    }

    public function mount(): void
    {
        $this->page = (int) request()->get('page', 1);

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

            if (!$companyId) {
                abort(403, 'Company not found');
            }
        }

        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        // ✅ RESET STATE FIRST
        $this->selectedFolder = null;
        $this->selectedSubfolder = null;

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
                        'created_at' => $this->getFolderDate($dir)->toDateTimeString(),
                        'linked' => false,
                        'owner_id' => $this->selectedUser->id,
                    ])->toArray();

                $allFolders = collect($rawFolders)
                    ->unique('path')
                    ->sortByDesc(fn ($i) => $i['created_at'])
                    ->values();

                $grouped = $this->groupByDate($allFolders->toArray());
                $this->folders = $this->paginateDateGroups($grouped);

            } else {
                $pathParts = explode('/', trim($folder, '/'));

                $folderCompanyId = (int) ($pathParts[0] ?? $companyId);
                $realOwnerId     = (int) ($pathParts[1] ?? $userId);
                $folderName      = $pathParts[2] ?? null;

                $extraPath = array_slice($pathParts, 3);
                $fromSearch = request()->get('from_search');

                if ($fromSearch && !$subfolder && !empty($extraPath)) {
                    $subfolder = implode('/', $extraPath);
                }

                $subfolderPath = null;
                if ($subfolder) {
                    $subfolderPath = trim($subfolder, '/');
                    if (!str_starts_with($subfolderPath, $folderName)) {
                        $subfolderPath = $subfolderPath;
                    }
                }

                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolderPath;

                $selectedFolderModel = Folder::where('name', $folderName)
                    ->where('user_id', $realOwnerId)
                    ->first();

                $isLinkedFolder = false;
                if ($selectedFolderModel) {
                    $isLinkedFolder = \DB::table('folder_links')
                        ->where('target_folder_id', $selectedFolderModel->id)
                        ->exists();
                }

                // ========================================================
                // ✅ RESOLVE TRUE TARGET PATH FOR LINKED SUBFOLDERS
                // ========================================================
                $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
                $targetPath = $basePath;

                if ($subfolderPath) {
                    $subfolderParts = explode('/', $subfolderPath);
                    $firstSubfolderPart = $subfolderParts[0];

                    $isLinked = false;
                    
                    if ($selectedFolderModel) {
                        $linkedSubfolderModel = Folder::where('name', $firstSubfolderPart)
                            ->whereIn('id', function($q) use ($selectedFolderModel) {
                                $q->select('target_folder_id')
                                  ->from('folder_links')
                                  ->where('source_folder_id', $selectedFolderModel->id);
                            })->first();

                        if ($linkedSubfolderModel) {
                            $isLinked = true;
                            // Map to true physical location of the linked folder
                            $linkedBasePath = "{$linkedSubfolderModel->company_id}/{$linkedSubfolderModel->user_id}/{$linkedSubfolderModel->name}";

                            if (count($subfolderParts) > 1) {
                                $remainingPath = implode('/', array_slice($subfolderParts, 1));
                                $targetPath = "{$linkedBasePath}/{$remainingPath}";
                            } else {
                                $targetPath = $linkedBasePath;
                            }
                        }
                    }

                    if (!$isLinked) {
                        $targetPath = "{$basePath}/{$subfolderPath}";
                    }
                }

                // ✅ permission fix
                $this->mountedFolderPermissionsCheck(storage_path("app/public/{$targetPath}"));

                $rawSubfolders = collect(
                    Cache::remember("dirs_{$targetPath}", 60, function () use ($targetPath) {
                        return Storage::disk('public')->directories($targetPath);
                    })
                )
                ->map(fn ($dir) => [
                    'type' => 'folder',
                    'path' => $dir,
                    'name' => basename($dir),
                    'created_at' => $this->getFolderDate($dir)->toDateTimeString(),
                    'linked' => false,
                ])
                ->toArray();

                $mountedLinkedFolders = [];
                $linkedFiles = [];
                $linkedSubfolders = [];

                $currentFolder = Folder::where('name', $folderName)
                    ->where('company_id', $companyId)
                    ->where('user_id', $realOwnerId)
                    ->first();

                // Do NOT mount links inside linked folders
                if (!$isLinkedFolder && !$subfolder) {
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

                $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

                $allFiles = Cache::remember("files_{$targetPath}", 60, function () use ($targetPath) {
                    return Storage::disk('public')->files($targetPath);
                });

                $this->subfolders = collect($rawSubfolders)
                    ->merge($mountedLinkedFolders)
                    ->merge($linkedSubfolders)
                    ->unique('path')
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                $filteredFiles = array_values(array_filter($allFiles, function ($file) use ($allowedExtensions) {
                    return in_array(
                        strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                        $allowedExtensions
                    );
                }));

                $mainFiles = collect($filteredFiles)->map(fn ($file) => [
                    'type' => match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                        'mp4' => 'video',
                        'pdf' => 'pdf',
                        default => 'image',
                    },
                    'path' => $file,
                    'name' => basename($file),
                    'created_at' => $this->getMediaDate($file)->toDateTimeString(),
                    'linked' => false,
                ]);

                $mediaAll = $mainFiles->merge($linkedFiles);
                $this->total = $mediaAll->count();

                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                    'linked' => $folder['linked'] ?? false,
                ]);

                $combined = $folderItems->merge($mediaAll)->toArray();

                $grouped = $this->groupByDate($combined);
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

    public function updatedPage()
    {
        $this->loadMediaOnly();
    }

    protected function loadMediaOnly()
    {
        if (!$this->selectedUser || !$this->selectedFolder) {
            return;
        }

        // ========================================================
        // ✅ REPLICATE TARGET PATH LOGIC FOR PAGINATION
        // ========================================================
        $pathParts = explode('/', trim($this->selectedFolder, '/'));
        $folderCompanyId = (int) ($pathParts[0] ?? auth()->user()->companies()->first()?->id);
        $realOwnerId     = (int) ($pathParts[1] ?? $this->selectedUser->id);
        $folderName      = $pathParts[2] ?? null;

        $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
        $targetPath = $basePath;

        if ($this->selectedSubfolder) {
            $subfolderParts = explode('/', $this->selectedSubfolder);
            $firstSubfolderPart = $subfolderParts[0];

            $selectedFolderModel = Folder::where('name', $folderName)
                ->where('user_id', $realOwnerId)
                ->first();

            $isLinked = false;
            
            if ($selectedFolderModel) {
                $linkedSubfolderModel = Folder::where('name', $firstSubfolderPart)
                    ->whereIn('id', function($q) use ($selectedFolderModel) {
                        $q->select('target_folder_id')
                          ->from('folder_links')
                          ->where('source_folder_id', $selectedFolderModel->id);
                    })->first();

                if ($linkedSubfolderModel) {
                    $isLinked = true;
                    $linkedBasePath = "{$linkedSubfolderModel->company_id}/{$linkedSubfolderModel->user_id}/{$linkedSubfolderModel->name}";

                    if (count($subfolderParts) > 1) {
                        $remainingPath = implode('/', array_slice($subfolderParts, 1));
                        $targetPath = "{$linkedBasePath}/{$remainingPath}";
                    } else {
                        $targetPath = $linkedBasePath;
                    }
                }
            }

            if (!$isLinked) {
                $targetPath = "{$basePath}/{$this->selectedSubfolder}";
            }
        }

        $allFiles = Cache::remember("files_{$targetPath}", 60, function () use ($targetPath) {
            return Storage::disk('public')->files($targetPath);
        });

        $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

        $filteredFiles = array_values(array_filter($allFiles, function ($file) use ($allowedExtensions) {
            return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowedExtensions);
        }));

        $mediaAll = collect($filteredFiles)->map(fn ($file) => [
            'type' => 'image',
            'path' => $file,
            'name' => basename($file),
            'created_at' => Carbon::createFromTimestamp(
                Storage::disk('public')->lastModified($file)
            )->toDateTimeString(),
        ]);

        $flat = $mediaAll->values();

        $paged = $flat->slice(
            ($this->page - 1) * $this->perPage,
            $this->perPage
        )->values();

        $this->items = $this->groupByDate($paged->toArray());
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}