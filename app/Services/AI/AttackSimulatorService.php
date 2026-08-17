<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\ChatbotService;
use App\Services\ProgressionService;

class AttackSimulatorService
{
    public function __construct(
        protected ProgressionService $progression,
        protected ChatbotService $chatbot
    ) {
    }

    /**
     * شبیه‌سازی و تولید نقشه تاکتیکی حمله ۳ مرحله‌ای بر اساس تاون‌هال هدف و ارتش انتخابی
     */
    public function generateTacticalBlueprint(User $user, int $targetTownHall, string $armyType = 'root_rider_smash', string $baseArchetype = 'box_base'): array
    {
        $gameProfile = $user->gameProfile()->first() ?? $user->gameProfile;
        $gameData = $gameProfile->game_data ?? [];
        $analysis = ! empty($gameData) ? $this->progression->analyze($gameData) : null;
        $playerTh = $analysis['town_hall'] ?? 15;

        $armyPresets = [
            'root_rider_smash' => [
                'name' => 'روت رایدر اسمش + اوورگروث (Root Rider Smash)',
                'siege' => 'Siege Barracks یا Battle Drill',
                'phase1_funnel' => 'کینگ بربر با دستکش غول‌پیکر و کلماتی والکری را از ساعت ۳ بفرستید تا محوطه بیرونی پاکسازی شود.',
                'phase2_core' => '۸ روت رایدر را همراه با گرند واردن و آچر کویین از زاویه ساعت ۴:۳۰ وارد بیس کنید. اسپل Overgrowth را روی منولیت و اسپل تاورها بیندازید.',
                'phase3_cleanup' => 'رویال چمپیون را از ساعت ۶ برای شکار دفاعی‌های باقیمانده بفرستید و اسپل فریز را برای اینفرنوهای چندهدفه ذخیره کنید.',
                'warden_timing' => 'دقیقه ۲:۱۵ (هنگام ورود روت رایدرها به محوطه تاون‌هال)',
                'win_probability' => $playerTh >= $targetTownHall ? 97 : max(60, 95 - (($targetTownHall - $playerTh) * 18)),
            ],
            'sarch_hydra' => [
                'name' => 'سوپر آرچر بلیمپ + دراگون ریدر (SArch Hydra)',
                'siege' => 'Battle Blimp پر از ۳ سوپر آرچر',
                'phase1_funnel' => 'لاوا هاند را جلوی بلیمپ بفرستید و گرند واردن را همراه آن رها کرده و ابیلیتی واردن را برای عبور از سویپر فعال کنید.',
                'phase2_core' => 'به محض ترکیدن بلیمپ در هسته بیس: ۱ اسپل نامرئی + ۱ کلون + ۱ خشم + ادامه اسپل‌های نامرئی هر ۴ ثانیه یکبار.',
                'phase3_cleanup' => 'دراگون‌ها و دراگون ریدرها را به صورت خطی در محوطه نابود شده رها کنید و با کویین بک‌اند را پاکسازی نمایید.',
                'warden_timing' => 'ثانیه ۰:۱۵ (برای محافظت از بلیمپ تا رسیدن به مرکز بیس)',
                'win_probability' => $playerTh >= $targetTownHall ? 94 : max(55, 90 - (($targetTownHall - $playerTh) * 20)),
            ],
            'zap_titan' => [
                'name' => 'زپ الکترو تایتان + اسماش (Zap Titan Smash)',
                'siege' => 'Log Launcher یا Flame Flinger',
                'phase1_funnel' => 'با ۴ اسپل رعد و ۱ زلزله اسپل تاور یا اینفرنوی کلیدی حریف را تخریب کنید. فلیم فلینگر را در گوشه امن رها کنید.',
                'phase2_core' => 'الکترو تایتان‌ها، هیلرها و هیروها را پشت لاگ لانچر بفرستید تا آرورا تایتان تمام اسکلت‌ها و ترپ‌ها را بسوزاند.',
                'phase3_cleanup' => 'با اسپل‌های خشم و هیل حرکت به سمت تاون‌هال را تقویت کرده و با هدهانترها هیروهای مدافع را نابود کنید.',
                'warden_timing' => 'دقیقه ۱:۴۵ (در زمان خروج نیروهای کلن کستل حریف)',
                'win_probability' => $playerTh >= $targetTownHall ? 92 : max(50, 88 - (($targetTownHall - $playerTh) * 22)),
            ],
            'queen_charge_hybrid' => [
                'name' => 'کویین شارژ هیبرید (QC Hybrid)',
                'siege' => 'Siege Barracks',
                'phase1_funnel' => 'کویین + ۵ هیلر را برای ورود به محوطه تاون‌هال یا ایگل هدایت کنید. با ریج و فریز از کویین محافظت کنید.',
                'phase2_core' => 'کینگ و سیج باراکس را در یک سمت برای ایجاد فانل L-Shape رها کرده و هاگ ریدرها و ماینرها را از میان بیس بفرستید.',
                'phase3_cleanup' => 'از اسپل‌های هیل در محوطه بمب تاورها و جاینت بمب‌ها استفاده کنید و با ابیلیتی RC دفاعی‌های دوردست را بزنید.',
                'warden_timing' => 'دقیقه ۱:۳۰ (در محوطه گیگا تسلا یا منولیت)',
                'win_probability' => $playerTh >= $targetTownHall ? 95 : max(52, 92 - (($targetTownHall - $playerTh) * 20)),
            ],
        ];

        $preset = $armyPresets[$armyType] ?? $armyPresets['root_rider_smash'];

        return [
            'ok' => true,
            'player_th' => $playerTh,
            'target_th' => $targetTownHall,
            'army_name' => $preset['name'],
            'siege_engine' => $preset['siege'],
            'win_probability' => $preset['win_probability'],
            'phases' => [
                [
                    'phase_number' => 1,
                    'title' => 'فاز ۱: فانلینگ و باز کردن کریدور ورود',
                    'time_window' => '۳:۰۰ تا ۲:۲۰',
                    'instruction' => $preset['phase1_funnel'],
                    'badge' => 'فاز مقدماتی',
                ],
                [
                    'phase_number' => 2,
                    'title' => 'فاز ۲: نفوذ به هسته و انفجار تاون‌هال',
                    'time_window' => '۲:۲۰ تا ۱:۱۰',
                    'instruction' => $preset['phase2_core'],
                    'badge' => 'فاز اصلی (Core Push)',
                ],
                [
                    'phase_number' => 3,
                    'title' => 'فاز ۳: پاکسازی محوطه با اسپل‌ها و رویال چمپیون',
                    'time_window' => '۱:۱۰ تا پایان',
                    'instruction' => $preset['phase3_cleanup'],
                    'badge' => 'فاز نهایی (Clean-up)',
                ],
            ],
            'key_tactics' => [
                'زمان‌بندی ابیلیتی واردن:' => $preset['warden_timing'],
                'مدیریت اسپل‌ها:' => 'هیچگاه اسپل‌ها را همزمان مصرف نکنید؛ اسپل فریز را برای لحظه قفل شدن منولیت روی هیرو نگه دارید.',
                'تارگت کلن کستل:' => 'نیروهای CC حریف با اسپل سم (Poison) + آرورا تایتان یا هدهانترها در چند ثانیه خنثی می‌شوند.',
            ],
        ];
    }
}
