<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\User;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public int $remainingLimit = 0;
    public bool $limitReached = false;

    public function mount(): void
    {
        parent::mount();
        $this->calculateLimit();
    }

    /**
     * Only one Create button + disable when limit reached
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Create User')
                ->disabled(fn () => $this->limitReached),
        ];
    }

    /**
     * HARD backend block if limit reached
     */
    protected function beforeCreate(): void
    {
        if ($this->limitReached) {
            Notification::make()
                ->title('User limit reached')
                ->body('Your user creation limit is reached. Please contact Super Admin.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = auth()->user()?->fresh();

        $data['created_by'] = $currentUser->id;
        $data['assigned_to'] = $currentUser->id;

        if ($currentUser->hasRole('Super Admin')) {
            if (empty($data['company_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'company_id' => ['Please select a company.'],
                ]);
            }
        } else {
            $data['company_id'] = $currentUser->company_id;
        }

        $data['password'] = \Hash::make($data['password']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $currentUser = auth()->user()?->fresh();
        $user = $this->record;

        // Assign role
        if ($user->role) {
            $user->syncRoles([$user->role]);
        }

        // Assign company
        if ($currentUser->hasRole('Super Admin')) {
            if (!empty($this->data['company_id'])) {
                $user->companies()->syncWithoutDetaching([$this->data['company_id']]);
            }
        } else {
            $companyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            if (!empty($companyIds)) {
                $user->companies()->syncWithoutDetaching($companyIds);
            }
        }

        // Reset form
        $this->form->fill();

        // Recalculate limit after creation
        $this->calculateLimit();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create');
    }

    /**
     * Calculate remaining admin limit
     */
    protected function calculateLimit(): void
    {
        $currentUser = auth()->user()?->fresh();

        if (!$currentUser || !$currentUser->hasRole('admin')) {
            $this->limitReached = false;
            return;
        }

        $adminMaxLimit = $currentUser->max_limit ?? 0;

        // 1. Users created directly by admin
        $adminUserCount = User::where('created_by', $currentUser->id)
            ->where('role', 'user')
            ->count();

        // 2. Managers created by admin
        $managerIds = User::where('created_by', $currentUser->id)
            ->where('role', 'manager')
            ->pluck('id');

        // 3. Users created by managers
        $managerUserCount = $managerIds->isEmpty()
            ? 0
            : User::whereIn('created_by', $managerIds)
                ->where('role', 'user')
                ->count();

        // 4. Manager limits already reserved
        $assignedLimitToManagers = User::where('created_by', $currentUser->id)
            ->where('role', 'manager')
            ->sum('max_limit');

        $used = $adminUserCount
            + $managerUserCount
            + $assignedLimitToManagers;

        $this->remainingLimit = max($adminMaxLimit - $used, 0);
        $this->limitReached = $this->remainingLimit <= 0;
    }

    public function getSubheading(): ?string
    {
        return $this->limitReached
            ? 'Your user creation limit is reached. Please contact Super Admin.'
            : null;
    }
}
