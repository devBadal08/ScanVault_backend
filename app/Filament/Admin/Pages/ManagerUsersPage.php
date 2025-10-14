<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Folder;
use App\Models\FolderShare;

class ManagerUsersPage extends Page
{
    protected static string $view = 'filament.admin.pages.manager-users-page';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Manager Users';
    protected static ?int $navigationSort = 8;

    public $managerUsers = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $users = [];
    public $items = [];

    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    public int $perPage = 550;
    public int $page = 1;
    public int $total = 0;

    protected function groupByDate(array $items): array
    {
        $lastThreeDays = [];
        for ($i = 1; $i <= 3; $i++) {
            $lastThreeDays[] = now()->subDays($i)->format('d-m-Y');
        }

        $groups = array_merge(
            ['Today' => []],
            array_combine($lastThreeDays, array_fill(0, 3, [])),
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
            } elseif (in_array($createdDate, $lastThreeDays)) {
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
        $userId = request()->get('user');
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if (!in_array($authUser->role, ['manager', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        // Manager sees only their own users
        if ($authUser->role === 'manager') {
            $this->managerUsers = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get();
        } 
        // Admin can see all users under managers they created
        else {
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $this->managerUsers = User::where('role', 'user')
                ->whereIn('created_by', $managerIds)
                ->get();
        }

        // -------------------------------
        // Selected user → show folders/media
        // -------------------------------
        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId;

            // 🔹 Top-level folders
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
            } 
            // 🔹 Inside folder / subfolder
            else {
                $this->selectedFolder = $folder;
                $targetPath = $subfolder ?: $folder;
                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // Physical subfolders
                $rawSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                              ->toDateTimeString(),
                        'linked' => false,
                    ])->toArray();

                // Linked folders from DB
                $linkedFolders = [];
                $selectedFolderModel = \App\Models\Folder::where('user_id', $this->selectedUser->id)
                    ->where('name', basename($folder))
                    ->first();

                if ($selectedFolderModel) {
                    $linkedFolders = $selectedFolderModel->linkedFolders
                        ->map(fn($f) => [
                            'type' => 'folder',
                            'path' => $f->user_id . '/' . $f->name,
                            'name' => $f->name,
                            'created_at' => $f->created_at->toDateTimeString(),
                            'linked' => true,
                        ])->toArray();
                }

                // Merge physical + linked
                $this->subfolders = collect($rawSubfolders)
                    ->merge($linkedFolders)
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                // Media files
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
                $mediaPaged = $allMedia->forPage($this->page, $this->perPage)->values();

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
        $user = auth()->user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}
