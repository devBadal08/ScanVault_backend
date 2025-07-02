<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\Page;
use App\Models\Company;

class CompanyList extends Page
{
    protected static string $resource = \App\Filament\Resources\CompanyResource::class;

    protected static string $view = 'filament.resources.company-resource.pages.company-list';

    public function getCompaniesProperty()
    {
        return Company::all();
    }
}
