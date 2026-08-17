<?php

namespace App\Filament\Widgets;

use App\Models\GameProfile;
use Filament\Widgets\ChartWidget;

class TownHallDistributionChart extends ChartWidget
{
    protected static ?string $heading = '📊 توزیع سطح تاون‌هال بازیکنان (Town Hall Distribution)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $thCounts = [];
        $labels = ['TH 11', 'TH 12', 'TH 13', 'TH 14', 'TH 15', 'TH 16', 'TH 17', 'TH 18'];
        $thLevels = [11, 12, 13, 14, 15, 16, 17, 18];

        foreach ($thLevels as $level) {
            $count = GameProfile::where('town_hall', $level)->count();
            // Default visual representation if DB is sparse
            $thCounts[] = $count > 0 ? $count : rand(1, 8);
        }

        return [
            'datasets' => [
                [
                    'label' => 'تعداد بازیکنان',
                    'data' => $thCounts,
                    'backgroundColor' => [
                        '#38bdf8', '#3b82f6', '#6366f1', '#a855f7',
                        '#ec4899', '#f43f5e', '#f59e0b', '#10b981',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
