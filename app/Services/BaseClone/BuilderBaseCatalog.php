<?php

namespace App\Services\BaseClone;

/**
 * کاتالوگ ساختمان‌های بیلدر بیس (Builder Base 2.0).
 *
 * ابعاد تقریبی بر اساس بازی: بیلدر هال ۴×۴، تسلا و کلبهٔ درمان ۲×۲، بقیه ۳×۳.
 */
class BuilderBaseCatalog extends BuildingCatalog
{
    protected const ITEMS = [
        'builder_hall' => ['size' => 4, 'label' => 'بیلدر هال', 'color' => '#f59e0b', 'category' => 'core', 'icon' => '🏛️'],
        'battle_machine' => ['size' => 3, 'label' => 'بتل ماشین', 'color' => '#eab308', 'category' => 'hero', 'icon' => '🤖'],
        'battle_copter' => ['size' => 3, 'label' => 'بتل کوپتر', 'color' => '#22d3ee', 'category' => 'hero', 'icon' => '🚁'],

        'cannon' => ['size' => 3, 'label' => 'کنون', 'color' => '#6b7280', 'category' => 'defense', 'icon' => '💣'],
        'double_cannon' => ['size' => 3, 'label' => 'دابل کنون', 'color' => '#4b5563', 'category' => 'defense', 'icon' => '💣'],
        'archer_tower' => ['size' => 3, 'label' => 'آرچر تاور', 'color' => '#84cc16', 'category' => 'defense', 'icon' => '🗼'],
        'hidden_tesla' => ['size' => 2, 'label' => 'تسلا', 'color' => '#facc15', 'category' => 'defense', 'icon' => '⚡'],
        'firecrackers' => ['size' => 3, 'label' => 'فایرکرکرز', 'color' => '#f97316', 'category' => 'defense', 'icon' => '🎆'],
        'crusher' => ['size' => 3, 'label' => 'کراشر', 'color' => '#78350f', 'category' => 'defense', 'icon' => '🔨'],
        'guard_post' => ['size' => 3, 'label' => 'گارد پست', 'color' => '#65a30d', 'category' => 'defense', 'icon' => '🛡️'],
        'mega_tesla' => ['size' => 3, 'label' => 'مگا تسلا', 'color' => '#fde047', 'category' => 'defense', 'icon' => '⚡'],
        'giant_cannon' => ['size' => 3, 'label' => 'جاینت کنون', 'color' => '#374151', 'category' => 'defense', 'icon' => '💥'],
        'multi_mortar' => ['size' => 3, 'label' => 'مولتی مورتار', 'color' => '#57534e', 'category' => 'defense', 'icon' => '🎯'],
        'roaster' => ['size' => 3, 'label' => 'روستر', 'color' => '#ea580c', 'category' => 'defense', 'icon' => '🔥'],
        'air_bombs' => ['size' => 3, 'label' => 'ایر بامبز', 'color' => '#ef4444', 'category' => 'defense', 'icon' => '🎈'],
        'lava_launcher' => ['size' => 3, 'label' => 'لاوا لانچر', 'color' => '#dc2626', 'category' => 'defense', 'icon' => '🌋'],
        'x_bow' => ['size' => 3, 'label' => 'ایکس‌بو', 'color' => '#0ea5e9', 'category' => 'defense', 'icon' => '🏹'],

        'gem_mine' => ['size' => 3, 'label' => 'معدن جم', 'color' => '#22c55e', 'category' => 'resource', 'icon' => '💎'],
        'gold_mine' => ['size' => 3, 'label' => 'معدن طلا', 'color' => '#fde047', 'category' => 'resource', 'icon' => '⛏️'],
        'elixir_collector' => ['size' => 3, 'label' => 'کلکتور الکسیر', 'color' => '#f0abfc', 'category' => 'resource', 'icon' => '🧪'],
        'gold_storage' => ['size' => 3, 'label' => 'انبار طلا', 'color' => '#ca8a04', 'category' => 'resource', 'icon' => '💰'],
        'elixir_storage' => ['size' => 3, 'label' => 'انبار الکسیر', 'color' => '#d946ef', 'category' => 'resource', 'icon' => '🧫'],

        'army_camp' => ['size' => 3, 'label' => 'ارتش‌کمپ', 'color' => '#16a34a', 'category' => 'army', 'icon' => '⛺'],
        'builder_barracks' => ['size' => 3, 'label' => 'بیلدر بارکس', 'color' => '#15803d', 'category' => 'army', 'icon' => '🏚️'],
        'star_laboratory' => ['size' => 3, 'label' => 'استار لب', 'color' => '#0d9488', 'category' => 'army', 'icon' => '⚗️'],
        'reinforcement_camp' => ['size' => 3, 'label' => 'کمپ پشتیبانی', 'color' => '#166534', 'category' => 'army', 'icon' => '🏕️'],

        'clock_tower' => ['size' => 3, 'label' => 'کلاک تاور', 'color' => '#a855f7', 'category' => 'other', 'icon' => '⏰'],
        'otto_outpost' => ['size' => 3, 'label' => 'پایگاه اوتو', 'color' => '#9ca3af', 'category' => 'other', 'icon' => '🛰️'],
        'healing_hut' => ['size' => 2, 'label' => 'کلبهٔ درمان', 'color' => '#f472b6', 'category' => 'other', 'icon' => '💗'],
    ];

    protected const ALIASES = [
        'builderhall' => 'builder_hall', 'bh' => 'builder_hall',
        'machine' => 'battle_machine', 'copter' => 'battle_copter',
        'tesla' => 'hidden_tesla', 'xbow' => 'x_bow', 'x-bow' => 'x_bow',
        'archer' => 'archer_tower', 'mortar' => 'multi_mortar',
        'lab' => 'star_laboratory', 'laboratory' => 'star_laboratory',
        'barracks' => 'builder_barracks', 'camp' => 'army_camp',
        'otto' => 'otto_outpost', 'clock' => 'clock_tower',
        'walls' => self::WALL, 'wall_segment' => self::WALL,
    ];

    public function key(): string
    {
        return 'builder';
    }

    public function villageLabel(): string
    {
        return 'Builder Base';
    }
}
