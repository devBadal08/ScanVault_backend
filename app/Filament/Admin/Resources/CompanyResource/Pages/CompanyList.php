<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\Page;
use App\Models\Company;

class CompanyList extends Page
{
    protected static string $resource = CompanyResource::class;

    protected static string $view = 'filament.admin.resources.company-resource.pages.company-list';

    protected static ?string $slug = 'company-list';

    public $companies;

    public function mount()
    {
        $this->companies = Company::all();
    }
}
