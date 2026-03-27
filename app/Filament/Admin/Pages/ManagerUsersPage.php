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

    public int $perPage = 1;
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

        // ✅ Minimum 8 characters condition
        if (strlen($query) < 8) {
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
                        ->where('name', 'LIKE', "%{$query}%")
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
            ->where('company_id', $companyId)
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

                // ✅ FOLDERS FROM DB
                $photos = Photo::where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(1000) // prevent huge load
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

                // Normalize folder path
                $pathParts = explode('/', trim($folder, '/'));

                $folderCompanyId = (int) ($pathParts[0] ?? $companyId);
                $realOwnerId     = (int) ($pathParts[1] ?? $userId);
                $folderName      = $pathParts[2] ?? basename($folder);

                $subfolderPath = $subfolder ? trim($subfolder, '/') : null;

                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolderPath;

                // Build DB path
                $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
                $targetPath = $subfolderPath
                    ? "{$basePath}/{$subfolderPath}"
                    : $basePath;

                // ✅ GET ALL FILES FROM DB
                $photos = Photo::where('company_id', $companyId)
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

                $this->total = $mediaAll->count();

                // ✅ MERGE folders + files
                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                ]);

                $combined = $folderItems->merge($mediaAll)->toArray();

                // ✅ KEEP YOUR PAGINATION SAME
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

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}