<?php

namespace App\Services\BaseClone;

/**
 * آمار چیدمان (بخش stats خروجی LayoutGridMapper).
 *
 * placed_count + unplaced_count + trimmed_count = building_count.
 * uncertain_count شامل همهٔ ساختمان‌هایی است که uncertain=true دارند (جانشده، حذف‌شده با سقف،
 * جابه‌جایی ≥ ۲ خانه، لبهٔ خانه یا ناسازگاری اندازهٔ جعبه).
 */
class LayoutStats
{
    /**
     * @param  array<int, array<string, mixed>>  $buildings  خروجی نهایی ساختمان‌ها (با placed/uncertain/flags)
     * @param  array<string, mixed>  $extra  فیلدهای اضافی (مثلاً expected_total، wall_cap)
     * @return array<string, mixed>
     */
    public static function build(array $buildings, int $wallCount, array $extra = []): array
    {
        $byCategory = [];
        $byType = [];
        $placed = 0;
        $unplaced = 0;
        $trimmed = 0;
        $uncertain = 0;

        foreach ($buildings as $b) {
            $flags = $b['flags'] ?? [];
            if (! empty($b['uncertain'])) {
                $uncertain++;
            }
            if (in_array('cap_trimmed', $flags, true)) {
                $trimmed++;

                continue;
            }
            if (empty($b['placed'])) {
                $unplaced++;

                continue;
            }
            $placed++;
            $byCategory[$b['category']] = ($byCategory[$b['category']] ?? 0) + 1;
            $byType[$b['type']] = ($byType[$b['type']] ?? 0) + 1;
        }

        return array_merge([
            'building_count' => count($buildings),
            'placed_count' => $placed,
            'unplaced_count' => $unplaced,
            'trimmed_count' => $trimmed,
            'uncertain_count' => $uncertain,
            'wall_count' => $wallCount,
            'by_category' => $byCategory,
            'by_type' => $byType,
        ], $extra);
    }
}
