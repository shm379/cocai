<?php

namespace App\Services\BaseClone;

/**
 * سقف تعداد ساختمان‌ها به ازای هر تاون‌هال (دهکدهٔ اصلی، TH9 تا TH17).
 *
 * منبع: جدول «Maximum Number of Buildings» صفحهٔ Town Hall ویکی کلش (MediaWiki API).
 * برای TH16/17 که ادغام (merge) ساختمان‌ها ممکن است کامل نشده باشد، سقف تکیِ کنون/آرچر تاور
 * همان مقدار پیش از ادغام است و علاوه بر آن یک «سقف گروهی» اعمال می‌شود:
 *   cannon + 2·ricochet_cannon + multi_gear_tower ≤ 7
 *   archer_tower + 2·multi_archer_tower + multi_gear_tower ≤ 8 (TH17: 9)
 *
 * هیچ عددی حدس زده نمی‌شود؛ برای تاون‌هال‌های خارج از جدول، بیلدر بیس یا تاون‌هال نامشخص
 * سقفی اعمال نمی‌شود (applies() = false).
 */
class BuildingCaps
{
    public const MIN_TH = 9;

    public const MAX_TH = 17;

    /**
     * سقف هر نوع به ازای TH9..TH17 (ایندکس ۰ = TH9). null = بدون سقف (یا نوع ناشناخته).
     *
     * @var array<string, array<int, int>>
     */
    protected const CAPS = [
        //                       TH9 10 11 12 13 14 15 16 17
        'cannon' => [5, 6, 7, 7, 7, 7, 7, 7, 7],
        'archer_tower' => [6, 7, 8, 8, 8, 8, 8, 8, 9],
        'ricochet_cannon' => [0, 0, 0, 0, 0, 0, 0, 2, 3],
        'multi_archer_tower' => [0, 0, 0, 0, 0, 0, 0, 2, 3],
        'multi_gear_tower' => [0, 0, 0, 0, 0, 0, 0, 0, 1],
        'firespitter' => [0, 0, 0, 0, 0, 0, 0, 0, 2],
        'mortar' => [4, 4, 4, 4, 4, 4, 4, 4, 4],
        'air_defense' => [4, 4, 4, 4, 4, 4, 4, 4, 4],
        'wizard_tower' => [4, 4, 5, 5, 5, 5, 5, 5, 5],
        'air_sweeper' => [2, 2, 2, 2, 2, 2, 2, 2, 2],
        'hidden_tesla' => [4, 4, 4, 5, 5, 5, 5, 5, 5],
        'bomb_tower' => [1, 2, 2, 2, 2, 2, 2, 2, 2],
        'x_bow' => [2, 3, 4, 4, 4, 4, 4, 4, 4],
        'inferno_tower' => [0, 2, 2, 3, 3, 3, 3, 3, 3],
        'eagle_artillery' => [0, 0, 1, 1, 1, 1, 1, 1, 1],
        'scattershot' => [0, 0, 0, 0, 2, 2, 2, 2, 2],
        'spell_tower' => [0, 0, 0, 0, 0, 0, 2, 2, 2],
        'monolith' => [0, 0, 0, 0, 0, 0, 1, 1, 1],

        'gold_mine' => [7, 7, 7, 7, 7, 7, 7, 7, 7],
        'elixir_collector' => [7, 7, 7, 7, 7, 7, 7, 7, 7],
        'dark_elixir_drill' => [3, 3, 3, 3, 3, 3, 3, 3, 3],
        'gold_storage' => [4, 4, 4, 4, 4, 4, 4, 4, 4],
        'elixir_storage' => [4, 4, 4, 4, 4, 4, 4, 4, 4],
        'dark_elixir_storage' => [1, 1, 1, 1, 1, 1, 1, 1, 1],

        'town_hall' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'clan_castle' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'hero_hall' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'helper_hut' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'army_camp' => [4, 4, 4, 4, 4, 4, 4, 4, 4],
        'barracks' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'dark_barracks' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'laboratory' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'spell_factory' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'dark_spell_factory' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'blacksmith' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'workshop' => [0, 0, 0, 1, 1, 1, 1, 1, 1],
        'pet_house' => [0, 0, 0, 0, 0, 1, 1, 1, 1],
        // ۵ کلبه + کلبهٔ B.O.B از TH10 (alias به builder_hut)
        'builder_hut' => [5, 6, 6, 6, 6, 6, 6, 6, 6],

        // محراب هیروها فقط در اسکرین‌شات‌های پیش از Hero Hall؛ حداکثر ۱ عدد از هر کدام
        'barbarian_king' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'archer_queen' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
        'grand_warden' => [0, 0, 1, 1, 1, 1, 1, 1, 1],
        'royal_champion' => [0, 0, 0, 0, 1, 1, 1, 1, 1],
        'minion_prince' => [1, 1, 1, 1, 1, 1, 1, 1, 1],
    ];

    /** @var array<int, int> سقف تعداد خانه‌های دیوار، TH9..TH17 */
    protected const WALLS = [250, 275, 300, 300, 325, 325, 325, 325, 325];

    /** @var array<int, int> کل ساختمان‌ها (با تاون‌هال و کلبه‌ها، ادغام‌ها انجام‌شده)، TH9..TH17 */
    protected const TOTALS = [77, 84, 89, 92, 94, 95, 98, 94, 93];

    /**
     * سقف‌های گروهی برای ساختمان‌های ادغام‌شونده: [وزن هر نوع, سقف به ازای TH9..TH17].
     *
     * @var array<string, array{weights: array<string, int>, caps: array<int, int>}>
     */
    protected const GROUPS = [
        'cannon_group' => [
            'weights' => ['cannon' => 1, 'ricochet_cannon' => 2, 'multi_gear_tower' => 1],
            'caps' => [5, 6, 7, 7, 7, 7, 7, 7, 7],
        ],
        'archer_group' => [
            'weights' => ['archer_tower' => 1, 'multi_archer_tower' => 2, 'multi_gear_tower' => 1],
            'caps' => [6, 7, 8, 8, 8, 8, 8, 8, 9],
        ],
    ];

    /**
     * آیا سقف‌ها برای این ترکیب معتبرند؟ فقط دهکدهٔ اصلی و TH9..TH17.
     */
    public static function applies(?int $th, string $village = 'home'): bool
    {
        return $village === 'home' && $th !== null && $th >= self::MIN_TH && $th <= self::MAX_TH;
    }

    /**
     * سقف یک نوع در یک تاون‌هال؛ null = سقفی تعریف نشده (نوع ناشناخته یا TH خارج از جدول).
     */
    public static function max(string $type, ?int $th): ?int
    {
        if (! self::applies($th) || ! isset(self::CAPS[$type])) {
            return null;
        }

        return self::CAPS[$type][$th - self::MIN_TH];
    }

    public static function wallCap(?int $th): ?int
    {
        return self::applies($th) ? self::WALLS[$th - self::MIN_TH] : null;
    }

    public static function total(?int $th): ?int
    {
        return self::applies($th) ? self::TOTALS[$th - self::MIN_TH] : null;
    }

    /**
     * سقف‌های گروهی برای یک تاون‌هال.
     *
     * @return array<string, array{weights: array<string, int>, cap: int}>
     */
    public static function groups(?int $th): array
    {
        if (! self::applies($th)) {
            return [];
        }

        $out = [];
        foreach (self::GROUPS as $name => $group) {
            $out[$name] = ['weights' => $group['weights'], 'cap' => $group['caps'][$th - self::MIN_TH]];
        }

        return $out;
    }

    /**
     * انواع دارای سقف.
     *
     * @return array<int, string>
     */
    public static function types(): array
    {
        return array_keys(self::CAPS);
    }
}
