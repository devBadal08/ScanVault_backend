<?php

namespace App\Filament\Admin\Resources\PhotoDeleteHistoryResource\Pages;

use App\Filament\Admin\Resources\PhotoDeleteHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhotoDeleteHistory extends EditRecord
{
    protected static string $resource = PhotoDeleteHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
