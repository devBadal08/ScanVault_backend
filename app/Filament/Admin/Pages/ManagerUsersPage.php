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

    public function canDeleteUserPhotos(int $userId): bool
    {
        if (!$this->canDeletePhotos()) {
            return false;
        }

        $companyId = Auth::user()->companies()->first()?->id;

        if (!$companyId) {
            return false;
        }

        // If user has any photo which is NOT backed up,
        // deletion is not allowed.
        return !Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('backed_up_at')
            ->exists();
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

                // Update user counter
                $user = User::find($userId);

                if ($user) {
                    $user->total_photos = max(
                        0,
                        $user->total_photos - 1
                    );

                    $user->save();
                }
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
                /*
                |--------------------------------------------------------------------------
                | Get company/user from actual folder path
                |--------------------------------------------------------------------------
                */

                $pathParts = explode('/', trim($path, '/'));

                $folderCompanyId = (int) ($pathParts[0] ?? 0);
                $folderUserId    = (int) ($pathParts[1] ?? 0);

                if (!$folderCompanyId || !$folderUserId) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Count photos inside folder
                |--------------------------------------------------------------------------
                */

                $folderPhotos = Photo::where('company_id', $folderCompanyId)
                    ->where('user_id', $folderUserId)
                    ->where('path', 'LIKE', $path . '/%')
                    ->get(['id', 'path']);

                $folderPhotoCount = $folderPhotos->count();

                /*
                |--------------------------------------------------------------------------
                | Calculate folder size
                |--------------------------------------------------------------------------
                */

                $folderSizeMB = 0;

                foreach ($folderPhotos as $photo) {
                    if (Storage::disk('public')->exists($photo->path)) {
                        $folderSizeMB += Storage::disk('public')->size($photo->path)
                            / (1024 * 1024);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Delete physical folder
                |--------------------------------------------------------------------------
                */

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->deleteDirectory($path);
                }

                /*
                |--------------------------------------------------------------------------
                | Delete all photos inside folder/subfolders
                |--------------------------------------------------------------------------
                */

                Photo::where('company_id', $folderCompanyId)
                    ->where('user_id', $folderUserId)
                    ->where('path', 'LIKE', $path . '/%')
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Delete folder database records
                |--------------------------------------------------------------------------
                */
                $folderCount = Folder::where('company_id', $folderCompanyId)
                    ->where('user_id', $folderUserId)
                    ->where(function ($query) use ($path) {
                        $query->where('path', $path)
                            ->orWhere('path', 'LIKE', $path . '/%');
                    })
                    ->count();

                Folder::where('company_id', $folderCompanyId)
                    ->where('user_id', $folderUserId)
                    ->where(function ($query) use ($path) {
                        $query->where('path', $path)
                            ->orWhere('path', 'LIKE', $path . '/%');
                    })
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Update company counters
                |--------------------------------------------------------------------------
                */

                $company = \App\Models\Company::find($folderCompanyId);

                if ($company) {

                    $company->used_storage_mb = max(
                        0,
                        $company->used_storage_mb - $folderSizeMB
                    );

                    $company->total_photos = max(
                        0,
                        $company->total_photos - $folderPhotoCount
                    );

                    $company->total_folders = max(
                        0,
                        $company->total_folders - $folderCount
                    );

                    $company->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Update user folder counter
                |--------------------------------------------------------------------------
                */

                // Update user counters
                $user = User::find($folderUserId);

                if ($user) {

                    $user->total_photos = max(
                        0,
                        $user->total_photos - $folderPhotoCount
                    );

                    $user->total_folders = max(
                        0,
                        $user->total_folders - $folderCount
                    );

                    $user->save();
                }
                /*
                |--------------------------------------------------------------------------
                | Clear cache
                |--------------------------------------------------------------------------
                */

                Cache::forget("files_{$path}");

                /*
                |--------------------------------------------------------------------------
                | Remove folder from UI
                |--------------------------------------------------------------------------
                */

                foreach ($this->folders as $group => $folderGroup) {

                    $this->folders[$group] = array_filter(
                        $folderGroup,
                        function ($folder) use ($path) {
                            return $folder['path'] !== $path;
                        }
                    );

                    if (empty($this->folders[$group])) {
                        unset($this->folders[$group]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Remove folder contents from UI
                |--------------------------------------------------------------------------
                */

                foreach ($this->items as $group => $itemGroup) {

                    $this->items[$group] = array_filter(
                        $itemGroup,
                        function ($item) use ($path) {
                            return !str_starts_with(
                                $item['path'],
                                $path . '/'
                            );
                        }
                    );

                    if (empty($this->items[$group])) {
                        unset($this->items[$group]);
                    }
                }

                continue;
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

                        $user = User::find($userId);

                    if ($user) {
                        $user->total_photos = max(
                            0,
                            $user->total_photos - 1
                        );

                        $user->save();
                    }
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

    public function deleteUserPhotos($userId)
    {
        if (!$this->canDeletePhotos()) {
            abort(403);
        }

        $authUser = Auth::user();

        $companyId = $authUser->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Company not found.');
        }

        $user = User::find($userId);

        if (!$user) {
            abort(404, 'User not found.');
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT: Check backup status
        |--------------------------------------------------------------------------
        */

        $unbackedPhotos = Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('backed_up_at')
            ->count();

        if ($unbackedPhotos > 0) {

            \Filament\Notifications\Notification::make()
                ->title('Backup Required')
                ->body(
                    "Cannot delete {$user->name}. {$unbackedPhotos} photo(s) have not been backed up yet."
                )
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get ALL photos of this user
        |--------------------------------------------------------------------------
        */

        $photos = Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->get(['id', 'path']);

        $totalSizeMB = 0;
        $deletedPhotos = $photos->count();

        foreach ($photos as $photo) {

            if (Storage::disk('public')->exists($photo->path)) {

                // Get size before deleting
                $totalSizeMB += Storage::disk('public')->size($photo->path)
                    / (1024 * 1024);

                /*
                |--------------------------------------------------------------------------
                | Delete history
                |--------------------------------------------------------------------------
                */

                PhotoDeleteHistory::create([
                    'deleted_by' => auth()->id(),
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'photo_path' => $photo->path,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Delete physical file
                |--------------------------------------------------------------------------
                */

                Storage::disk('public')->delete($photo->path);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Delete all physical folders
        |--------------------------------------------------------------------------
        */

        $folders = Folder::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->get(['id', 'path']);

        $deletedFolders = $folders->count();

        foreach ($folders as $folder) {

            if (
                !empty($folder->path) &&
                Storage::disk('public')->exists($folder->path)
            ) {
                Storage::disk('public')->deleteDirectory($folder->path);
            }

            Cache::forget("files_{$folder->path}");
        }

        /*
        |--------------------------------------------------------------------------
        | Delete database records
        |--------------------------------------------------------------------------
        */

        Photo::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->delete();

        Folder::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Update company storage
        |--------------------------------------------------------------------------
        */

        $company = \App\Models\Company::find($companyId);

        if ($company) {

            $company->used_storage_mb = max(
                0,
                $company->used_storage_mb - $totalSizeMB
            );

            $company->total_photos = max(
                0,
                $company->total_photos - $deletedPhotos
            );

            $company->total_folders = max(
                0,
                $company->total_folders - $deletedFolders
            );

            $company->save();
        }

        // Update user counters
        $user->total_photos = max(
            0,
            $user->total_photos - $deletedPhotos
        );

        $user->total_folders = max(
            0,
            $user->total_folders - $deletedFolders
        );

        $user->save();

        /*
        |--------------------------------------------------------------------------
        | Update user card count immediately
        |--------------------------------------------------------------------------
        */

        foreach ($this->managerUsers as $item) {

            if ((int) $item->id === (int) $userId) {
                $item->photo_count = 0;
            }
        }

        session()->flash(
            'success',
            "All photos and folders of {$user->name} have been deleted successfully."
        );
    }

    public function mount(): void
    {
        $this->page = max(1, (int) request()->get('page', 1));

        if ($this->isSearching) {
            return;
        }

        $authUser = Auth::user();
        $userId = request()->get('user');

        $companyId = request()->get('company_id')
            ?? $authUser->companies()->first()?->id;

        /*
        |--------------------------------------------------------------------------
        | Validate selected user
        |--------------------------------------------------------------------------
        */

        if ($userId) {
            $this->selectedUser = User::find($userId);

            if (!$this->selectedUser) {
                return;
            }

            if (!$companyId) {
                abort(403, 'Company not found');
            }
        }

        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        /*
        |--------------------------------------------------------------------------
        | Reset state
        |--------------------------------------------------------------------------
        */

        $this->selectedFolder = null;
        $this->selectedSubfolder = null;
        $this->folders = [];
        $this->subfolders = [];
        $this->items = [];
        $this->images = [];
        $this->total = 0;

        /*
        |--------------------------------------------------------------------------
        | Permission
        |--------------------------------------------------------------------------
        */

        if (!in_array($authUser->role, ['manager', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $companyId = $authUser->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Company not found for this user');
        }

        /*
        |--------------------------------------------------------------------------
        | Load manager users
        |--------------------------------------------------------------------------
        */

        if ($authUser->role === 'manager') {

            $this->managerUsers = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get()
                ->map(function ($user) use ($companyId) {
                    $user->photo_count = $this->getUserPhotoCount(
                        $companyId,
                        $user->id
                    );

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
                    $user->photo_count = $this->getUserPhotoCount(
                        $companyId,
                        $user->id
                    );

                    return $user;
                });
        }

        /*
        |--------------------------------------------------------------------------
        | No selected user
        |--------------------------------------------------------------------------
        */

        if (!$userId) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure selected user is available
        |--------------------------------------------------------------------------
        */

        $this->selectedUser = User::find($userId);

        if (!$this->selectedUser) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | USER ROOT LEVEL
        |
        | IMPORTANT:
        | Do NOT load all photos.
        |
        | Before:
        |     Photo::...->get()
        |
        | User 24 has 92,992 photos, which caused the 128 MB
        | PHP memory limit to be exhausted.
        |
        | Instead, MySQL returns only one row per main folder.
        |--------------------------------------------------------------------------
        */

        if (!$folder) {

            $folderRows = Photo::query()
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->selectRaw("
                    SUBSTRING_INDEX(path, '/', 3) AS folder_path,
                    MAX(COALESCE(captured_at, created_at)) AS created_at
                ")
                ->whereNotNull('path')
                ->where('path', '<>', '')
                ->groupByRaw("SUBSTRING_INDEX(path, '/', 3)")
                ->orderByDesc('created_at')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Convert folder rows into existing UI structure
            |--------------------------------------------------------------------------
            */

            $rawFolders = $folderRows
                ->map(function ($folderRow) {

                    $folderPath = trim($folderRow->folder_path);

                    $parts = explode('/', $folderPath);

                    return [
                        'type' => 'folder',
                        'name' => $parts[2] ?? null,
                        'path' => $folderPath,
                        'created_at' => $folderRow->created_at,
                        'linked' => false,
                    ];
                })
                ->filter(fn ($folder) => !empty($folder['name']))
                ->unique('path')
                ->values()
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Group folders by date
            |--------------------------------------------------------------------------
            */

            $grouped = $this->groupByDate($rawFolders);

            /*
            |--------------------------------------------------------------------------
            | Date pagination
            |--------------------------------------------------------------------------
            */

            $this->folders = $this->paginateDateGroups($grouped);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | OPEN FOLDER
        |--------------------------------------------------------------------------
        */

        $pathParts = explode('/', trim($folder, '/'));

        $folderCompanyId = (int) ($pathParts[0] ?? $companyId);
        $realOwnerId = (int) ($pathParts[1] ?? $userId);
        $folderName = $pathParts[2] ?? null;

        $extraPath = array_slice($pathParts, 3);

        $fromSearch = request()->get('from_search');

        if ($fromSearch && !$subfolder && !empty($extraPath)) {
            $subfolder = implode('/', $extraPath);
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve subfolder
        |--------------------------------------------------------------------------
        */

        $subfolderPath = null;

        if ($subfolder) {
            $subfolderPath = trim($subfolder, '/');
        }

        $this->selectedFolder = $folder;
        $this->selectedSubfolder = $subfolderPath;

        /*
        |--------------------------------------------------------------------------
        | Get selected folder model
        |--------------------------------------------------------------------------
        */

        $selectedFolderModel = Folder::where('path', $folder)
            ->where('company_id', $folderCompanyId)
            ->where('user_id', $realOwnerId)
            ->first();

        $isLinkedFolder = false;

        if ($selectedFolderModel) {

            $isLinkedFolder = DB::table('folder_links')
                ->where('target_folder_id', $selectedFolderModel->id)
                ->exists();
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve true target path for linked folders
        |--------------------------------------------------------------------------
        */

        $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
        $targetPath = $basePath;

        if ($subfolderPath) {

            $subfolderParts = explode('/', $subfolderPath);
            $firstSubfolderPart = $subfolderParts[0];

            $isLinked = false;

            if ($selectedFolderModel) {

                $linkedSubfolderModel = Folder::where(
                        'name',
                        $firstSubfolderPart
                    )
                    ->whereIn('id', function ($q) use ($selectedFolderModel) {
                        $q->select('target_folder_id')
                            ->from('folder_links')
                            ->where(
                                'source_folder_id',
                                $selectedFolderModel->id
                            );
                    })
                    ->first();

                if ($linkedSubfolderModel) {

                    $isLinked = true;

                    $linkedBasePath =
                        "{$linkedSubfolderModel->company_id}/" .
                        "{$linkedSubfolderModel->user_id}/" .
                        "{$linkedSubfolderModel->name}";

                    if (count($subfolderParts) > 1) {

                        $remainingPath = implode(
                            '/',
                            array_slice($subfolderParts, 1)
                        );

                        $targetPath =
                            "{$linkedBasePath}/{$remainingPath}";

                    } else {

                        $targetPath = $linkedBasePath;
                    }
                }
            }

            if (!$isLinked) {
                $targetPath = "{$basePath}/{$subfolderPath}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Permission fix
        |--------------------------------------------------------------------------
        */

        $this->mountedFolderPermissionsCheck(
            storage_path("app/public/{$targetPath}")
        );

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT MEMORY OPTIMIZATION
        |
        | Do NOT do:
        |
        |     Photo::...->get()
        |
        | because a folder can contain thousands of photos.
        |
        | We separately query:
        |
        | 1. Immediate subfolders
        | 2. Direct files
        |--------------------------------------------------------------------------
        */

        $targetPath = trim($targetPath, '/');
        $pathPrefix = $targetPath . '/';

        /*
        |--------------------------------------------------------------------------
        | Calculate path depth
        |--------------------------------------------------------------------------
        */

        $targetDepth = substr_count($targetPath, '/') + 1;
        $childDepth = $targetDepth + 1;

        /*
        |--------------------------------------------------------------------------
        | Get immediate subfolders only
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | target:
        | 3/24/PK26030070 MES PIPING
        |
        | We only need:
        |
        | 3/24/PK26030070 MES PIPING/GT26020550
        |
        | Not every photo inside that subfolder.
        |--------------------------------------------------------------------------
        */

        $subfolderExpression =
            "SUBSTRING_INDEX(path, '/', {$childDepth})";

        $subfolderRows = Photo::query()
            ->where('company_id', $folderCompanyId)
            ->where('user_id', $realOwnerId)
            ->where('path', 'LIKE', $pathPrefix . '%')
            ->whereRaw(
                "LENGTH(path) - LENGTH(REPLACE(path, '/', '')) > ?",
                [$targetDepth]
            )
            ->selectRaw("
                {$subfolderExpression} AS subfolder_path,
                MAX(COALESCE(captured_at, created_at)) AS created_at
            ")
            ->groupByRaw($subfolderExpression)
            ->orderByDesc('created_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Convert subfolders
        |--------------------------------------------------------------------------
        */

        $rawSubfolders = $subfolderRows
            ->map(function ($row) use ($targetPath) {

                $fullPath = trim($row->subfolder_path, '/');

                $parts = explode('/', $fullPath);

                return [
                    'type' => 'folder',
                    'name' => end($parts),
                    'path' => $fullPath,
                    'created_at' => $row->created_at,
                    'linked' => false,
                ];
            })
            ->filter(fn ($folder) => !empty($folder['name']))
            ->unique('path')
            ->values()
            ->toArray();

        $this->subfolders = $rawSubfolders;

        /*
        |--------------------------------------------------------------------------
        | Linked folders
        |--------------------------------------------------------------------------
        */

        $mountedLinkedFolders = [];
        $linkedFiles = [];
        $linkedSubfolders = [];

        $currentFolder = Folder::where('path', $folder)
            ->where('company_id', $folderCompanyId)
            ->where('user_id', $realOwnerId)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Mount linked folders
        |--------------------------------------------------------------------------
        */

        if (!$isLinkedFolder && !$subfolder) {

            if ($currentFolder) {

                $mountedLinkedFolders = Folder::whereIn(
                    'id',
                    function ($q) use ($currentFolder) {

                        $q->select('target_folder_id')
                            ->from('folder_links')
                            ->where(
                                'source_folder_id',
                                $currentFolder->id
                            );
                    }
                )
                    ->get()
                    ->map(function ($folder) {

                        $folderPath =
                            "{$folder->company_id}/" .
                            "{$folder->user_id}/" .
                            "{$folder->name}";

                        $latestDate = Photo::where('company_id', $folder->company_id)
                            ->where('user_id', $folder->user_id)
                            ->where('path', 'LIKE', $folderPath . '/%')
                            ->selectRaw('MAX(COALESCE(captured_at, created_at)) as latest_date')
                            ->value('latest_date');

                        return [
                            'type' => 'folder',
                            'path' => $folderPath,
                            'name' => $folder->name,
                            'created_at' => $latestDate,
                            'linked' => true,
                            'owner_id' => $folder->user_id,
                        ];
                    })
                    ->toArray();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Get DIRECT files
        |--------------------------------------------------------------------------
        |
        | We need enough direct files for the current combined page.
        | We do NOT use SQL offset here because folders and files
        | must be paginated together.
        |--------------------------------------------------------------------------
        */

        $requiredDirectFiles = $this->page * $this->perPage;

        $directFiles = Photo::query()
            ->where('company_id', $folderCompanyId)
            ->where('user_id', $realOwnerId)
            ->where('path', 'LIKE', $pathPrefix . '%')
            ->whereRaw(
                "LENGTH(path) - LENGTH(REPLACE(path, '/', '')) = ?",
                [$targetDepth]
            )
            ->orderByRaw('COALESCE(captured_at, created_at) DESC')
            ->select([
                'id',
                'type',
                'path',
                'created_at',
                'captured_at',
            ])
            ->limit($requiredDirectFiles)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Convert direct files
        |--------------------------------------------------------------------------
        */

        $mediaAll = $directFiles
            ->map(function ($photo) {
                return [
                    'type' => $photo->type,
                    'path' => $photo->path,
                    'name' => basename($photo->path),
                    'created_at' => $photo->captured_at ?? $photo->created_at,
                    'linked' => false,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Add linked files if any
        |--------------------------------------------------------------------------
        */

        $mediaAll = $mediaAll->merge($linkedFiles);

        /*
        |--------------------------------------------------------------------------
        | Total direct media count
        |--------------------------------------------------------------------------
        */

        $directMediaTotal = Photo::query()
            ->where('company_id', $folderCompanyId)
            ->where('user_id', $realOwnerId)
            ->where('path', 'LIKE', $pathPrefix . '%')
            ->whereRaw(
                "LENGTH(path) - LENGTH(REPLACE(path, '/', '')) = ?",
                [$targetDepth]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Combine normal folders
        |--------------------------------------------------------------------------
        */

        $folderItems = collect($this->subfolders)
            ->map(fn ($folder) => [
                'type' => 'folder',
                'path' => $folder['path'],
                'name' => $folder['name'],
                'created_at' => $folder['created_at'],
                'linked' => $folder['linked'] ?? false,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Add mounted linked folders
        |--------------------------------------------------------------------------
        */

        if (!empty($mountedLinkedFolders)) {
            $folderItems = $folderItems->merge(
                collect($mountedLinkedFolders)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Combine folders + files
        |--------------------------------------------------------------------------
        */

        $combined = $folderItems
            ->merge($mediaAll)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Sort ALL items by created_at
        |--------------------------------------------------------------------------
        |
        | This is important because folders and files now share
        | the same pagination.
        |--------------------------------------------------------------------------
        */

        $combined = $combined
            ->filter(fn ($item) => !empty($item['created_at']))
            ->sortByDesc(function ($item) {
                return Carbon::parse($item['created_at'])->timestamp;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | TOTAL ITEMS
        |--------------------------------------------------------------------------
        |
        | Pagination now represents:
        |
        |   normal subfolders
        | + linked folders
        | + direct files
        |--------------------------------------------------------------------------
        */

        $this->total =
            count($this->subfolders)
            + count($mountedLinkedFolders)
            + $directMediaTotal;

        /*
        |--------------------------------------------------------------------------
        | Apply pagination to COMBINED items
        |--------------------------------------------------------------------------
        */

        $paged = $combined
            ->slice(
                ($this->page - 1) * $this->perPage,
                $this->perPage
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Final Livewire state
        |--------------------------------------------------------------------------
        */

        $this->items = $this->groupByDate(
            $paged->toArray()
        );

        $this->images = $paged->toArray();
    }

    // public function updatedPage()
    // {
    //     $this->loadMediaOnly();
    // }

    // protected function loadMediaOnly()
    // {
    //     if (!$this->selectedUser || !$this->selectedFolder) {
    //         return;
    //     }

    //     // ========================================================
    //     // ✅ REPLICATE TARGET PATH LOGIC FOR PAGINATION
    //     // ========================================================
    //     $pathParts = explode('/', trim($this->selectedFolder, '/'));
    //     $folderCompanyId = (int) ($pathParts[0] ?? auth()->user()->companies()->first()?->id);
    //     $realOwnerId     = (int) ($pathParts[1] ?? $this->selectedUser->id);
    //     $folderName      = $pathParts[2] ?? null;

    //     $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
    //     $targetPath = $basePath;

    //     if ($this->selectedSubfolder) {
    //         $subfolderParts = explode('/', $this->selectedSubfolder);
    //         $firstSubfolderPart = $subfolderParts[0];

    //         $selectedFolderModel = Folder::where('name', $folderName)
    //             ->where('user_id', $realOwnerId)
    //             ->first();

    //         $isLinked = false;
            
    //         if ($selectedFolderModel) {
    //             $linkedSubfolderModel = Folder::where('name', $firstSubfolderPart)
    //                 ->whereIn('id', function($q) use ($selectedFolderModel) {
    //                     $q->select('target_folder_id')
    //                       ->from('folder_links')
    //                       ->where('source_folder_id', $selectedFolderModel->id);
    //                 })->first();

    //             if ($linkedSubfolderModel) {
    //                 $isLinked = true;
    //                 $linkedBasePath = "{$linkedSubfolderModel->company_id}/{$linkedSubfolderModel->user_id}/{$linkedSubfolderModel->name}";

    //                 if (count($subfolderParts) > 1) {
    //                     $remainingPath = implode('/', array_slice($subfolderParts, 1));
    //                     $targetPath = "{$linkedBasePath}/{$remainingPath}";
    //                 } else {
    //                     $targetPath = $linkedBasePath;
    //                 }
    //             }
    //         }

    //         if (!$isLinked) {
    //             $targetPath = "{$basePath}/{$this->selectedSubfolder}";
    //         }
    //     }

    //     $allFiles = Cache::remember("files_{$targetPath}", 60, function () use ($targetPath) {
    //         return Storage::disk('public')->files($targetPath);
    //     });

    //     $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

    //     $filteredFiles = array_values(array_filter($allFiles, function ($file) use ($allowedExtensions) {
    //         return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowedExtensions);
    //     }));

    //     $mediaAll = collect($filteredFiles)->map(fn ($file) => [
    //         'type' => 'image',
    //         'path' => $file,
    //         'name' => basename($file),
    //         'created_at' => Carbon::createFromTimestamp(
    //             Storage::disk('public')->lastModified($file)
    //         )->toDateTimeString(),
    //     ]);

    //     $flat = $mediaAll->values();

    //     $paged = $flat->slice(
    //         ($this->page - 1) * $this->perPage,
    //         $this->perPage
    //     )->values();

    //     $this->items = $this->groupByDate($paged->toArray());
    // }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}