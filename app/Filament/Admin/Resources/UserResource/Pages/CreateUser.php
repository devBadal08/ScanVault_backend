<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    public $remainingSlots = null;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('admin')) {
            $createdCount = User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;

            if ($maxLimit > 0) {
                $percentUsed = ($createdCount / $maxLimit) * 100;

                if ($percentUsed >= 80 && $percentUsed < 100) {
                    Notification::make()
                        ->title('Almost at Limit')
                        ->body("You have used {$percentUsed}% of your limit. Only " . ($maxLimit - $createdCount) . " slots left.")
                        ->warning()
                        ->send();
                }

                if ($createdCount >= $maxLimit) {
                    Notification::make()
                        ->title('Limit Reached')
                        ->body('You have reached your maximum user creation limit.')
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'name' => ['You have reached your maximum user creation limit.'],
                    ]);
                }
            }
        }

        $data['created_by'] = $currentUser->id;
        $data['company_id'] = $currentUser->company_id ?? null;

        return $data;
    }
    protected function getViewData(): array
    {
        return [
            'remainingSlots' => $this->remainingSlots,
        ];
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole($this->record->role);
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the list page instead of edit page
        return $this->getResource()::getUrl('index');
    }
}
