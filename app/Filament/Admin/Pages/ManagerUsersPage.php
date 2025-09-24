<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class ManagerUsersPage extends Page
{
    protected static string $view = 'filament.admin.pages.manager-users-page';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Manager Users';
    protected static ?int $navigationSort = 8;

    public $managers = [];
    public $managerUsers = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $users = [];
    public $items = [];

    public $selectedManager = null;
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
            ['Last Week' => [], 'Earlier this Month' => [], 'Older' => []]
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
        $managerId = request()->get('manager');
        $userId = request()->get('user');
        $folder = request()->get('folder');
        $subfolder = request()->get('subfolder');

        if (!in_array($authUser->role, ['manager', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        if ($managerId) {
            $this->selectedManager = User::find($managerId);
        }

        if ($authUser->role === 'manager') {
            // Manager sees only their own users
            $this->managerUsers = User::where('role', 'user')
                ->where('created_by', $authUser->id)
                ->get();

        } elseif ($authUser->role === 'admin') {
            // Admin sees all users under managers they created
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $this->managerUsers = User::where('role', 'user')
                ->whereIn('created_by', $managerIds)
                ->get();
        }

        // -------------------------------
        // Selected user: show folders/images
        // -------------------------------
        if ($userId) {
            $this->selectedUser = User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId;

            // Top-level folders
            if (!$folder) {
                $rawFolders = collect(Storage::disk('public')->directories($baseUserPath))
                    ->map(fn($dir) => [
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                    ])->toArray();

                $this->folders = $this->groupByDate($rawFolders);
            }
            // Inside folder / subfolder
            else {
                $this->selectedFolder = $folder;
                $targetPath = $subfolder ?: $folder;
                if ($subfolder) $this->selectedSubfolder = $subfolder;

                // Subfolders
                $rawSubfolders = collect(Storage::disk('public')->directories($targetPath))
                    ->map(fn($dir) => [
                        'type' => 'folder',
                        'path' => $dir,
                        'name' => basename($dir),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))
                                            ->toDateTimeString(),
                    ]);
                $this->subfolders = $rawSubfolders->values()->toArray();

                // Images
                $allImages = collect(Storage::disk('public')->files($targetPath))
                    ->filter(fn($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg','jpeg','png']))
                    ->map(fn($file) => [
                        'type' => 'image',
                        'path' => $file,
                        'name' => basename($file),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))
                                            ->toDateTimeString(),
                    ])->values();

                $this->total = $allImages->count();

                $imagesPaged = $allImages->forPage($this->page, $this->perPage)->values();
                $this->images = $imagesPaged->toArray();

                // Merge folders first, then images
                $merged = $rawSubfolders->sortByDesc('created_at')->merge($imagesPaged->sortByDesc('created_at'))->values();
                $this->items = $this->groupByDate($merged->toArray());
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
