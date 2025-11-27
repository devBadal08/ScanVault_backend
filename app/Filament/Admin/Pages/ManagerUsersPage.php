<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Folder;
use App\Models\FolderShare;
use App\Models\Photo;

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
    public $globalSearch = '';
    public $globalResults = [];

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

    public function mountedFolderPermissionsCheck($path)
    {
        $full = storage_path("app/public/$path");

        if (is_dir($full)) {
            @chmod($full, 0755);
        }
    }

    public function updatedGlobalSearch()
    {
        if (strlen($this->globalSearch) < 2) {
            $this->globalResults = [];
            return;
        }

        $query = strtolower($this->globalSearch);
        $results = [];

        $authUser = auth()->user();

        if ($authUser->role === 'manager') {

            $users = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get();
        } else {

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $users = User::whereIn('created_by', $managerIds)->get();
        }

        foreach ($users as $user) {

            $basePath = (string) $user->id;

            if (!Storage::disk('public')->exists($basePath)) {
                continue;
            }

            $allFolders = collect([$basePath])
                            ->merge(Storage::disk('public')->allDirectories($basePath))
                            ->unique();

            foreach ($allFolders as $folder) {

                if (str_contains(strtolower(basename($folder)), $query)) {
                    $results[] = [
                        'type'    => 'folder',
                        'name'    => basename($folder),
                        'user'    => $user->name,
                        'user_id' => $user->id,
                        'path'    => $folder,
                    ];
                }

                $files = Storage::disk('public')->allFiles($folder);

                foreach ($files as $file) {

                    if (str_contains(strtolower(basename($file)), $query)) {
                        $results[] = [
                            'type'    => pathinfo($file, PATHINFO_EXTENSION),
                            'name'    => basename($file),
                            'user'    => $user->name,
                            'user_id' => $user->id,
                            'path'    => $file,
                        ];
                    }
                }
            }
        }

        $this->globalResults = collect($results)
            ->sortByDesc('name')
            ->take(60)
            ->values()
            ->toArray();
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

        if ($authUser->role === 'manager') {
            $this->managerUsers = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get();
        } else {

            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $this->managerUsers = User::where('role', 'user')
                ->whereIn('created_by', $managerIds)
                ->get();
        }

        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId;

            if (!$folder) {

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

                $sharedToUser = FolderShare::where('shared_with', $this->selectedUser->id)
                    ->with('folder')
                    ->get()
                    ->map(function ($share) {
                        $folder = $share->folder;
                        return [
                            'type' => 'folder',
                            'path' => $folder->user_id . '/' . $folder->name,
                            'name' => $folder->name,
                            'created_at' => $folder->created_at->toDateTimeString(),
                            'linked' => true,
                            'owner_id' => $folder->user_id,
                        ];
                    })->toArray();

                $mergedFolders = collect($rawFolders)
                    ->merge($sharedToUser)
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                $this->folders = $this->groupByDate($mergedFolders);

            } else {

                $this->selectedFolder = $folder;
                $targetPath = $subfolder ?: $folder;
                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // ✅ permission fix
                $this->mountedFolderPermissionsCheck($targetPath);

                $rawSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))->toDateTimeString(),
                        'linked' => false,
                    ])->toArray();

                $linkedFolders = [];

                $folderOwnerId = explode('/', $folder)[0];

                $selectedFolderModel = Folder::where('user_id', $folderOwnerId)
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

                $this->subfolders = collect($rawSubfolders)
                    ->merge($linkedFolders)
                    ->sortByDesc(fn($i) => $i['created_at'])
                    ->values()
                    ->toArray();

                $allowedExtensions = ['jpg','jpeg','png','mp4','pdf'];

                $allMedia = collect(Storage::disk('public')->files($targetPath))
                    ->filter(fn($file) =>
                        in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowedExtensions)
                    )
                    ->map(fn($file) => [
                        'type' => match(strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                            'mp4' => 'video',
                            'pdf' => 'pdf',
                            default => 'image',
                        },
                        'path' => $file,
                        'name' => basename($file),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))
                            ->toDateTimeString(),
                    ])->values();

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
