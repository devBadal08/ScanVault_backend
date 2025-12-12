<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = auth()->user();

        // Save who created this user
        $data['created_by'] = $currentUser->id;
        $data['assigned_to'] = $currentUser->id;

        if ($currentUser->hasRole('Super Admin')) {
            if (empty($data['company_id'])) {
                throw ValidationException::withMessages([
                    'company_id' => ['Please select a company.'],
                ]);
            }
        } else {
            // storing company_id inside users table
            $data['company_id'] = $currentUser->company_id;
        }

        $data['password'] = \Hash::make($data['password']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $currentUser = auth()->user();
        $user = $this->record; // just created user

        if ($user->role) {
            $user->syncRoles([$user->role]);
        }

        if ($currentUser->hasRole('Super Admin')) {
            $companyId = $this->data['company_id'] ?? null;
            if ($companyId) {
                $user->companies()->syncWithoutDetaching([$companyId]);
            }
        } else {
            // Admin or Manager → assign same companies as creator
            $companyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            if (!empty($companyIds)) {
                $user->companies()->syncWithoutDetaching($companyIds);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
