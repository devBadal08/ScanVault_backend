<?php

namespace App\Filament\Admin\Resources\DeletePermissionResource\Pages;

use App\Filament\Admin\Resources\DeletePermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeletePermissions extends ListRecords
{
    protected static string $resource = DeletePermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
