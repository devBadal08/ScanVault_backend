<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use App\Models\Company;

class CompanyList extends Page
{
    protected static string $resource = CompanyResource::class;

    protected static string $view = 'filament.admin.resources.company-resource.pages.company-list';

    protected static ?string $slug = 'company-list';

    public $companies;

    public function mount()
    {
        $this->refreshCompanies();
    }

    public function refreshCompanies()
    {
        $this->companies = Company::all();
    }

    public function deleteCompany($companyId)
    {
        $company = Company::findOrFail($companyId);

        // Step 1: Get all parent-level users of this company
        $users = \App\Models\User::where('company_id', $companyId)->get();

        // Step 2: Delete all users and their children
        foreach ($users as $user) {
            $this->deleteUserRecursively($user->id);
        }

        // Step 3: Soft delete company or hard delete
        $company->delete();

        \Filament\Notifications\Notification::make()
            ->title('Company and all child users deleted successfully')
            ->success()
            ->send();

        $this->refreshCompanies();
    }

    public function deleteUserRecursively($userId)
    {
        $childUsers = \App\Models\User::where('created_by', $userId)->get();

        // Delete all children first
        foreach ($childUsers as $child) {
            $this->deleteUserRecursively($child->id);
        }

        // Delete user folder
        $userFolder = storage_path("app/public/{$userId}");
        if (is_dir($userFolder)) {
            \File::deleteDirectory($userFolder);
        }

        // Delete the user itself
        \App\Models\User::where('id', $userId)->delete();
    }
}
