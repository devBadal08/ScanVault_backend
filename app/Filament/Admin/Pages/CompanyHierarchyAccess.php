<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\CompanyUser;

class CompanyHierarchyAccess extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'User Company Access';
    protected static ?string $navigationLabel = 'Company Hierarchy Access';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.company-hierarchy-access';

    public ?Company $selectedCompany = null;
    public array $selectedSubCompanies = [];

    public function selectCompany($companyId)
    {
        $this->selectedCompany = Company::find($companyId);
    }

    public function saveAccess()
    {
        foreach ($this->selectedSubCompanies as $subCompanyId) {
            Company::where('id', $subCompanyId)
                ->update(['parent_id' => $this->selectedCompany->id]);
        }

        $this->reset('selectedCompany', 'selectedSubCompanies');

        session()->flash('success', 'Updated successfully!');
    }

    public function getAllCompanies()
    {
        return Company::all();
    }

    public function getSubCompanies()
    {
        if (!$this->selectedCompany) return [];

        return Company::where('id', '!=', $this->selectedCompany->id)->get();
    }

    public function getAssignedSubCompanies()
    {
        if (!$this->selectedCompany) return collect();

        return Company::where('parent_id', $this->selectedCompany->id)->get();
    }

    public function goBack()
    {
        // Whatever "main page" state means for you
        $this->selectedCompany = null;

        // Your additional logic
        $this->showFormPage = false;
        $this->editingUserId = null;

        // If loadUsers() exists, call it
        if (method_exists($this, 'loadUsers')) {
            $this->loadUsers();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Super Admin');
    }
}
