<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Folder;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;
use App\Models\PhotoDeleteHistory;

class ManagerUsersPage extends Page
{
    protected static string $view = 'filament.admin.pages.manager-users-page';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        $user = Auth::user();

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
    public string $globalSearch = '';
    public array $globalResults = [];
    public bool $isSearching = false;

    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    public int $perPage = 30;
    public int $page = 1;
    public int $total = 0;
    public int $datesPerPage = 6;
    protected $listeners = [
        'bulkDeleteMedia' => 'bulkDeleteMedia'
    ];

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
                $item['created_at'] = now(); // fallback so it doesn't skip
            }

            // ✅ FORCE correct timezone
            $created = \Carbon\Carbon::parse($item['created_at'])
                ->setTimezone(config('app.timezone'));

            if ($created->isToday()) {
                $label = 'Today';
            } elseif ($created->isYesterday()) {
                $label = 'Yesterday';
            } else {
                $label = $created->format('d-m-Y');
            }

            $groups[$label][] = $item;
        }

        // ✅ FIX SORTING (IMPORTANT)
        uksort($groups, function ($a, $b) {
            $order = ['Today' => 0, 'Yesterday' => 1];

            $aOrder = $order[$a] ?? 2;
            $bOrder = $order[$b] ?? 2;

            if ($aOrder !== $bOrder) {
                return $aOrder <=> $bOrder;
            }

            if ($aOrder === 2 && $bOrder === 2) {
                return \Carbon\Carbon::createFromFormat('d-m-Y', $b)
                    <=> \Carbon\Carbon::createFromFormat('d-m-Y', $a);
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

        // total DATE groups (important for pagination UI)
        $this->total = count($keys);

        return $result;
    }

    public function searchGlobal(): void
    {
        $query = trim(strtolower($this->globalSearch));

        if ($query === '') {
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
                        ->whereRaw('LOWER(name) = ?', [$query])
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

        $companyId = request()->get('company_id') 
            ?? $authUser->companies()->first()?->id;

        if (!$companyId) {
            abort(403, 'Company not found for this user');
        }

        $photoCounts = cache()->remember(
            "photo_counts_{$companyId}",
            60,
            function () use ($companyId) {
                return Photo::select('user_id', DB::raw('COUNT(*) as total'))
                    ->where('company_id', $companyId)
                    ->where('type', 'image')
                    ->groupBy('user_id')
                    ->pluck('total','user_id');
            }
        );

        if ($authUser->role === 'manager') {
            $this->managerUsers = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get()
                ->map(function ($user) use ($photoCounts) {
                    $user->photo_count = $photoCounts[$user->id] ?? 0;
                    return $user;
                });
        } else {

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $this->managerUsers = User::where('role', 'user')
                ->whereIn('created_by', $managerIds)
                ->get()
                ->map(function ($user) use ( $photoCounts) {
                    $user->photo_count = $photoCounts[$user->id] ?? 0;
                    return $user;
                });
        }

        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = "{$companyId}/{$userId}";

            if (!$folder) {

                $rawFolders = Folder::where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->whereNull('parent_id')
                    ->orderByDesc('created_at')
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

                // $allFolders = collect($rawFolders)
                //     ->unique('path')
                //     ->sortByDesc(fn ($i) => $i['created_at'])
                //     ->values();

                $paginatedFolders = $rawFolders
                    ->slice(($this->page - 1) * $this->perPage, $this->perPage)
                    ->values();

                $this->total = $rawFolders->count();

                $this->folders = $this->groupByDate($paginatedFolders->toArray());

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

                $rawSubfolders = [];

                $currentFolder = Folder::select('id','path','user_id')
                    ->where('path', trim($targetPath, '/'))
                    ->first();

                $rawSubfolders = [];

                if ($currentFolder) {
                    $rawSubfolders = Folder::where('parent_id', $currentFolder->id)
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
                }

                // Load folders linked FROM this folder (mounted links)
                $mountedLinkedFolders = [];

                if (!$isLinkedFolder && !$subfolder) {

                    if ($currentFolder) {
                        $mountedLinkedFolders = Folder::whereIn('id', function ($q) use ($currentFolder) {
                                $q->select('target_folder_id')
                                ->from('folder_links')
                                ->where('source_folder_id', $currentFolder->id);
                            })
                            ->limit(50)
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

                $allFiles = collect();

                if ($currentFolder) {
                    $allFiles = Photo::where('folder_id', $currentFolder->id)
                        ->whereIn('type', ['image','video','pdf'])
                        ->orderByDesc('created_at')
                        ->skip(($this->page - 1) * $this->perPage)
                        ->take($this->perPage)
                        ->get();
                }

                $this->total = Photo::where('folder_id', $currentFolder->id)
                    ->whereIn('type', ['image','video','pdf'])
                    ->count();

                $mediaAll = $allFiles->map(fn ($file) => [
                    'type' => $file->type,
                    'path' => $file->path,
                    'name' => basename($file->path),
                    'created_at' => $file->created_at,
                ]);

                $folderItems = collect($this->subfolders)->map(fn ($folder) => [
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'name' => $folder['name'],
                    'created_at' => $folder['created_at'],
                    'linked' => $folder['linked'] ?? false,
                ]);

                // 🔥 LIMIT DATA (VERY IMPORTANT)
                $combined = $folderItems
                    ->merge($mediaAll)
                    ->take(200)
                    ->toArray();

                $this->items = $this->groupByDate($combined);
                $this->images = $mediaAll->toArray();
            }
        }
    }

    public function updatedPage()
    {
        $this->mount();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}