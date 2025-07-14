<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
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

