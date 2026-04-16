<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
                $groups['Older / Unknown Date'][] = $item;
                continue;
            }

            $created = Carbon::parse($item['created_at']);

            if ($created->isToday()) {
                $label = 'Today';
            } elseif ($created->isYesterday()) {
                $label = 'Yesterday';
            } else {
                $label = $created->format('d-m-Y');
            }

            $groups[$label][] = $item;
        }

        uksort($groups, function ($a, $b) {
            $priority = [
                'Today' => 3,
                'Yesterday' => 2,
                'Older / Unknown Date' => -1,
            ];

            $aPriority = $priority[$a] ?? 1;
            $bPriority = $priority[$b] ?? 1;

            if ($aPriority !== $bPriority) {
                return $bPriority <=> $aPriority;
            }

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

        $this->total = count($grouped);

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

        if (strlen($query) < 6) {
            $this->globalResults = [];
            return;
        }

        $authUser = Auth::user();
        $companyIds = $authUser->companies()->pluck('companies.id');

        $users = User::where('role', 'user')
            ->where('created_by', $authUser->id)
            ->select('id', 'name')
            ->get();

        if ($users->isEmpty()) {
            $this->globalResults = [];
            return;
        }

        $photos = \App\Models\Photo::whereIn('company_id', $companyIds)
            ->whereIn('user_id', $users->pluck('id'))
            ->where(function ($q) use ($query) {
                $q->where('path', 'LIKE', "%/{$query}%")
                ->orWhere('path', 'LIKE', "{$query}%");
            })
            ->select('path', 'user_id')
            ->get();

        $userMap = $users->keyBy('id');

        $results = $photos->map(function ($photo) use ($userMap, $query) {
            $parts = explode('/', $photo->path);

            $folderName = strtolower($parts[2] ?? '');
            $subfolderName = strtolower($parts[count($parts) - 2] ?? '');
            $queryClean = strtolower($query);

            $isMainMatch = str_starts_with($folderName, $queryClean);
            $isSubMatch  = str_starts_with($subfolderName, $queryClean);

            $folder = implode('/', array_slice($parts, 0, 3));
            $subPath = array_slice($parts, 3, -1);

            if ($isMainMatch) {
                return [
                    'type' => 'folder',
                    'name' => $parts[2] ?? null,
                    'user' => $userMap[$photo->user_id]->name ?? null,
                    'user_id' => $photo->user_id,
                    'folder' => $folder,
                    'subfolder' => null,
                ];
            }

            if ($isSubMatch && !empty($subPath)) {
                return [
                    'type' => 'folder',
                    'name' => $parts[count($parts) - 2] ?? null,
                    'user' => $userMap[$photo->user_id]->name ?? null,
                    'user_id' => $photo->user_id,
                    'folder' => $folder,
                    'subfolder' => implode('/', $subPath),
                ];
            }

            return null;
        })
        ->filter()
        ->unique(fn($item) => $item['folder'].'|'.$item['subfolder'])
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

        $authUser = Auth::user();
        $activeCompanyId = $authUser->companies()->first()?->id;

        $userId = trim(request()->get('user'));
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if ($authUser->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $companyIds = collect([
            $authUser->companies()->first()?->id,
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
                $q->whereNull('created_by') 
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

        if ($userId) {
            $this->selectedUser = User::select('id','name')->find($userId);
            if (!$this->selectedUser) return;
            
            // ✅ GET SHARED FOLDERS
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

            if (!$folder) {

                $photos = \App\Models\Photo::where('company_id', $activeCompanyId)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->select('path', 'created_at')
                    ->get();

                $dbFolders = $photos->map(function ($photo) {
                    $parts = explode('/', $photo->path);

                    return [
                        'type' => 'folder',
                        'name' => $parts[2] ?? null,
                        'path' => implode('/', array_slice($parts, 0, 3)),
                        'created_at' => $photo->created_at,
                        'linked' => false,
                    ];
                })
                ->filter(fn($f) => $f['name'])
                ->unique('path')
                ->toArray();

                // ✅ FIX: Merge shared folders into root view!
                $allFolders = collect($dbFolders)
                    ->merge($sharedFolders)
                    ->unique('path')
                    ->sortByDesc('created_at')
                    ->values()
                    ->toArray();

                $this->folders = $this->paginateDateGroups(
                    $this->groupByDate($allFolders)
                );

            } else {
                
                $pathParts = explode('/', trim($folder, '/'));
                $folderCompanyId = (int) ($pathParts[0] ?? $activeCompanyId);
                $realOwnerId     = (int) ($pathParts[1] ?? $userId);
                $folderName      = $pathParts[2] ?? basename($folder);

                $subfolderPath = $subfolder ? trim($subfolder, '/') : null;
                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolderPath;

                $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
                $targetPath = $basePath;

                // Query targets (Default to current folder)
                $queryCompanyId = $folderCompanyId;
                $queryUserId = $realOwnerId;
                $isLinkedSubfolder = false;

                // ========================================================
                // ✅ RESOLVE TRUE TARGET PATH FOR LINKED SUBFOLDERS
                // ========================================================
                if ($subfolderPath) {
                    $subfolderParts = explode('/', $subfolderPath);
                    $firstSubfolderPart = $subfolderParts[0];

                    $selectedFolderModel = Folder::where('name', $folderName)
                        ->where('user_id', $realOwnerId)
                        ->first();

                    if ($selectedFolderModel) {
                        $linkedSubfolderModel = Folder::where('name', $firstSubfolderPart)
                            ->whereIn('id', function($q) use ($selectedFolderModel) {
                                $q->select('target_folder_id')
                                  ->from('folder_links')
                                  ->where('source_folder_id', $selectedFolderModel->id);
                            })->first();

                        if ($linkedSubfolderModel) {
                            $isLinkedSubfolder = true;
                            // Switch query IDs to the true owner of the linked folder
                            $queryCompanyId = $linkedSubfolderModel->company_id;
                            $queryUserId = $linkedSubfolderModel->user_id;
                            
                            $linkedBasePath = "{$queryCompanyId}/{$queryUserId}/{$linkedSubfolderModel->name}";

                            if (count($subfolderParts) > 1) {
                                $remainingPath = implode('/', array_slice($subfolderParts, 1));
                                $targetPath = "{$linkedBasePath}/{$remainingPath}";
                            } else {
                                $targetPath = $linkedBasePath;
                            }
                        }
                    }

                    if (!$isLinkedSubfolder) {
                        $targetPath = "{$basePath}/{$subfolderPath}";
                    }
                }

                // ✅ GET ALL FILES FROM DB (Using the resolved Company/User)
                $photos = \App\Models\Photo::where('company_id', $queryCompanyId)
                    ->where('user_id', $queryUserId)
                    ->where('path', 'LIKE', "{$targetPath}/%")
                    ->orderBy('created_at', 'desc')
                    ->get();

                // ✅ SUBFOLDERS FROM DB
                $dbSubfolders = $photos->map(function ($photo) use ($targetPath) {
                    $relative = str_replace($targetPath . '/', '', $photo->path);
                    $parts = explode('/', $relative);

                    if (count($parts) > 1) {
                        return [
                            'type' => 'folder',
                            'name' => $parts[0],
                            'path' => $targetPath . '/' . $parts[0],
                            'created_at' => $photo->created_at,
                            'linked' => false,
                        ];
                    }

                    return null;
                })
                ->filter()
                ->unique('name')
                ->values()
                ->toArray();

                // ✅ ADD MOUNTED LINKED FOLDERS AT ROOT LEVEL
                $mountedLinkedFolders = [];
                if (!$subfolderPath) {
                    $currentFolder = Folder::where('name', $folderName)
                        ->where('user_id', $realOwnerId)
                        ->first();

                    if ($currentFolder) {
                        $mountedLinkedFolders = Folder::whereIn('id', function ($q) use ($currentFolder) {
                                $q->select('target_folder_id')
                                ->from('folder_links')
                                ->where('source_folder_id', $currentFolder->id);
                            })
                            ->get()
                            ->map(function ($f) {
                                return [
                                    'type' => 'folder',
                                    'path' => "{$f->company_id}/{$f->user_id}/{$f->name}",
                                    'name' => $f->name,
                                    'created_at' => $f->created_at->toDateTimeString(),
                                    'linked' => true,
                                    'owner_id' => $f->user_id,
                                ];
                            })
                            ->toArray();
                    }
                }

                $this->subfolders = collect($dbSubfolders)
                    ->merge($mountedLinkedFolders)
                    ->unique('name')
                    ->sortByDesc('created_at')
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

                // ✅ MERGE & PAGINATE
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
                $this->total = count($combined);
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
        // ✅ EXACT SAME RESOLUTION LOGIC FOR PAGINATION
        // ========================================================
        $pathParts = explode('/', trim($this->selectedFolder, '/'));
        $folderCompanyId = (int) ($pathParts[0] ?? auth()->user()->companies()->first()?->id);
        $realOwnerId     = (int) ($pathParts[1] ?? $this->selectedUser->id);
        $folderName      = $pathParts[2] ?? null;

        $basePath = "{$folderCompanyId}/{$realOwnerId}/{$folderName}";
        $targetPath = $basePath;

        $queryCompanyId = $folderCompanyId;
        $queryUserId = $realOwnerId;
        $isLinkedSubfolder = false;

        if ($this->selectedSubfolder) {
            $subfolderParts = explode('/', $this->selectedSubfolder);
            $firstSubfolderPart = $subfolderParts[0];

            $selectedFolderModel = Folder::where('name', $folderName)
                ->where('user_id', $realOwnerId)
                ->first();
            
            if ($selectedFolderModel) {
                $linkedSubfolderModel = Folder::where('name', $firstSubfolderPart)
                    ->whereIn('id', function($q) use ($selectedFolderModel) {
                        $q->select('target_folder_id')
                          ->from('folder_links')
                          ->where('source_folder_id', $selectedFolderModel->id);
                    })->first();

                if ($linkedSubfolderModel) {
                    $isLinkedSubfolder = true;
                    $queryCompanyId = $linkedSubfolderModel->company_id;
                    $queryUserId = $linkedSubfolderModel->user_id;

                    $linkedBasePath = "{$queryCompanyId}/{$queryUserId}/{$linkedSubfolderModel->name}";

                    if (count($subfolderParts) > 1) {
                        $remainingPath = implode('/', array_slice($subfolderParts, 1));
                        $targetPath = "{$linkedBasePath}/{$remainingPath}";
                    } else {
                        $targetPath = $linkedBasePath;
                    }
                }
            }

            if (!$isLinkedSubfolder) {
                $targetPath = "{$basePath}/{$this->selectedSubfolder}";
            }
        }

        // ✅ REPLICATE DB FETCHING FOR PAGINATION
        $photos = \App\Models\Photo::where('company_id', $queryCompanyId)
            ->where('user_id', $queryUserId)
            ->where('path', 'LIKE', "{$targetPath}/%")
            ->orderBy('created_at', 'desc')
            ->get();

        $dbSubfolders = $photos->map(function ($photo) use ($targetPath) {
            $relative = str_replace($targetPath . '/', '', $photo->path);
            $parts = explode('/', $relative);
            if (count($parts) > 1) {
                return [
                    'type' => 'folder',
                    'name' => $parts[0],
                    'path' => $targetPath . '/' . $parts[0],
                    'created_at' => $photo->created_at,
                    'linked' => false,
                ];
            }
            return null;
        })->filter()->unique('name')->values()->toArray();

        $mountedLinkedFolders = [];
        if (!$this->selectedSubfolder) {
            $currentFolder = Folder::where('name', $folderName)->where('user_id', $realOwnerId)->first();
            if ($currentFolder) {
                $mountedLinkedFolders = Folder::whereIn('id', function ($q) use ($currentFolder) {
                        $q->select('target_folder_id')->from('folder_links')->where('source_folder_id', $currentFolder->id);
                    })->get()->map(function ($f) {
                        return [
                            'type' => 'folder',
                            'path' => "{$f->company_id}/{$f->user_id}/{$f->name}",
                            'name' => $f->name,
                            'created_at' => $f->created_at->toDateTimeString(),
                            'linked' => true,
                            'owner_id' => $f->user_id,
                        ];
                    })->toArray();
            }
        }

        $allFolders = collect($dbSubfolders)->merge($mountedLinkedFolders)->unique('name')->sortByDesc('created_at')->values()->toArray();

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

        $folderItems = collect($allFolders)->map(fn ($folder) => [
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
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}