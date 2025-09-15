<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

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

    protected function handleRecordCreation(array $data): User
    {
        // 1️⃣ If role is 'user', set max_limit to null
        if ($data['role'] === 'user') {
            $data['max_limit'] = 0;
        }

        // 2️⃣ Save in main database
        $user = User::create($data);

        // 3️⃣ Save in company's database
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->company && !empty($currentUser->company->database_name)) {
            $companyUser = (new User)->setConnectionByCompany($currentUser->company->database_name);
            $companyUser->setConnection('tenant');
            $companyUser->fill([
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password, // already hashed
                'role' => $user->role,
                'max_limit' => $user->max_limit,
                'created_by' => $user->created_by,
                'assigned_to' => $user->assigned_to,
                'company_id' => $user->company_id,
            ]);
            $companyUser->save();
            $companyUser->assignRole($user->role);

            // 4️⃣ If role is 'user', create a table like username_photos
            if ($user->role === 'user') {
                $tableName = strtolower($user->name) . '_photos';
                $tableName = preg_replace('/\s+/', '_', $tableName); // replace spaces
                $tableName = preg_replace('/[^a-z0-9_]/', '', $tableName); // remove special chars

                $connection = $companyUser->getConnection(); // tenant DB connection

                $connection->statement("
                    CREATE TABLE IF NOT EXISTS `$tableName` (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        path VARCHAR(255) NOT NULL,
                        user_id BIGINT UNSIGNED NOT NULL,
                        uploaded_by BIGINT UNSIGNED NULL,
                        folder_id BIGINT UNSIGNED NULL,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL,
                        INDEX uploaded_by_index (uploaded_by),
                        INDEX folder_id_index (folder_id),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            }
        }

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the list page instead of edit page
        return $this->getResource()::getUrl('index');
    }
}