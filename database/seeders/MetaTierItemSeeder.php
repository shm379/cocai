<?php

namespace Database\Seeders;

use App\Models\MetaTierItem;
use Illuminate\Database\Seeder;

class MetaTierItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => '💥 روت رایدر اسمش + اسپل اوورگروث (Root Rider Smash)',
                'category' => 'army',
                'town_hall_min' => 15,
                'town_hall_max' => 18,
                'tier' => 'S_PLUS',
                'win_rate_percentage' => 97,
                'difficulty_rating' => 2,
                'army_link' => 'https://link.clashofclans.com/en?action=CopyArmy&army=u8x110-4x107-1x82-3x53s2x2-2x5-1x11-2x53',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'tactical_brief_fa' => 'مرگبارترین ترکیب متای ۲۰۲۶ برای اتک‌های ۳ ستاره وار. با اسپل Overgrowth بخش‌های سنگین دفاعی (منولیت و اسپل تاورها) را ایزوله کنید، سپس با ۸ روت رایدر و والکری‌ها از مرکز بیس عبور کنید. ابیلیتی گرند واردن را دقیقاً قبل از ورود به تان‌هال فعال کنید.',
                'units_payload' => [
                    'troops' => ['8x Root Rider', '4x Valkyrie', '1x Ice Golem', '3x Super Wall Breaker', '2x Wizard', '3x Headhunter'],
                    'spells' => ['2x Rage Spell', '2x Freeze Spell', '2x Overgrowth Spell', '1x Poison Spell'],
                    'siege' => 'Siege Barracks یا Battle Drill',
                ],
                'equipment_payload' => [
                    'Barbarian King' => 'Giant Gauntlet + Spiky Ball',
                    'Archer Queen' => 'Frozen Arrow + Invisibility Vial',
                    'Grand Warden' => 'Eternal Tome + Healing Tome',
                    'Royal Champion' => 'Rocket Spear + Haste Vial',
                ],
                'is_featured' => true,
                'views_count' => 18400,
                'copies_count' => 8200,
            ],
            [
                'title' => '👑 ست تجهیزات هیروی گادمود (God-Mode Equipment Loadout)',
                'category' => 'equipment',
                'town_hall_min' => 12,
                'town_hall_max' => 18,
                'tier' => 'S_PLUS',
                'win_rate_percentage' => 95,
                'difficulty_rating' => 1,
                'army_link' => null,
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'tactical_brief_fa' => 'بهترین سینرژی تجهیزات اپیک قهرمانان برای پیروزی ۱۰۰٪ در وارها. دستکش غول‌پیکر (Giant Gauntlet) به پادشاه بربر توانایی تخریب محوطه ۶ کاشی با ۵۰٪ کاهش آسیب وارده می‌دهد. تیر منجمد کننده (Frozen Arrow) کویین هر دفاع تک‌هدفه از جمله اینفرنو را فلج می‌کند.',
                'units_payload' => null,
                'equipment_payload' => [
                    'Barbarian King' => ['Giant Gauntlet (Lv 18+)', 'Rage Vial یا Spiky Ball'],
                    'Archer Queen' => ['Frozen Arrow (Lv 18+)', 'Invisibility Vial (Lv 15+)'],
                    'Grand Warden' => ['Eternal Tome (Lv 18)', 'Healing Tome (Lv 15+)'],
                    'Royal Champion' => ['Seeking Shield', 'Haste Vial (Lv 15+)'],
                ],
                'is_featured' => true,
                'views_count' => 24000,
                'copies_count' => 11500,
            ],
            [
                'title' => '⚡ سوپر آرچر بلیمپ + دراگون ریدر (SArch Blimp Hydra)',
                'category' => 'attack_combo',
                'town_hall_min' => 14,
                'town_hall_max' => 17,
                'tier' => 'S',
                'win_rate_percentage' => 92,
                'difficulty_rating' => 4,
                'army_link' => 'https://link.clashofclans.com/en?action=CopyArmy&army=u5x65-4x87-6x8-1x53s5x35-1x2-1x5-1x11',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'tactical_brief_fa' => 'حمله تخریب هسته بیس در ۲۰ ثانیه اول نبرد. بتل بلیمپ حاوی ۳ سوپر آرچر را همراه با واردن و لاوا به سمت مرکز بیس بفرستید. بلافاصله پس از ترکیدن بلیمپ، اسپل نامرئی (Invis) + کلون (Clone) + خشم (Rage) بیندازید تا تاون‌هال، ایگل و منولیت یکجا پودر شوند.',
                'units_payload' => [
                    'troops' => ['5x Dragon Rider', '4x Dragon', '6x Balloon', '1x Lava Hound', '2x Baby Dragon'],
                    'spells' => ['5x Invisibility Spell', '1x Clone Spell', '1x Rage Spell'],
                    'clan_castle' => '3x Super Archer + 1x Wall Breaker',
                ],
                'equipment_payload' => [
                    'Grand Warden' => 'Eternal Tome + Fireball',
                ],
                'is_featured' => true,
                'views_count' => 15200,
                'copies_count' => 6400,
            ],
            [
                'title' => '🌾 فرمول فارم ۱ ساعته ۲۰ میلیون طلا و اکسیر با اسنیکی گابلین',
                'category' => 'army',
                'town_hall_min' => 11,
                'town_hall_max' => 18,
                'tier' => 'S_PLUS',
                'win_rate_percentage' => 99,
                'difficulty_rating' => 1,
                'army_link' => 'https://link.clashofclans.com/en?action=CopyArmy&army=u85x55-6x53s4x5-3x28',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'tactical_brief_fa' => 'سریع‌ترین روش پر کردن مخازن برای روشن نگه داشتن تمام ۶ کارگر. در کاپ Master III با رهاسازی ۳ گوبلین مخفی روی هر مخزن یا کالکتور بیرونی، در هر حمله زیر ۳۰ ثانیه ۸۰۰ هزار لوت کسب کنید و فوراً بتل را Surrender کنید.',
                'units_payload' => [
                    'troops' => ['85x Sneaky Goblin', '6x Super Wall Breaker'],
                    'spells' => ['4x Jump Spell', '3x Haste Spell', '1x Invisibility Spell'],
                ],
                'equipment_payload' => null,
                'is_featured' => true,
                'views_count' => 31000,
                'copies_count' => 16200,
            ],
        ];

        foreach ($items as $item) {
            MetaTierItem::updateOrCreate(
                ['title' => $item['title']],
                $item
            );
        }
    }
}
