<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class AdminUsersPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';
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
    // At top with other public props
    public ?string $username = null;

    // pagination properties
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

            $created = Carbon::parse($item['created_at'])->timezone(config('app.timezone'));
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
        $authUser = auth()->user();
        if ($authUser->role !== 'admin') abort(403);

        $managerId = request()->get('manager');
        $userId = request()->get('user');
        $folderName = request()->get('folder');       // main folder
        $subfolderName = request()->get('subfolder'); // subfolder (optional)
        $adminId = $authUser->id;

        // Load managers & admin users
        $this->managers = User::where('role', 'manager')->where('created_by', $adminId)->get();
        $this->adminUsers = User::where('role', 'user')->where('created_by', $adminId)->get();

        $this->users = $managerId 
            ? User::where('role', 'user')->where('created_by', $managerId)->get()
            : $this->adminUsers;

        // If manager selected but no user yet → show users under that manager
        if ($managerId && !$userId) {
            $this->selectedManager = User::find($managerId);
            if (!$this->selectedManager) return;

            $this->users = User::where('role', 'user')
                ->where('created_by', $managerId)
                ->get();

            // prepare items array for blade view
            $this->items = $this->users->map(function($u) use ($managerId) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'type' => 'user',
                    'created_at' => $u->created_at,
                    'path' => '?manager='.$managerId.'&user='.$u->id,
                ];
            })->toArray();

            return;
        }

        $this->selectedUser = User::find($userId);
        if (!$this->selectedUser) return;

        // Get company DB
        $company = \DB::connection('mysql')->table('companies')
            ->where('id', $this->selectedUser->company_id)
            ->first();
        if (!$company || !$company->database_name) return;

        $companyDb = $company->database_name;

        // Configure dynamic connection
        config([
            "database.connections.dynamic_company" => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'database' => $companyDb,
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
        ]);

        // Company user ID
        $companyUserId = \DB::connection('dynamic_company')
            ->table('users')
            ->where('email', $this->selectedUser->email)
            ->value('id');
        if (!$companyUserId) return;

        // Company username for photos
        $companyUser = \DB::connection('dynamic_company')
            ->table('users')
            ->where('id', $companyUserId)
            ->first();
        if (!$companyUser || !$companyUser->name) return;
        $username = $companyUser->name;

        // -----------------------------
        // Determine which folder to load
        // -----------------------------
        $folderPath = $subfolderName ?? $folderName; // subfolder overrides folder

        if (!$folderPath) {
            // Load top-level folders
            $folders = \App\Models\Folder::topLevelFolders('dynamic_company', $companyUserId);
            $folders->each(fn($f) => $f->setCompanyConnection('dynamic_company'));

            $foldersArray = $folders->map(function($f) use ($username) {
                // Get all photos recursively inside this folder and its subfolders
                $photos = $f->userPhotos($username);

                // Use latest photo created_at if exists, else folder created_at
                $latestDate = $photos->max(fn($p) => $p->created_at) ?? $f->created_at;

                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'path' => $f->name,
                    'type' => 'folder',
                    'created_at' => $latestDate,
                ];
            })->toArray();

            $this->folders = $this->groupByDate($foldersArray);
            return;
        }

        // -----------------------------
        // Load selected folder and subfolders
        // -----------------------------
        $folderSegments = explode('/', $folderPath);
        $lastFolderName = end($folderSegments);

        $folderModel = \App\Models\Folder::on('dynamic_company')
            ->where('name', $lastFolderName)
            ->where('user_id', $companyUserId)
            ->first();
        if (!$folderModel) return;

        $folderModel->setCompanyConnection('dynamic_company');
        $this->selectedFolder = $folderName;
        $this->selectedSubfolder = $subfolderName;

        // Load subfolders with latest image date
        $subfolders = $folderModel->getSubfolders();
        $this->subfolders = $subfolders->map(function($sf) use ($username, $folderPath) {
            $photos = $sf->userPhotos($username);
            $latestDate = $photos->max(fn($p) => $p->created_at) ?? $sf->created_at;

            return [
                'id' => $sf->id,
                'name' => $sf->name,
                'path' => $folderPath.'/'.$sf->name,
                'type' => 'folder',
                'created_at' => $latestDate,
            ];
        })->toArray();

        // Load images in selected folder (including all subfolders recursively)
        $photos = $folderModel->userPhotos($username);

        // Merge subfolders and photos
        $allItems = collect($this->subfolders)->merge(
            $photos->map(function($p) {
                $p = (array) $p;
                return [
                    'id' => $p['id'],
                    'path' => $p['path'],
                    'name' => basename($p['path']),
                    'type' => 'image',
                    'created_at' => $p['created_at'],
                ];
            })
        );

        $this->total = $photos->count();
        $photosPaged = $allItems->forPage($this->page, $this->perPage);
        $this->items = $this->groupByDate($photosPaged->toArray());
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