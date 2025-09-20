<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Folder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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
            ['Last Week' => [], 'Earlier this Month' => [], 'Older' => []]
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
        $authUser = Auth::user();
        if (!in_array($authUser->role, ['manager', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $userId = request()->get('user');
        $folderName = request()->get('folder');
        $subfolderName = request()->get('subfolder');

        // ----------------------------
        // Step 0: Manager users
        // ----------------------------
        if ($authUser->role === 'manager') {
            $this->managerUsers = User::where('role', 'user')
                ->where('assigned_to', $authUser->id)
                ->get();
        } elseif ($authUser->role === 'admin') {
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id');

            $this->managerUsers = User::where('role', 'user')
                ->whereIn('assigned_to', $managerIds)
                ->get();
        }

        if (!$userId) return;

        $this->selectedUser = User::find($userId);
        if (!$this->selectedUser) return;

        // ----------------------------
        // Step 1: Company DB setup
        // ----------------------------
        $company = \DB::connection('mysql')->table('companies')
            ->where('id', $this->selectedUser->company_id)
            ->first();

        if (!$company || !$company->database_name) return;

        config([
            "database.connections.dynamic_company" => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'database' => $company->database_name,
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
        ]);

        // Resolve user in company DB
        $companyUserId = \DB::connection('dynamic_company')
            ->table('users')
            ->where('email', $this->selectedUser->email)
            ->value('id');
        if (!$companyUserId) return;

        $companyUser = \DB::connection('dynamic_company')
            ->table('users')
            ->where('id', $companyUserId)
            ->first();
        if (!$companyUser || !$companyUser->name) return;

        $username = $companyUser->name;

        // ----------------------------
        // Step 2: Determine folderPath
        // ----------------------------
        $folderPath = $subfolderName ?? $folderName;

        if (!$folderPath) {
            // Top-level folders
            $folders = Folder::topLevelFolders('dynamic_company', $companyUserId);
            $folders->each(fn($f) => $f->setCompanyConnection('dynamic_company'));

            $foldersArray = $folders->map(function ($f) use ($username) {
                $photos = $f->userPhotos($username);
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

        // ----------------------------
        // Step 3: Selected folder
        // ----------------------------
        $folderSegments = explode('/', $folderPath);
        $lastFolderName = end($folderSegments);

        $folderModel = Folder::on('dynamic_company')
            ->where('name', $lastFolderName)
            ->where('user_id', $companyUserId)
            ->first();
        if (!$folderModel) return;

        $folderModel->setCompanyConnection('dynamic_company');
        $this->selectedFolder = $folderName;
        $this->selectedSubfolder = $subfolderName;

        // Subfolders
        $subfolders = $folderModel->getSubfolders();
        $this->subfolders = $subfolders->map(function ($sf) use ($username, $folderPath) {
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

        // Photos
        $photos = $folderModel->userPhotos($username);

        // Merge subfolders + photos
        $allItems = collect($this->subfolders)->merge(
            $photos->map(function ($p) {
                $p = (array)$p;
                return [
                    'id' => $p['id'],
                    'path' => Storage::disk('public')->url($p['path']),
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
        $user = auth()->user();
        return $user && in_array($user->role, ['manager', 'admin']);
    }
}
