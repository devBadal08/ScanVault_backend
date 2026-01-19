<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Schema;

class AssignUserToCompany extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'User Company Access';
    protected static ?string $navigationLabel = 'Assign User to Company';
    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.admin.pages.assign-user-to-company';

    public $selectedUser = null;
    public $selectedCompanies = [];

    // Fetch users under Admin panel
    public function getUsers()
    {
        $mainCompanyId = auth()->user()->company_id;

        // all company ids admin controls (main + sub)
        $companyIds = Company::where('id', $mainCompanyId)
            ->orWhere('parent_id', $mainCompanyId)
            ->pluck('id')
            ->toArray();

        // unique user ids from company_user for those companies
        $userIds = CompanyUser::whereIn('company_id', $companyIds)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        // If no users, return empty collection
        if (empty($userIds)) {
            return collect();
        }

        // If you use Spatie's roles package (User::role() exists), use it
        if (method_exists(User::class, 'role')) {
            return User::role('user')
                ->whereIn('id', $userIds)
                ->get();
        }

        // Otherwise fall back to a simple 'role' column on users table
        if (Schema::hasColumn('users', 'role')) {
            return User::where('role', 'user')
                ->whereIn('id', $userIds)
                ->get();
        }

        // Final fallback: return users by id but filter out common admin/manager roles
        // (adjust the role names to match your app if needed)
        return User::whereIn('id', $userIds)
            ->whereNotIn('role', ['admin', 'manager', 'super-admin', 'super_admin'])
            ->get();
    }

    // Show main company + sub companies of logged-in admin
    public function getCompanies()
    {
        $mainCompanyId = auth()->user()->company_id;

        return Company::where('id', $mainCompanyId)
            ->orWhere('parent_id', $mainCompanyId)
            ->get();
    }

    // Fetch already assigned companies for selected user
    public function getAssignedCompanies()
    {
        if (!$this->selectedUser) return collect();

        return Company::whereIn('id', 
            CompanyUser::where('user_id', $this->selectedUser)->pluck('company_id')
        )->get();
    }

    public function getSelectedUserBaseCompanyId()
    {
        if (!$this->selectedUser) {
            return null;
        }

        return User::where('id', $this->selectedUser)->value('company_id');
    }

    // Save access
    public function save()
    {
        foreach ($this->selectedCompanies as $companyId) {
            CompanyUser::firstOrCreate([
                'user_id' => $this->selectedUser,
                'company_id' => $companyId,
            ]);
        }

        session()->flash('success', 'Access assigned successfully!');
    }

    // Remove access
    public function removeAccess($companyId)
    {
        CompanyUser::where('user_id', $this->selectedUser)
            ->where('company_id', $companyId)
            ->delete();

        session()->flash('success', 'Access removed successfully!');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !$user->hasRole('admin')) {
            return false;
        }

        $company = Company::find($user->company_id);

        // Block sub-company admins → only allow main company admins
        return $company && $company->parent_id === null;
    }
}
