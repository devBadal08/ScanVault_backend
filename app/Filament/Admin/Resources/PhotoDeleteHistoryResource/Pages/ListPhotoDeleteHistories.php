<?php

namespace App\Filament\Admin\Resources\PhotoDeleteHistoryResource\Pages;

use App\Filament\Admin\Resources\PhotoDeleteHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class ListPhotoDeleteHistories extends ListRecords
{
    protected static string $resource = PhotoDeleteHistoryResource::class;

    public ?string $companyId = null;

    public function mount($company = null): void
    {
        parent::mount();

        $this->companyId = $company;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('warning') // orange style
                ->url(route('filament.admin.resources.photo-delete-histories.index'))
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('company_id', $this->companyId);
    }

    public function getBreadcrumbs(): array
    {
        $companyId = $this->companyId;
        $company = \App\Models\Company::find($companyId);

        return [
            route('filament.admin.resources.photo-delete-histories.index') => 'Companies',
            '' => $company?->company_name ?? 'Records',
        ];
    }
}
