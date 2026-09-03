<?php

namespace Database\Seeders;

use App\Models\Map;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class ClashMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * توجه: این نقشه‌ها نمونه‌های نمایشی‌اند و «لینک کپی داخل بازی» ندارند (copy_link = null).
     * لینک OpenLayout یک شناسهٔ ۳۲ کاراکتری است که فقط خود بازی صادر می‌کند و قابل ساختن نیست؛
     * شناسه‌های جای‌گذار قبلی (مثل TH18_WAR_META_01) در بازی باز نمی‌شدند و Base Cloner آن‌ها را
     * به‌اشتباه به‌عنوان لینک واقعی برمی‌گرداند. لینک‌های واقعی فقط از آرشیو Clasher.us
     * (php artisan fetch:clasher) می‌آیند و با Map::hasValidCopyLink() بررسی می‌شوند.
     * map_link فقط کلید یکتای رکورد است و به‌عنوان لینک بازی استفاده نمی‌شود.
     */
    public function run(): void
    {
        $thMaps = [
            // TH 18
            [
                'name' => 'مپ وار افسانه‌ای TH18 — آنتی ۳ ستاره متای جدید',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH18_WAR_META_01',
                'copy_link' => null,
                'view_count' => 14200,
                'download_count' => 5200,
                'like_count' => 980,
                'hall_type' => 0,
                'hall_level' => 18,
                'topic_name' => 'Town Hall 18 War Base',
            ],
            // TH 17
            [
                'name' => 'مپ متای وار و لیگ CWL تاون‌هال ۱۷ — ضد الکترودراگون و روت رایدر',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH17_WAR_META_02',
                'copy_link' => null,
                'view_count' => 28500,
                'download_count' => 11200,
                'like_count' => 1840,
                'hall_type' => 0,
                'hall_level' => 17,
                'topic_name' => 'Town Hall 17 War Base',
            ],
            [
                'name' => 'مپ کاپ‌گیری لجند لیگ تاون‌هال ۱۷ — حفظ منابع و مونوکاستر',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH17_TROPHY_01',
                'copy_link' => null,
                'view_count' => 19300,
                'download_count' => 7400,
                'like_count' => 1250,
                'hall_type' => 0,
                'hall_level' => 17,
                'topic_name' => 'Town Hall 17 Trophy Base',
            ],
            // TH 16
            [
                'name' => 'مپ وار آنتی ۲ ستاره تاون‌هال ۱۶ — محافظت از ریکوشت کنون و منولیت',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH16_WAR_ANTI3',
                'copy_link' => null,
                'view_count' => 45000,
                'download_count' => 18900,
                'like_count' => 3100,
                'hall_type' => 0,
                'hall_level' => 16,
                'topic_name' => 'Town Hall 16 War Base',
            ],
            [
                'name' => 'مپ فارمینگ و محافظت از دارک اکسیر تاون‌هال ۱۶',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH16_FARM_01',
                'copy_link' => null,
                'view_count' => 22000,
                'download_count' => 8900,
                'like_count' => 1430,
                'hall_type' => 0,
                'hall_level' => 16,
                'topic_name' => 'Town Hall 16 Farming Base',
            ],
            // TH 15
            [
                'name' => 'مپ وار لیگ قهرمانان TH15 — تاون‌هال ایزوله با اسپل تاورهای خشم و سم',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH15_WAR_LEAGUE',
                'copy_link' => null,
                'view_count' => 38000,
                'download_count' => 15600,
                'like_count' => 2650,
                'hall_type' => 0,
                'hall_level' => 15,
                'topic_name' => 'Town Hall 15 War Base',
            ],
            // TH 14
            [
                'name' => 'مپ هایبرید و وار تاون‌هال ۱۴ — ضد سوپر دراگون و کویین شارژ',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH14_HYBRID_01',
                'copy_link' => null,
                'view_count' => 31000,
                'download_count' => 13400,
                'like_count' => 2100,
                'hall_type' => 0,
                'hall_level' => 14,
                'topic_name' => 'Town Hall 14 War Base',
            ],
            // TH 13
            [
                'name' => 'مپ وار آنتی ۳ ستاره تاون‌هال ۱۳ — محافظت از ایگل و گیگا اینفرنو',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH13_WAR_ANTI3',
                'copy_link' => null,
                'view_count' => 27000,
                'download_count' => 11800,
                'like_count' => 1890,
                'hall_type' => 0,
                'hall_level' => 13,
                'topic_name' => 'Town Hall 13 War Base',
            ],
            // TH 12
            [
                'name' => 'مپ وار و کاپ تاون‌هال ۱۲ — تله‌های مرگبار ضد هاگ و بالون',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH12_WAR_01',
                'copy_link' => null,
                'view_count' => 24000,
                'download_count' => 10200,
                'like_count' => 1650,
                'hall_type' => 0,
                'hall_level' => 12,
                'topic_name' => 'Town Hall 12 War Base',
            ],
            // TH 11
            [
                'name' => 'مپ متای تاون‌هال ۱۱ — محافظت ویژه از ایگل آرتیلری و کلن کستل',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH11_WAR_EAGLE',
                'copy_link' => null,
                'view_count' => 18500,
                'download_count' => 7800,
                'like_count' => 1240,
                'hall_type' => 0,
                'hall_level' => 11,
                'topic_name' => 'Town Hall 11 War Base',
            ],
        ];

        $bhMaps = [
            // BH 10
            [
                'name' => 'مپ ۲ مرحله‌ای بیلدرهال ۱۰ — آنتی ۶ ستاره با محافظت از اکس-بو و مگا تسلا',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=BH10_META_01',
                'copy_link' => null,
                'view_count' => 16500,
                'download_count' => 6900,
                'like_count' => 1120,
                'hall_type' => 1,
                'hall_level' => 10,
                'topic_name' => 'Builder Hall 10 War Base',
            ],
            // BH 9
            [
                'name' => 'مپ کاپ‌گیری بیلدرهال ۹ با لاوا لانچر مرکزی برای باز کردن ربات کارگر O.T.T.O',
                'image_url' => 'https://api-assets.clashofclans.com/leagues/288/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'thumbnail_url' => 'https://api-assets.clashofclans.com/leagues/72/R2zmhyqQ0_lKcDR5EyghXCxghC9E45Tma1OHCXQ272Y.png',
                'map_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=BH9_OTTO_FAST',
                'copy_link' => null,
                'view_count' => 22000,
                'download_count' => 9800,
                'like_count' => 1560,
                'hall_type' => 1,
                'hall_level' => 9,
                'topic_name' => 'Builder Hall 9 War Base',
            ],
        ];

        $allMaps = array_merge($thMaps, $bhMaps);

        foreach ($allMaps as $mapData) {
            $topic = Topic::firstOrCreate(
                ['name' => $mapData['topic_name']],
                [
                    'hall_type' => $mapData['hall_type'],
                    'hall_level' => $mapData['hall_level'],
                ]
            );

            $map = Map::updateOrCreate(
                ['map_link' => $mapData['map_link']],
                [
                    'name' => $mapData['name'],
                    'image_url' => $mapData['image_url'],
                    'thumbnail_url' => $mapData['thumbnail_url'],
                    'copy_link' => $mapData['copy_link'],
                    'view_count' => $mapData['view_count'],
                    'download_count' => $mapData['download_count'],
                    'like_count' => $mapData['like_count'],
                    'report_count' => 0,
                    'created_at' => now(),
                ]
            );

            if (! $map->topics()->where('topics.id', $topic->id)->exists()) {
                $map->topics()->attach($topic->id);
            }
        }
    }
}
