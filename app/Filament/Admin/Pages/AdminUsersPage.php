<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminUsersPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.admin-users-page';

    protected static ?string $navigationGroup = 'Photos';
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?int $navigationSort = 7;

    public $managers = [];
    public $adminUsers = [];
    public $folders = [];
    public $subfolders = [];
    public $images = [];
    public $users = [];

    public $selectedManager = null;
    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    // pagination properties
    public int $perPage = 550; // number of images per page
    public int $page = 1;     // current page
    public int $total = 0;    // total images

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

        if ($authUser->role === 'admin') {
            $adminId = $authUser->id;

            // Managers under this admin
            $this->managers = User::where('role', 'manager')
                ->where('created_by', $adminId)
                ->get();

            // Collect manager IDs
            $managerIds = $this->managers->pluck('id')->toArray();

            // 🔹 Users directly created by this admin
            $this->adminUsers = User::where('role', 'user')
                ->where('created_by', $adminId)
                ->get();

            // 🔹 If a manager is selected, show their users
            if (request()->has('manager')) {
                $managerId = request()->get('manager');

                $this->selectedManager = $this->managers->firstWhere('id', $managerId);

                $this->users = User::where('role', 'user')
                    ->where('assigned_to', $managerId)
                    ->get();
            } else {
                // If no manager selected → show only admin’s users
                $this->users = $this->adminUsers;
            }

        } elseif ($authUser->role === 'manager') {
            // Manager sees only their assigned users
            $this->selectedManager = $authUser;

            $this->users = User::where('role', 'user')
                ->where('assigned_to', $authUser->id)
                ->get();
        }

        if ($userId) {
            $this->selectedUser = \App\Models\User::find($userId);
            if (!$this->selectedUser) return;

            $baseUserPath = $userId;

            if (!$folder) {
                $this->folders = Storage::disk('public')->directories($baseUserPath);

            } elseif (!$subfolder) {
                $this->selectedFolder = $folder;

                $this->subfolders = Storage::disk('public')->directories($folder);

                // ✅ Paginate images here
                $allImages = collect(Storage::disk('public')->files($folder))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    ->values();

                $this->total = $allImages->count();
                $this->images = $allImages
                    ->forPage($this->page, $this->perPage)
                    ->toArray();

            } else {
                $this->selectedFolder = $folder;
                $this->selectedSubfolder = $subfolder;

                // ✅ Fetch deeper subfolders
                $this->subfolders = Storage::disk('public')->directories($subfolder);

                // ✅ Paginate images here
                $allImages = collect(Storage::disk('public')->files($subfolder))
                    ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    ->values();

                $this->total = $allImages->count();
                $this->images = $allImages
                    ->forPage($this->page, $this->perPage)
                    ->toArray();
            }
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        // Only show to admins
        return $user && $user->role === 'admin';
    }

     // When page changes, reload images
    public function updatedPage()
    {
        $this->mount(); // re-run mount to refresh images
    }
}
