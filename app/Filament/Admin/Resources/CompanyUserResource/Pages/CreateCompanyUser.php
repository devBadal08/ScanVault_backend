<?php

namespace App\Filament\Admin\Resources\CompanyUserResource\Pages;

use App\Filament\Admin\Resources\CompanyUserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyUser extends CreateRecord
{
    protected static string $resource = CompanyUserResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect to the list page instead of edit page
        return $this->getResource()::getUrl('index');
    }
}
