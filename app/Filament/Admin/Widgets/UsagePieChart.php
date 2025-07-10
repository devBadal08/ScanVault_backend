<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;

class UsagePieChart extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?string $maxHeight = '150px';
    protected array|string|int $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('admin') || $user->hasRole('manager'));
    }
    
    protected function getData(): array
    {
        $currentUser = auth()->user();
        $totalCreated = 0;
        $maxLimit = 10;
        $remaining = 10;
        // DOUBLE CHECK: stop even if Filament tries to render
        if (!$currentUser || $currentUser->hasRole('Super Admin')) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $totalCreated = User::where('created_by', $currentUser->id)->count();
        $maxLimit = $currentUser->max_limit ?? 10;
        $remaining = max($maxLimit - $totalCreated, 0);

        return [
            'datasets' => [
                [
                    'label' => 'Usage',
                    'data' => [$totalCreated, $remaining],
                    'backgroundColor' => [
                        $this->getUsageColor($totalCreated, $maxLimit),
                        '#e5e7eb'
                    ],
                ],
            ],
            'labels' => ['Used', 'Remaining'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getUsageColor($created, $limit): string
    {
        $percentUsed = ($limit > 0) ? ($created / $limit) * 100 : 0;

        return $percentUsed >= 100 ? '#f87171' :
               ($percentUsed >= 90 ? '#facc15' : '#22c55e');
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'left',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Usage vs Remaining',
                    'font' => [
                        'size' => 18
                    ],
                ],
            ],
            'cutout' => '60%',
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
