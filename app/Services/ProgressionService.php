<?php

namespace App\Services;

use App\Services\GameData\GameDataService;

/**
 * موتور قطعی تحلیل پیشرفت بازیکن.
 *
 * اصل طراحی: هیچ عدد حدسی وارد خروجی نمی‌شود.
 *  - سقف هیروها از جدول مرجع per-TH (دقیق و نگهداری‌شده).
 *  - سقف نیروها/طلسم‌ها از فیلد maxLevel خود API بازی (سقف کل بازی).
 *  - اولویت‌بندی از دانش استراتژیک (ارتش‌های متا برای TH بازیکن).
 *
 * خروجی analyze() مبنای همهٔ توصیه‌هاست: تسک روزانه، تقویم، و context مدل زبانی.
 */
class ProgressionService
{
    // نیروهای سوپر در لیست troops می‌آیند ولی قابل آپگرید نیستند
    private const SUPER_TROOP_PREFIXES = ['Super ', 'Sneaky ', 'Rocket ', 'Inferno ', 'Ice Hound'];

    public function __construct(private GameDataService $gameData)
    {
    }

    public function analyze(array $playerData): array
    {
        $th = (int) ($playerData['townHallLevel'] ?? 0);

        if ($th < 2) {
            return ['ok' => false, 'reason' => 'invalid_player_data'];
        }

        $heroes = $this->analyzeHeroes($playerData, $th);
        $lab = $this->analyzeLab($playerData, $th);
        $queue = $this->buildUpgradeQueue($heroes, $lab);
        $rush = $this->rushScore($heroes, $th);
        $armies = $this->gameData->armiesForTh($th);

        return [
            'ok' => true,
            'data_version' => $this->gameData->dataVersion(),
            'town_hall' => $th,
            'player_name' => $playerData['name'] ?? null,
            'trophies' => $playerData['trophies'] ?? null,
            'heroes' => $heroes,
            'lab' => $lab,
            'rush' => $rush,
            'upgrade_queue' => $queue,
            'armies' => $armies,
            'summary_fa' => $this->summaryFa($th, $heroes, $lab, $rush, $queue),
        ];
    }

    /**
     * هیروها: لِوِل فعلی در برابر سقف TH فعلی و سقف TH قبلی.
     */
    private function analyzeHeroes(array $playerData, int $th): array
    {
        $result = [];

        foreach ($playerData['heroes'] ?? [] as $hero) {
            if (($hero['village'] ?? 'home') !== 'home') {
                continue;
            }

            $name = $hero['name'] ?? '';
            $level = (int) ($hero['level'] ?? 0);
            $cap = $this->gameData->heroCap($name, $th);

            if ($cap === null) {
                continue; // هیروی بیلدربیس یا خارج از جدول مرجع
            }

            $prevCap = $this->gameData->heroCapPreviousTh($name, $th);

            $result[] = [
                'name' => $name,
                'level' => $level,
                'cap' => $cap,
                'deficit' => max(0, $cap - $level),
                'percent' => $cap > 0 ? (int) round($level / $cap * 100) : 100,
                // زیر سقفِ TH قبلی بودن = نشانهٔ کلاسیک راش
                'below_previous_th' => $prevCap !== null && $level < $prevCap,
            ];
        }

        // هیروهایی که در این TH باز می‌شوند ولی بازیکن هنوز ندارد
        $owned = array_column($result, 'name');
        foreach ($this->gameData->heroNames() as $name) {
            if (! in_array($name, $owned, true) && $this->gameData->heroCap($name, $th) !== null) {
                $result[] = [
                    'name' => $name,
                    'level' => 0,
                    'cap' => $this->gameData->heroCap($name, $th),
                    'deficit' => $this->gameData->heroCap($name, $th),
                    'percent' => 0,
                    'below_previous_th' => true,
                    'not_unlocked' => true,
                ];
            }
        }

        return $result;
    }

    /**
     * لَب: هر نیرو/طلسم خانگی نسبت به سقف کل بازی (maxLevel از خود API).
     * یونیت‌های ارتش جنگی TH بازیکن پرچم war_unit می‌گیرند — اولویت لَب.
     */
    private function analyzeLab(array $playerData, int $th): array
    {
        $warUnits = $this->gameData->warUnitNamesForTh($th);
        $items = [];

        foreach (['troops', 'spells'] as $group) {
            foreach ($playerData[$group] ?? [] as $unit) {
                if (($unit['village'] ?? 'home') !== 'home') {
                    continue;
                }

                $name = $unit['name'] ?? '';
                if ($this->isSuperTroop($name)) {
                    continue;
                }

                $level = (int) ($unit['level'] ?? 0);
                $maxLevel = (int) ($unit['maxLevel'] ?? $level);
                $meta = $this->gameData->unitMeta($name);

                $items[] = [
                    'name' => $name,
                    'group' => $group,
                    'category' => $meta['category'] ?? null,
                    'level' => $level,
                    'game_max' => $maxLevel,
                    'maxed' => $level >= $maxLevel,
                    'war_unit' => in_array($name, $warUnits, true),
                    'priority' => $meta['priority'] ?? 1,
                ];
            }
        }

        $total = count($items);
        $levelSum = array_sum(array_column($items, 'level'));
        $maxSum = array_sum(array_column($items, 'game_max'));

        return [
            'items' => $items,
            'count' => $total,
            // درصد نسبت به سقف کل بازی — برای مقایسهٔ بین بازیکنان معنا دارد
            'overall_percent_of_game_max' => $maxSum > 0 ? (int) round($levelSum / $maxSum * 100) : 0,
        ];
    }

    /**
     * صف آپگرید رتبه‌بندی‌شده. منطق:
     *  ۱. هیروهای زیر سقف TH قبلی (راش) — فوری‌ترین.
     *  ۲. بقیهٔ هیروهای زیر سقف TH فعلی.
     *  ۳. یونیت‌های ارتش جنگی TH بازیکن که هنوز maxed نیستند (به ترتیب priority).
     *  ۴. سایر یونیت‌ها به ترتیب priority.
     */
    private function buildUpgradeQueue(array $heroes, array $lab): array
    {
        $queue = [];

        foreach ($heroes as $hero) {
            if ($hero['deficit'] <= 0) {
                continue;
            }

            $queue[] = [
                'type' => 'hero',
                'name' => $hero['name'],
                'current' => $hero['level'],
                'target' => $hero['cap'],
                'urgent' => $hero['below_previous_th'],
                'reason_fa' => ! empty($hero['not_unlocked'])
                    ? 'این هیرو در تاون‌هال شما باز می‌شود — در اولین فرصت بخر و فعالش کن.'
                    : ($hero['below_previous_th']
                        ? 'زیر سقف تاون‌هال قبلی است؛ نشانهٔ راش. هیروها مهم‌ترین سرمایهٔ حمله‌اند.'
                        : 'تا سقف تاون‌هال فعلی فاصله دارد. آپگرید هیرو هیچ‌وقت متوقف نشود.'),
                'sort' => $hero['below_previous_th'] ? 0 : 1,
            ];
        }

        foreach ($lab['items'] as $item) {
            if ($item['maxed']) {
                continue;
            }

            $queue[] = [
                'type' => $item['group'] === 'spells' ? 'spell' : 'troop',
                'name' => $item['name'],
                'current' => $item['level'],
                'target' => $item['game_max'],
                'urgent' => false,
                'reason_fa' => $item['war_unit']
                    ? 'در ارتش جنگی متای تاون‌هال شما استفاده می‌شود — اولویت اول لَب.'
                    : 'برای تکمیل لَب؛ بعد از یونیت‌های ارتش جنگی.',
                'sort' => $item['war_unit'] ? 2 : (3 + (5 - $item['priority']) / 10),
            ];
        }

        usort($queue, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return array_map(function ($item) {
            unset($item['sort']);

            return $item;
        }, array_slice($queue, 0, 20));
    }

    /**
     * امتیاز راش ۰..۱۰۰ فقط بر پایهٔ هیروها (تنها دادهٔ با سقف دقیق per-TH).
     * ۰ = کاملاً متناسب با TH؛ بالای ~۴۰ یعنی راش جدی.
     */
    private function rushScore(array $heroes, int $th): array
    {
        $prevCapSum = 0;
        $levelSum = 0;

        foreach ($heroes as $hero) {
            $prevCap = $this->gameData->heroCapPreviousTh($hero['name'], $th);
            if ($prevCap === null) {
                continue;
            }
            $prevCapSum += $prevCap;
            $levelSum += min($hero['level'], $prevCap);
        }

        if ($prevCapSum === 0) {
            return ['score' => 0, 'basis' => 'no_previous_th_caps', 'label_fa' => 'قابل‌محاسبه نیست'];
        }

        $score = (int) round((1 - $levelSum / $prevCapSum) * 100);

        return [
            'score' => $score,
            'basis' => 'heroes_vs_previous_th_caps',
            'label_fa' => match (true) {
                $score >= 60 => 'راش شدید — قبل از بردن تاون‌هال، هیروها را برسان',
                $score >= 35 => 'راش قابل‌توجه — تمرکز روی هیروها',
                $score >= 15 => 'کمی عقب‌افتادگی — قابل جبران',
                default => 'متناسب با تاون‌هال — عالی',
            },
        ];
    }

    private function summaryFa(int $th, array $heroes, array $lab, array $rush, array $queue): string
    {
        $heroLines = array_map(
            fn ($h) => "{$h['name']}: {$h['level']}/{$h['cap']}",
            $heroes
        );

        $top = array_slice($queue, 0, 5);
        $topLines = array_map(
            fn ($q) => "- {$q['name']} ({$q['current']} → {$q['target']}): {$q['reason_fa']}",
            $top
        );

        return "تاون‌هال {$th}. وضعیت هیروها: ".implode('، ', $heroLines)
            ."\nپیشرفت لَب نسبت به سقف بازی: {$lab['overall_percent_of_game_max']}٪"
            ."\nوضعیت راش: {$rush['label_fa']} (امتیاز {$rush['score']})"
            ."\nپنج اولویت بعدی:\n".implode("\n", $topLines);
    }

    private function isSuperTroop(string $name): bool
    {
        foreach (self::SUPER_TROOP_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
