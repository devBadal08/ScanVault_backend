<?php

namespace App\Filament\Admin\Resources\PhotoDeleteHistoryResource\Pages;

use App\Filament\Admin\Resources\PhotoDeleteHistoryResource;
use Filament\Resources\Pages\Page;
use App\Models\Company;

class PhotoDeleteHistoryCompanies extends Page
{
    protected static string $resource = PhotoDeleteHistoryResource::class;

    protected static string $view = 'filament.admin.resources.photo-delete-history-resource.pages.photo-delete-history-companies';

    public function getViewData(): array
    {
        return [
            'companies' => Company::all(),
        ];
    }
}
