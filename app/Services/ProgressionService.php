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
        $equipment = $this->analyzeEquipment($playerData);
        $queue = $this->buildUpgradeQueue($heroes, $lab, $equipment);
        $rush = $this->rushScore($heroes, $th);
        $armies = $this->gameData->armiesForTh($th);
        $warReadiness = $this->analyzeWarReadiness($playerData, $th, $heroes, $lab, $equipment);
        $builderBase = $this->analyzeBuilderBase($playerData);
        $farming = $this->analyzeFarming($th, (int) ($playerData['trophies'] ?? 0));
        $clanActivity = $this->analyzeClanActivity($playerData);

        return [
            'ok' => true,
            'data_version' => $this->gameData->dataVersion(),
            'town_hall' => $th,
            'player_name' => $playerData['name'] ?? null,
            'player_tag' => $playerData['tag'] ?? null,
            'exp_level' => $playerData['expLevel'] ?? null,
            'trophies' => $playerData['trophies'] ?? null,
            'best_trophies' => $playerData['bestTrophies'] ?? null,
            'war_stars' => $playerData['warStars'] ?? null,
            'heroes' => $heroes,
            'lab' => $lab,
            'equipment' => $equipment,
            'war_readiness' => $warReadiness,
            'builder_base' => $builderBase,
            'farming' => $farming,
            'clan_activity' => $clanActivity,
            'rush' => $rush,
            'upgrade_queue' => $queue,
            'armies' => $armies,
            'summary_fa' => $this->summaryFa($th, $heroes, $lab, $rush, $queue, $warReadiness, $equipment, $farming),
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
     *  ۳. تجهیزات اپیک قهرمانان با سطح S.
     *  ۴. یونیت‌های ارتش جنگی TH بازیکن که هنوز maxed نیستند (به ترتیب priority).
     *  ۵. سایر یونیت‌ها به ترتیب priority.
     */
    private function buildUpgradeQueue(array $heroes, array $lab, array $equipment = []): array
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
                'urgent' => $hero['below_previous_th'] ?? false,
                'reason_fa' => ($hero['below_previous_th'] ?? false)
                    ? 'پایین‌تر از سقف تاون‌هال قبلی؛ اولویت فوری برای رفع راش.'
                    : 'رساندن به سقف تاون‌هال فعلی برای حداکثر قدرت در وار.',
                'sort' => ($hero['below_previous_th'] ?? false) ? 0 : 1,
            ];
        }

        // افزودن تجهیزات اپیک به صف اولویت
        foreach ($equipment['items'] ?? [] as $eq) {
            if ($eq['level'] < $eq['max_level'] && ($eq['rarity'] ?? '') === 'epic' && ($eq['tier'] ?? '') === 'S') {
                $queue[] = [
                    'type' => 'equipment',
                    'name' => $eq['name'],
                    'current' => $eq['level'],
                    'target' => $eq['max_level'],
                    'urgent' => false,
                    'reason_fa' => "تجهیزات اپیک سطح S ({$eq['hero']}) — ارزش ارتقای فوق‌العاده با سنگ‌های معدنی (Ores).",
                    'sort' => 1.5,
                ];
            }
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
     * تحلیل جامع تجهیزات قهرمانان (Hero Equipment)
     */
    private function analyzeEquipment(array $playerData): array
    {
        $rawEquip = $playerData['heroEquipment'] ?? [];
        $heroEquipmentMap = [];
        $totalLevel = 0;
        $maxPossibleLevel = 0;
        $epicCount = 0;
        $items = [];

        // نقشه تجهیزات مجهز شده روی هیروها
        $equippedNames = [];
        foreach ($playerData['heroes'] ?? [] as $h) {
            $hName = $h['name'] ?? '';
            foreach ($h['equipment'] ?? [] as $eq) {
                $eqName = $eq['name'] ?? '';
                if ($eqName) {
                    $equippedNames[$eqName] = $hName;
                }
            }
        }

        foreach ($rawEquip as $eq) {
            if (($eq['village'] ?? 'home') !== 'home') {
                continue;
            }

            $name = $eq['name'] ?? '';
            $lvl = (int) ($eq['level'] ?? 1);
            $maxLvl = (int) ($eq['maxLevel'] ?? 18);

            // یافتن متادیتا از دیتابیس
            $heroOwner = $equippedNames[$name] ?? null;
            $meta = null;
            if ($heroOwner) {
                $meta = $this->gameData->equipmentMeta($heroOwner, $name);
            } else {
                foreach (['Barbarian King', 'Archer Queen', 'Grand Warden', 'Royal Champion', 'Minion Prince'] as $candidate) {
                    $m = $this->gameData->equipmentMeta($candidate, $name);
                    if ($m) {
                        $meta = $m;
                        $heroOwner = $candidate;
                        break;
                    }
                }
            }

            $rarity = $meta['rarity'] ?? ($maxLvl > 18 ? 'epic' : 'common');
            $tier = $meta['tier'] ?? 'A';
            $isEquipped = isset($equippedNames[$name]);

            if ($rarity === 'epic') {
                $epicCount++;
            }

            $totalLevel += $lvl;
            $maxPossibleLevel += $maxLvl;

            $items[] = [
                'name' => $name,
                'hero' => $heroOwner ?? 'قهرمان',
                'level' => $lvl,
                'max_level' => $maxLvl,
                'rarity' => $rarity,
                'tier' => $tier,
                'is_equipped' => $isEquipped,
                'percent' => $maxLvl > 0 ? (int) round(($lvl / $maxLvl) * 100) : 100,
                'description_fa' => $meta['description_fa'] ?? '',
            ];
        }

        usort($items, function ($a, $b) {
            // اول تجهیزات فعال، سپس اولویت Epic، سپس درصد پیشرفت
            if ($a['is_equipped'] !== $b['is_equipped']) {
                return $b['is_equipped'] <=> $a['is_equipped'];
            }
            if ($a['tier'] === 'S' && $b['tier'] !== 'S') {
                return -1;
            }
            if ($b['tier'] === 'S' && $a['tier'] !== 'S') {
                return 1;
            }

            return $b['level'] <=> $a['level'];
        });

        $synergyScore = $maxPossibleLevel > 0 ? (int) round(($totalLevel / $maxPossibleLevel) * 100) : 0;

        return [
            'total_items' => count($items),
            'epic_count' => $epicCount,
            'synergy_score' => $synergyScore,
            'items' => $items,
        ];
    }

    /**
     * تحلیل آمادگی برای کلن وار و کلن وار لیگ (CWL)
     */
    private function analyzeWarReadiness(array $playerData, int $th, array $heroes, array $lab, array $equipment): array
    {
        // امتیاز هوایی
        $airTroops = ['Dragon', 'Electro Dragon', 'Lava Hound', 'Balloon', 'Dragon Rider', 'Minion', 'Baby Dragon'];
        $airSpells = ['Rage Spell', 'Freeze Spell', 'Haste Spell', 'Lightning Spell', 'Clone Spell'];
        
        $airScoreSum = 0;
        $airCount = 0;
        foreach ($lab['items'] as $item) {
            if (in_array($item['name'], array_merge($airTroops, $airSpells), true)) {
                $airScoreSum += $item['percent_of_game_max'];
                $airCount++;
            }
        }
        $airMastery = $airCount > 0 ? (int) round($airScoreSum / $airCount) : 50;

        // امتیاز زمینی
        $groundTroops = ['Root Rider', 'Yeti', 'Bowler', 'Witch', 'Hog Rider', 'Miner', 'Golem', 'P.E.K.K.A', 'Valkyrie'];
        $groundSpells = ['Heal Spell', 'Jump Spell', 'Poison Spell', 'Overgrowth Spell', 'Earthquake Spell'];

        $groundScoreSum = 0;
        $groundCount = 0;
        foreach ($lab['items'] as $item) {
            if (in_array($item['name'], array_merge($groundTroops, $groundSpells), true)) {
                $groundScoreSum += $item['percent_of_game_max'];
                $groundCount++;
            }
        }
        $groundMastery = $groundCount > 0 ? (int) round($groundScoreSum / $groundCount) : 50;

        // میانگین درصد هیروها
        $heroPercentSum = 0;
        foreach ($heroes as $h) {
            $heroPercentSum += ($h['percent'] ?? 0);
        }
        $heroAvg = count($heroes) > 0 ? round($heroPercentSum / count($heroes)) : 50;

        // امتیاز کل قدرت هجومی وار (Weighted Offense Power)
        $offenseScore = (int) round(($heroAvg * 0.45) + (max($airMastery, $groundMastery) * 0.35) + (($equipment['synergy_score'] ?? 50) * 0.20));

        $tier = match (true) {
            $offenseScore >= 90 => 'S+',
            $offenseScore >= 80 => 'S',
            $offenseScore >= 70 => 'A',
            $offenseScore >= 55 => 'B',
            $offenseScore >= 40 => 'C',
            default => 'D',
        };

        $cwlLeague = match (true) {
            $th >= 17 => 'Champion I - Master I',
            $th >= 15 => 'Master I - Master III',
            $th >= 13 => 'Master III - Crystal II',
            $th >= 11 => 'Crystal II - Gold I',
            default => 'Gold II - Silver I',
        };

        $preferredStyle = $airMastery >= $groundMastery + 8 ? 'حمله هوایی (Air Specialist)' : ($groundMastery >= $airMastery + 8 ? 'حمله زمینی (Ground Specialist)' : 'متعادل (Hybrid / Versatile)');

        return [
            'offense_score' => $offenseScore,
            'tier' => $tier,
            'air_mastery' => $airMastery,
            'ground_mastery' => $groundMastery,
            'preferred_style' => $preferredStyle,
            'recommended_cwl_league' => $cwlLeague,
        ];
    }

    /**
     * تحلیل پیشرفت بیلدر بیس و بازگشایی کارگر ششم (B.O.B)
     */
    private function analyzeBuilderBase(array $playerData): array
    {
        $bhLevel = (int) ($playerData['builderHallLevel'] ?? 0);
        $bhTrophies = (int) ($playerData['builderBaseTrophies'] ?? 0);
        $bestBhTrophies = (int) ($playerData['bestBuilderBaseTrophies'] ?? 0);

        // بررسی وظایف ۴ گانه B.O.B
        $tasksCompleted = 0;

        // وظیفه ۱: Gear up ۳ ساختمان در دهکده اصلی
        $gearUpDone = false;
        foreach ($playerData['achievements'] ?? [] as $ach) {
            if (($ach['name'] ?? '') === 'High Gear' && ($ach['value'] ?? 0) >= 3) {
                $gearUpDone = true;
                $tasksCompleted++;
                break;
            }
        }

        // وظیفه ۲: ارتقای یک نیروی بیلدربیس به لول ۱۸
        $troopLvl18Done = false;
        foreach ($playerData['troops'] ?? [] as $t) {
            if (($t['village'] ?? '') === 'builderBase' && ($t['level'] ?? 0) >= 18) {
                $troopLvl18Done = true;
                $tasksCompleted++;
                break;
            }
        }

        // وظیفه ۳: ارتقای یک دفاعی به لول ۹
        $defenseLvl9Done = $bhLevel >= 9;
        if ($defenseLvl9Done) {
            $tasksCompleted++;
        }

        // وظیفه ۴: مجموع لول هیروهای بیلدربیس به ۴۵ (Battle Machine + Battle Copter)
        $bbHeroSum = 0;
        foreach ($playerData['heroes'] ?? [] as $h) {
            if (($h['village'] ?? '') === 'builderBase') {
                $bbHeroSum += (int) ($h['level'] ?? 0);
            }
        }
        $hero45Done = $bbHeroSum >= 45;
        if ($hero45Done) {
            $tasksCompleted++;
        }

        return [
            'builder_hall_level' => $bhLevel,
            'trophies' => $bhTrophies,
            'best_trophies' => $bestBhTrophies,
            'bob_progress_percent' => (int) round(($tasksCompleted / 4) * 100),
            'tasks' => [
                ['title' => 'Gear Up سه ساختمان در دهکده اصلی', 'done' => $gearUpDone],
                ['title' => 'رساندن حداقل یک نیروی بیلدربیس به لِوِل ۱۸', 'done' => $troopLvl18Done],
                ['title' => 'ارتقای حداقل یک ساختمان دفاعی به لِوِل ۹', 'done' => $defenseLvl9Done],
                ['title' => 'مجموع لِوِل هیروهای بیلدربیس حداقل ۴۵ (فعلی: '.$bbHeroSum.'/۴۵)', 'done' => $hero45Done],
            ],
        ];
    }

    /**
     * مشاور هوشمند فارمینگ و تخمین سنگ‌های معدنی (Ores)
     */
    private function analyzeFarming(int $th, int $trophies): array
    {
        $minOptimal = match (true) {
            $th >= 16 => 3200,
            $th >= 14 => 2600,
            $th >= 12 => 2200,
            $th >= 10 => 1800,
            default => 1300,
        };

        $maxOptimal = match (true) {
            $th >= 16 => 4200,
            $th >= 14 => 3400,
            $th >= 12 => 2800,
            $th >= 10 => 2400,
            default => 1800,
        };

        $status = match (true) {
            $trophies < $minOptimal => 'too_low',
            $trophies > $maxOptimal => 'too_high',
            default => 'in_sweetspot',
        };

        $statusFa = match ($status) {
            'in_sweetspot' => 'محدوده طلایی فارم و کسب لوت با سرعت بالا ⚡',
            'too_low' => 'کاپ پایین؛ لوت وجود دارد اما پاداش ستاره روزانه (Ores) کمتر است.',
            'too_high' => 'کاپ بالا؛ مناسب پوش کاپ و پاداش سنگ معدن بالا، ولی زمان سرچ بیشتر است.',
        };

        // تخمین سنگ‌های ستاره روزانه
        $dailyShiny = match (true) {
            $trophies >= 5000 => 1000,
            $trophies >= 4100 => 900,
            $trophies >= 3200 => 800,
            $trophies >= 2600 => 675,
            $trophies >= 2000 => 550,
            default => 400,
        };

        $dailyGlowy = match (true) {
            $trophies >= 5000 => 54,
            $trophies >= 4100 => 50,
            $trophies >= 3200 => 44,
            $trophies >= 2600 => 38,
            $trophies >= 2000 => 30,
            default => 20,
        };

        $dailyStarry = $trophies >= 4100 ? 6 : ($trophies >= 3200 ? 5 : ($trophies >= 2600 ? 4 : 0));

        return [
            'optimal_min' => $minOptimal,
            'optimal_max' => $maxOptimal,
            'status' => $status,
            'status_fa' => $statusFa,
            'daily_ores' => [
                'shiny' => $dailyShiny,
                'glowy' => $dailyGlowy,
                'starry' => $dailyStarry,
            ],
        ];
    }

    /**
     * تحلیل فعالیت کلنی، مشارکت در پایتخت (Clan Capital) و دونیت
     */
    private function analyzeClanActivity(array $playerData): array
    {
        $donations = (int) ($playerData['donations'] ?? 0);
        $received = (int) ($playerData['donationsReceived'] ?? 0);
        $capitalContributions = (int) ($playerData['clanCapitalContributions'] ?? 0);
        $warStars = (int) ($playerData['warStars'] ?? 0);

        $ratio = $received > 0 ? round($donations / $received, 2) : ($donations > 0 ? 99 : 1.0);

        $warRating = match (true) {
            $warStars >= 2000 => 'جنگجوی افسانه‌ای (Legendary Veteran) ⚔️',
            $warStars >= 1000 => 'استاد جنگ‌های قبیله‌ای (War Master) 🔥',
            $warStars >= 500 => 'جنگجوی باتجربه (Experienced Fighter) 🛡️',
            default => 'جنگجوی تازه‌نفس (Rising Warrior)',
        };

        return [
            'donations' => $donations,
            'donations_received' => $received,
            'donation_ratio' => $ratio,
            'clan_capital_contributed' => $capitalContributions,
            'war_stars' => $warStars,
            'war_rating_fa' => $warRating,
            'is_generous' => $ratio >= 1.0 || $donations >= 1000,
        ];
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

    private function summaryFa(
        int $th,
        array $heroes,
        array $lab,
        array $rush,
        array $queue,
        array $warReadiness = [],
        array $equipment = [],
        array $farming = []
    ): string {
        $heroLines = array_map(
            fn ($h) => "{$h['name']}: {$h['level']}/{$h['cap']}",
            $heroes
        );

        $top = array_slice($queue, 0, 5);
        $topLines = array_map(
            fn ($q) => "- {$q['name']} ({$q['current']} → {$q['target']}): {$q['reason_fa']}",
            $top
        );

        $warInfo = ! empty($warReadiness)
            ? "\nرتبه آمادگی وار: سطح {$warReadiness['tier']} (امتیاز قدرت: {$warReadiness['offense_score']}٪ - {$warReadiness['preferred_style']})"
            : '';

        $equipInfo = ! empty($equipment)
            ? "\nسینرژی تجهیزات قهرمانان: {$equipment['synergy_score']}٪ با {$equipment['epic_count']} قطعه اپیک"
            : '';

        $farmInfo = ! empty($farming)
            ? "\nوضعیت فارمینگ: {$farming['status_fa']}"
            : '';

        return "تاون‌هال {$th}. وضعیت هیروها: ".implode('، ', $heroLines)
            ."\nپیشرفت لَب نسبت به سقف بازی: {$lab['overall_percent_of_game_max']}٪"
            ."\nوضعیت راش: {$rush['label_fa']} (امتیاز {$rush['score']})"
            .$warInfo
            .$equipInfo
            .$farmInfo
            ."\nپنج اولویت بعدی ارتقا:\n".implode("\n", $topLines);
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
