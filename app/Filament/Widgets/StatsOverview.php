<?php

namespace App\Filament\Widgets;

use App\Models\GameProfile;
use App\Models\Map;
use App\Models\StrategyLabSession;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeStreakUsers = User::where('task_streak', '>', 0)->count();
        $totalProfiles = GameProfile::count();
        $avgTownHall = (int) (GameProfile::all()->avg(fn ($p) => (int) ($p->game_data['townHallLevel'] ?? 1)) ?: 14);
        $totalMaps = Map::count();
        $totalLabSessions = StrategyLabSession::count();
        $completedTasks = Task::where('status', 'completed')->count();

        return [
            Stat::make('👥 فرماندهان ثبت‌نام شده', number_format($totalUsers))
                ->description("{$activeStreakUsers} کاربر با استریک فعال 🔥")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('amber')
                ->chart([3, 7, 12, 18, 25, $totalUsers ?: 30]),

            Stat::make('🏰 پروفایل‌های کلش فعال', number_format($totalProfiles))
                ->description("میانگین تاون‌هال: لول {$avgTownHall} 👑")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning')
                ->chart([5, 10, 15, 20, 24, $totalProfiles ?: 28]),

            Stat::make('🧪 آنالیزهای آزمایشگاه بیس', number_format($totalLabSessions))
                ->description('تحلیل تصویری مپ با هوش مصنوعی ⚡')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('success')
                ->chart([2, 5, 8, 14, 20, $totalLabSessions ?: 25]),

            Stat::make('🗺️ آرشیو نقشه‌های برتر', number_format($totalMaps))
                ->description('بیس‌های تاون‌هال و بیلدرهال 🗺️')
                ->descriptionIcon('heroicon-m-map')
                ->color('primary')
                ->chart([10, 30, 60, 90, 120, $totalMaps ?: 150]),

            Stat::make('✅ تسک‌های تکمیل شده AI', number_format($completedTasks))
                ->description('برنامه‌ریزی هوشمند روزانه 📅')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info')
                ->chart([4, 9, 15, 22, 30, $completedTasks ?: 40]),
        ];
    }
}
