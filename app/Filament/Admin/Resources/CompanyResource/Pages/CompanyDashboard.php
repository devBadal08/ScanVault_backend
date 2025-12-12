<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Models\Company;
use App\Models\User;

class CompanyDashboard extends Page
{
    protected static string $resource = CompanyResource::class;
    protected static string $view = 'filament.admin.resources.company-resource.pages.company-dashboard';

    public $company;
    public $totalAdmins;
    public $totalManagers;
    public $totalUsers;
    public $users;
    public $totalStorageUsed;
    public $totalPhotos;

    public $showFormPage = false;
    public $editingUserId = null;

    // Form fields
    public $name;
    public $email;
    public $password;
    public $role;
    public $max_limit;
    public $max_storage;

    public function mount($record): void
    {
        $this->company = Company::findOrFail($record);
        $this->loadUsers();
        $this->refreshCounts();
    }

    public function loadUsers()
    {
        $this->users = $this->company->users()->get();
    }

    public function refreshCounts()
    {
        $this->totalAdmins = $this->company->users()->where('role', 'admin')->count();
        $this->totalManagers = $this->company->users()->where('role', 'manager')->count();
        $this->totalUsers = $this->company->users()->where('role', 'user')->count();
        $this->totalStorageUsed = $this->calculateCompanyStorage();
        $this->totalPhotos = $this->calculateCompanyPhotos();
    }

    private function calculateCompanyStorage(): string
    {
        $totalSize = 0;

        // Loop through each user in the company
        foreach ($this->company->users as $user) {
            $userFolder = storage_path('app/public/' . $user->id);
            if (is_dir($userFolder)) {
                foreach (new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($userFolder, \FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    $totalSize += $file->getSize();
                }
            }
        }

        // Convert bytes → MB & GB
        $totalSizeMB = round($totalSize / (1024 ** 2), 2);
        $totalSizeGB = round($totalSize / (1024 ** 3), 2);

        return "{$totalSizeGB} GB (≈{$totalSizeMB} MB)";
    }

    public function refreshStorageUsage()
    {
        $this->totalStorageUsed = $this->calculateCompanyStorage();
    }

    private function calculateCompanyPhotos(): int
    {
        $totalPhotos = 0;
        $imageExtensions = ['jpg', 'jpeg', 'png'];

        // Loop through each user in the company
        foreach ($this->company->users as $user) {
            $userFolder = storage_path('app/public/' . $user->id);

            if (is_dir($userFolder)) {
                foreach (new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($userFolder, \FilesystemIterator::SKIP_DOTS)
                ) as $file) {
                    if (in_array(strtolower($file->getExtension()), $imageExtensions)) {
                        $totalPhotos++;
                    }
                }
            }
        }

        return $totalPhotos;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(fn () => !$this->editingUserId),

            Forms\Components\Select::make('role')
                ->label('Role')
                ->options([
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ])
                ->disabled(fn () => $this->editingUserId) // lock role on edit
                ->required(),

            Forms\Components\TextInput::make('max_limit')
                ->label('Max Limit')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('max_storage')
                ->label('Max Storage (GB)')
                ->numeric()
                ->helperText('Enter value in GB (e.g., 1 = 1GB, 2 = 2GB)'),
        ];
    }

    // --- ACTIONS ---

    public function createNewUserPage()
    {
        $this->editingUserId = null;
        $this->showFormPage = true;

        $this->form->fill([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'max_limit' => '',
        ]);
    }

    public function editUserPage($userId)
    {
        $this->editingUserId = $userId;
        $this->showFormPage = true;

        $user = User::findOrFail($userId);
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->role,
            'max_limit' => $user->max_limit,
            'max_storage' => $user->max_storage / 1024,
        ]);
    }

    public function goBack()
    {
        // Redirect to your custom Company List page
        return redirect(CompanyResource::getUrl('company-list'));
    }

    public function back()
    {
        $this->showFormPage = false;
        $this->editingUserId = null;
    }

    public function saveUser()
    {
        $data = $this->form->getState();
        $maxStorageInMB = isset($data['max_storage']) ? $data['max_storage'] * 1024 : 0;

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
                'max_limit' => $data['max_limit'],
                'max_storage' => $maxStorageInMB,
            ]);
        } else {

            $currentUser = auth()->user();
            $createdCount = User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;

            if (in_array($currentUser->role, ['admin', 'manager']) && $createdCount >= $maxLimit) {
                Notification::make()
                    ->title('Limit Reached')
                    ->body('You have reached your max user creation limit.')
                    ->danger()
                    ->send();
                return;
            }

            // 🚀 Create new user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'max_limit' => $data['max_limit'],
                'max_storage' => $maxStorageInMB,
                'created_by' => auth()->id(),
                'company_id' => $this->company->id,
            ]);

            $user->assignRole($data['role']);

            // IMPORTANT — Add to pivot
            $user->companies()->syncWithoutDetaching([$this->company->id]);

        }

        // Reset form/view after finishing creation or update
        $this->showFormPage = false;
        $this->editingUserId = null;

        $this->loadUsers();
        $this->refreshCounts();

        Notification::make()
            ->title('User Saved')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'company' => $this->company,
            'users' => $this->users,
            'totalAdmins' => $this->totalAdmins,
            'totalManagers' => $this->totalManagers,
            'totalUsers' => $this->totalUsers,
            'totalStorageUsed' => $this->totalStorageUsed,
            'totalPhotos' => $this->totalPhotos ?? 0,
            'form' => $this->form,
            'showFormPage' => $this->showFormPage,
            'editingUserId' => $this->editingUserId,
        ];
    }
}
