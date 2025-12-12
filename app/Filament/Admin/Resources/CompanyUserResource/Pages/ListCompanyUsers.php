<?php

namespace App\Filament\Admin\Resources\CompanyUserResource\Pages;

use App\Filament\Admin\Resources\CompanyUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyUsers extends ListRecords
{
    protected static string $resource = CompanyUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
