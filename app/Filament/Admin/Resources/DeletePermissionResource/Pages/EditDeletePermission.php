<?php

namespace App\Filament\Admin\Resources\DeletePermissionResource\Pages;

use App\Filament\Admin\Resources\DeletePermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeletePermission extends EditRecord
{
    protected static string $resource = DeletePermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
