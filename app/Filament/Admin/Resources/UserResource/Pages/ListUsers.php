<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Storage;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        Actions\CreateAction::make(),

        Action::make('exportExcel')
            ->label('Export to Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function () {

                $filePath = storage_path('app/public/users.xlsx');

                // If file exists, load it; else create new
                if (file_exists($filePath)) {
                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();

                    // Read existing emails from Excel to avoid duplicates
                    $existingEmails = [];
                    $highestRow = $sheet->getHighestRow();

                    for ($row = 2; $row <= $highestRow; $row++) {
                        $email = $sheet->getCell("B{$row}")->getValue();
                        if ($email) {
                            $existingEmails[] = trim($email);
                        }
                    }

                } else {

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    // Create header
                    $sheet->fromArray([
                        ['Name', 'Email', 'Role', 'Created By', 'Assigned To']
                    ], null, 'A1');

                    $existingEmails = [];
                }

                // Find next empty row
                $nextRow = $sheet->getHighestRow() + 1;

                // Get filtered user list
                $users = $this->getTableQuery()->get();

                foreach ($users as $user) {

                    // Skip if already exported
                    if (in_array(trim($user->email), $existingEmails)) {
                        continue;
                    }

                    // Append new data only
                    $sheet->fromArray([
                        [
                            $user->name,
                            $user->email,
                            $user->role,
                            $user->createdBy->name ?? '',
                            $user->assignedTo->name ?? '',
                        ]
                    ], null, "A{$nextRow}");

                    $nextRow++;
                }

                // Save file back to same path
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);

                \Filament\Notifications\Notification::make()
                    ->title('Excel Updated')
                    ->body('Only new users were appended. No duplicates added.')
                    ->success()
                    ->send();
            }),
    ];

        $currentUser = auth()->user();
        $createdCount = 0;
        $maxLimit = 0;
        $remaining = 1;

        if ($currentUser && $currentUser->hasRole('admin')) {
            $createdCount = \App\Models\User::where('created_by', $currentUser->id)->count();
            $maxLimit = $currentUser->max_limit ?? 0;
            $remaining = max($maxLimit - $createdCount, 0);
        }

        return [
            Actions\CreateAction::make()
                ->disabled($remaining <= 0)
                ->tooltip($remaining <= 0 ? 'You have reached your maximum user creation limit.' : null),
        ];
    }

     /**
     * Show only the users created by the current admin.
     */
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->hasRole('admin')) {
            $adminId = $currentUser->id;

            $managerIds = User::where('created_by', $adminId)
                            ->where('role', 'manager')
                            ->pluck('id')
                            ->toArray();

            return $query->where(function($query) use ($adminId, $managerIds) {
                $query->where('created_by', $adminId)
                    ->orWhereIn('created_by', $managerIds);
            });
        }

        return $query;
    }
}

