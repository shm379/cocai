<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\ProgressionService;

class CwlManagerService
{
    public function __construct(protected ProgressionService $progression)
    {
    }

    /**
     * تحلیل و پیشنهاد چینش و استراتژی وارلیگ (CWL Lineup & Star Optimizer)
     */
    public function analyzeCwl(User $user, string $league = 'champions_3', int $rosterSize = 15): array
    {
        $gameProfile = $user->gameProfile()->first() ?? $user->gameProfile;
        $gameData = $gameProfile->game_data ?? [];
        $analysis = ! empty($gameData) ? $this->progression->analyze($gameData) : null;

        $playerTh = $analysis['town_hall'] ?? 15;
        $warStars = $gameData['warStars'] ?? 600;

        $leaguesInfo = [
            'champions_1' => ['name' => 'چمپیون ۱ (Champion I)', 'medals' => 508, 'expected_th' => 17],
            'champions_2' => ['name' => 'چمپیون ۲ (Champion II)', 'medals' => 456, 'expected_th' => 16],
            'champions_3' => ['name' => 'چمپیون ۳ (Champion III)', 'medals' => 404, 'expected_th' => 16],
            'masters_1' => ['name' => 'مستر ۱ (Master I)', 'medals' => 356, 'expected_th' => 15],
            'masters_2' => ['name' => 'مستر ۲ (Master II)', 'medals' => 312, 'expected_th' => 14],
            'masters_3' => ['name' => 'مستر ۳ (Master III)', 'medals' => 272, 'expected_th' => 13],
            'crystal_1' => ['name' => 'کریستال ۱ (Crystal I)', 'medals' => 236, 'expected_th' => 12],
        ];

        $targetLeague = $leaguesInfo[$league] ?? $leaguesInfo['masters_1'];
        $expectedOpponentTh = $targetLeague['expected_th'];

        // تخمین ستاره‌ها و مدال‌های دریافتی
        $predictedStarsPerDay = $playerTh >= $expectedOpponentTh ? 2.8 : max(1.5, 2.7 - (($expectedOpponentTh - $playerTh) * 0.5));
        $totalPredictedStars = round($predictedStarsPerDay * 7);
        $medalYield = round($targetLeague['medals'] * min(1.0, (0.2 + ($totalPredictedStars * 0.1))));

        return [
            'ok' => true,
            'player_th' => $playerTh,
            'selected_league' => $targetLeague['name'],
            'roster_size' => $rosterSize,
            'predicted_stars_total' => $totalPredictedStars,
            'predicted_medals' => $medalYield,
            'max_possible_medals' => $targetLeague['medals'],
            'recommended_matchups' => [
                [
                    'day' => 'روز ۱ و ۲',
                    'target_role' => 'حمله ایمن به هم‌سطح (Mirror Hit)',
                    'tactic' => 'استفاده از ترکیب روت رایدر با بلیمپ سیف ۲ ستاره تضمینی روی تاون‌هال ۱۶/۱۷.',
                ],
                [
                    'day' => 'روز ۳ و ۴',
                    'target_role' => 'حمله سنگین ۳ ستاره (High-Reward Attack)',
                    'tactic' => 'استفاده از زپ تایتان یا سوپر آرچر هیدرا روی بیس‌های با کمپارتمنت‌های فشرده.',
                ],
                [
                    'day' => 'روز ۵ تا ۷',
                    'target_role' => 'مدیریت مدال پاداش (Bonus Medal Securing)',
                    'tactic' => 'زدن تارگت‌های با احتمال بالای ۳ ستاره برای ارتقای رتبه فردی در جدول کلن.',
                ],
            ],
            'bonus_medal_formula_fa' => 'فرمول توزیع مدال اضافه: ۵۰ مدال پایه کلن + ۲۰ مدال به ازای هر برد وار. اولویت اهدای بانس به بازیکنان با بیش از ۱۸ ستاره و بدون میس اتک.',
        ];
    }
}
