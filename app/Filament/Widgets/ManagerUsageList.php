<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ManagerUsageList extends BaseWidget
{
    protected int | string | array $columnSpan = 'full'; // make it full width

    public static function canView(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('admin')
        );
    }

    public function table(Table $table): Table
    {
        $currentUser = auth()->user();

        return $table
            ->query(
                User::query()
                    ->where('role', 'manager')
                    ->where('created_by', $currentUser->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Manager Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_limit')
                    ->label('Max Limit'),

                Tables\Columns\TextColumn::make('used')
                    ->label('Used')
                    ->state(function (User $record) {
                        return User::where('role', 'user')
                            ->where('assigned_to', $record->id)
                            ->count();
                    }),

                Tables\Columns\TextColumn::make('remaining')
                    ->label('Remaining')
                    ->state(function (User $record) {
                        $used = User::where('role', 'user')
                            ->where('assigned_to', $record->id)
                            ->count();
                        return max(0, $record->max_limit - $used);
                    }),
            ]);
    }
}
