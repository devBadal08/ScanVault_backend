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

        // if ($user->role === 'admin') {
        //     return true;
        // }

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

        // ✅ Minimum 6 characters condition
        if (strlen($query) < 6) {
            $this->globalResults = [];
            return;
        }

        $this->isSearching = true;
        $results = [];

        $authUser = Auth::user();

        // ✅ Manager can ONLY search their own users
        $users = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->select('id', 'name')
            ->get();

        if ($users->isEmpty()) {
            $this->globalResults = [];
            $this->isSearching = false;
            return;
        }

        $companyIds = $authUser->companies()->pluck('companies.id');

        foreach ($users as $user) {
            foreach ($companyIds as $companyId) {

                $basePath = "{$companyId}/{$user->id}";

                $results = array_merge(
                    $results,
                    Folder::where('company_id', $companyId)
                        ->where('user_id', $user->id)
                        ->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                        ->limit(50)
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
                        'created_at' => $this->getFolderDate($dir)->toDateTimeString(),
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

                $allFolders = collect($rawFolders)
                    ->unique('path')
                    ->sortByDesc(fn ($i) => $i['created_at'])
                    ->values();

                // ✅ group EVERYTHING first
                $grouped = $this->groupByDate($allFolders->toArray());

                // ✅ paginate DATE GROUPS (3 per page)
                $this->folders = $this->paginateDateGroups($grouped);

            } else {

                // Normalize folder and subfolder to names only
                $pathParts = explode('/', trim($folder, '/'));

                $folderCompanyId = (int) ($pathParts[0] ?? $companyId);
                $realOwnerId     = (int) ($pathParts[1] ?? $userId);
                $folderName      = $pathParts[2] ?? basename($folder);

                $subfolderPath = $subfolder ? trim($subfolder, '/') : null;

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

                $linkedSubfolderModel = null;

                if ($subfolder) {
                    $linkedSubfolderModel = Folder::where('name', $subfolderPath)
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
                $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";

                $targetPath = $subfolderPath
                    ? "{$basePath}/{$subfolderPath}"
                    : $basePath;

                // ✅ permission fix
                $this->mountedFolderPermissionsCheck(storage_path("app/public/{$targetPath}"));

                $rawSubfolders = [];

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

                $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

                $allFiles = Cache::remember("files_{$targetPath}", 60, function () use ($targetPath) {
                    return Storage::disk('public')->files($targetPath);
                });

                // ✅ FILTER FIRST
                $filteredFiles = array_values(array_filter($allFiles, function ($file) use ($allowedExtensions) {
                    return in_array(
                        strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                        $allowedExtensions
                    );
                }));

                // ✅ MAP
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

                $this->total = $mediaAll->count();

                // ✅ MERGE folders + media BEFORE pagination
                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                    'linked' => $folder['linked'] ?? false,
                ]);

                $combined = $folderItems->merge($mediaAll)->toArray();

                // ✅ GROUP → FLATTEN → SLICE → REGROUP
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

        $targetPath = $this->selectedSubfolder
            ? "{$this->selectedFolder}/{$this->selectedSubfolder}"
            : $this->selectedFolder;

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