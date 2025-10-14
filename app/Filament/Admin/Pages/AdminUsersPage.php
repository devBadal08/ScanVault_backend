<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Folder;
use Carbon\Carbon;
use App\Models\FolderShare;

class AdminUsersPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.admin-users-page';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?int $navigationSort = 7;

    public $managers = [];
    public $adminUsers = [];
    public $users = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $items = [];

    public $selectedManager = null;
    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    // pagination properties
    public int $perPage = 550; 
    public int $page = 1;     
    public int $total = 0;    

    protected function groupByDate(array $items): array
    {
        $lastSixDays = [];
        for ($i = 1; $i <= 6; $i++) {
            $lastSixDays[] = now()->subDays($i)->format('d-m-Y');
        }

        $groups = array_merge(
            ['Today' => []],
            array_combine($lastSixDays, array_fill(0, 6, [])),
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
            } elseif (in_array($createdDate, $lastSixDays)) {
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

    public function mount(): void
    {
        $authUser = Auth::user();
        $managerId = request()->get('manager');
        $userId = request()->get('user');
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if ($authUser->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $adminId = $authUser->id;

        // 🔹 Managers and Admin Users
        $this->managers = User::where('role', 'manager')->where('created_by', $adminId)->get();
        $this->adminUsers = User::where('role', 'user')->where('created_by', $adminId)->get();

        // 🔹 Manager selection → show their users
        if ($managerId) {
            $this->selectedManager = $this->managers->firstWhere('id', $managerId);
            $this->users = User::where('role', 'user')->where('created_by', $managerId)->get();
        } else {
            $this->users = $this->adminUsers;
        }

        // 🔹 If a user is selected → folders, subfolders, images
        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId; // top-level storage folder

            // Top-level folders (if no folder selected)
            if (!$folder) {
                // 🔹 User's own folders
                $rawFolders = collect(Storage::disk('public')->directories($baseUserPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                        'linked' => false,
                        'owner_id' => $this->selectedUser->id,
                    ])->toArray();

                // 🔹 Folders shared to this user
                $sharedToUser = FolderShare::where('shared_with', $this->selectedUser->id)
                    ->with('folder') // eager load the folder
                    ->get()
                    ->map(function ($share) {
                        $folder = $share->folder;
                        return [
                            'type' => 'folder',
                            'path' => $folder->user_id . '/' . $folder->name, // physical storage path of owner
                            'name' => $folder->name,
                            'created_at' => $folder->created_at->toDateTimeString(),
                            'linked' => true, // highlight shared
                            'owner_id' => $folder->user_id,
                        ];
                    })
                    ->toArray();

                // Merge owner's + shared folders
                $mergedFolders = collect($rawFolders)
                    ->merge($sharedToUser)
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                $this->folders = $this->groupByDate($mergedFolders);
            } else {
                // Selected folder / subfolder
                $this->selectedFolder = $folder;
                $targetPath = $subfolder ?: $folder;
                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // 🔹 Physical subfolders
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
                $linkedFolders = [];
                $selectedFolderModel = Folder::where('user_id', $this->selectedUser->id)
                    ->where('name', basename($folder))
                    ->first();

                if ($selectedFolderModel) {
                    $linkedFolders = $selectedFolderModel->linkedFolders
                        ->map(fn($f) => [
                            'type' => 'folder',
                            'path' => $f->user_id . '/' . $f->name, // full storage path
                            'name' => $f->name,
                            'created_at' => $f->created_at->toDateTimeString(),
                            'linked' => true, // highlight linked
                        ])->toArray();
                }

                // Merge physical + linked folders
                $this->subfolders = collect($rawSubfolders)
                    ->merge($linkedFolders)
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                // 🔹 Fetch media files (images + videos)
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'mp4', 'pdf'];

                $folderModel = Folder::where('name', basename($folder))
                    ->where(function($q) use ($userId) {
                        $q->where('user_id', $userId)
                        ->orWhereHas('shares', fn($q2) => $q2->where('shared_with', $userId));
                    })
                    ->first();

                $allMedia = collect();
                if ($folderModel) {
                    $allMedia = $folderModel->allPhotos()
                        ->filter(fn($photo) => in_array(strtolower(pathinfo($photo->path, PATHINFO_EXTENSION)), $allowedExtensions))
                        ->map(fn($photo) => [
                            'type' => match(strtolower(pathinfo($photo->path, PATHINFO_EXTENSION))) {
                                'mp4' => 'video',
                                'pdf' => 'pdf',
                                default => 'image',
                            },
                            'path' => $photo->path,
                            'name' => basename($photo->path),
                            'created_at' => $photo->created_at->toDateTimeString(),
                            'uploaded_by' => $photo->uploaded_by,
                        ])->values();
                }

                $this->total = $allMedia->count();

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