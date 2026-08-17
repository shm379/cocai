<?php

namespace App\Services;

class StrategyLabAnalyzer
{
    /**
     * Run a deterministic analysis on a base layout.
     *
     * @param  array<int, array{id: int, type: string, x: float, y: float}>  $buildings
     * @return array<string, mixed>
     */
    public function analyze(array $buildings): array
    {
        if (empty($buildings)) {
            return [
                'ok' => false,
                'message' => 'هیچ ساختمانی روی نقشه علامت‌گذاری نشده است.',
            ];
        }

        $byType = $this->groupByType($buildings);

        $weakPoints = [];
        $entrySuggestions = [];

        // 1) Air defense gaps
        $airDefenses = array_merge(
            $byType['air_defense'] ?? [],
            $byType['air_defense_old'] ?? []
        );
        if (count($airDefenses) < 3) {
            $weakPoints[] = [
                'severity' => 'high',
                'title' => 'شکاف دفاع هوایی',
                'description' => 'تعداد دفاع‌های هوایی کم است ('.count($airDefenses).'). حملات هوایی (Lavaloon/Dragon) می‌توانند از این ضعف استفاده کنند.',
            ];
        }

        // 2) Exposed Town Hall
        $townHall = $this->findTownHall($byType);
        if ($townHall && $this->isNearEdge($townHall, $buildings, 0.35)) {
            $weakPoints[] = [
                'severity' => 'high',
                'title' => 'تانک هال در معرض',
                'description' => 'تاون هال نزدیک لبه نقشه قرار دارد و احتمالاً با کمترین نیرو قابل دستیابی است.',
            ];
        }

        // 3) Clan castle placement
        $clanCastle = $this->findByTypes($byType, ['clan_castle']);
        if ($clanCastle && $this->isNearEdge($clanCastle, $buildings, 0.30)) {
            $weakPoints[] = [
                'severity' => 'medium',
                'title' => 'کلن کسل غیرمرکزی',
                'description' => 'کلن کسل نزدیک لبه است؛ نیروهای حریف می‌توانند به‌راحتی آن را فعال و از بین ببرند.',
            ];
        }

        // 4) Splash coverage gaps
        $splash = array_merge(
            $byType['wizard_tower'] ?? [],
            $byType['bomb_tower'] ?? [],
            $byType['mortar'] ?? []
        );
        if (count($splash) < 2) {
            $weakPoints[] = [
                'severity' => 'medium',
                'title' => 'پوشش اسپلش ضعیف',
                'description' => 'تعداد برج‌های اسپلش کم است؛ حملات گروهی (Hog, Miner, Witch) آسیب زیادی وارد می‌کنند.',
            ];
        }

        // 5) Single-target inferno exposed
        $infernos = array_merge(
            $byType['inferno_tower_single'] ?? [],
            $byType['inferno_tower'] ?? []
        );
        foreach ($infernos as $inferno) {
            if ($this->isNearEdge($inferno, $buildings, 0.30)) {
                $weakPoints[] = [
                    'severity' => 'medium',
                    'title' => 'اینفرنو تک‌هدف در معرض',
                    'description' => 'اینفرنو تک‌هدف نزدیک لبه قرار دارد و با Queen Charge یا اسپل رعد آسان‌تر از بین می‌رود.',
                ];
                break;
            }
        }

        // 6) Eagle artillery reachable from edge
        $eagle = $this->findByTypes($byType, ['eagle_artillery']);
        if ($eagle && $this->isNearEdge($eagle, $buildings, 0.40)) {
            $weakPoints[] = [
                'severity' => 'high',
                'title' => 'عقاب در دسترس',
                'description' => 'عقاب نسبتاً نزدیک لبه است؛ می‌توان با تروپ‌های تانک‌کش آن را زودتر فعال کرد.',
            ];
        }

        // 7) Entry suggestions
        $entrySuggestions = $this->suggestEntries($buildings, $byType, $townHall);

        if (empty($weakPoints)) {
            $weakPoints[] = [
                'severity' => 'low',
                'title' => 'بیس متعادل',
                'description' => 'از نظر الگوریتم شاخص ضعف بارزی مشاهده نشد؛ همچنان توصیه می‌شود با ترکیب‌های مختلف تست کنید.',
            ];
        }

        return [
            'ok' => true,
            'summary' => 'تحلیل بر اساس موقعیت ساختمان‌ها انجام شد.',
            'weak_points' => $weakPoints,
            'entry_suggestions' => $entrySuggestions,
            'building_count' => count($buildings),
        ];
    }

    private function groupByType(array $buildings): array
    {
        $grouped = [];
        foreach ($buildings as $b) {
            $type = $b['type'] ?? 'unknown';
            $grouped[$type][] = $b;
        }

        return $grouped;
    }

    private function findTownHall(array $byType): ?array
    {
        return $this->findByTypes($byType, ['town_hall', 'townhall', 'town_hall_weaponized']);
    }

    private function findByTypes(array $byType, array $types): ?array
    {
        foreach ($types as $type) {
            if (! empty($byType[$type])) {
                return $byType[$type][0];
            }
        }

        return null;
    }

    /**
     * Determine if a building is close to the edge of the layout.
     * We approximate the layout bounding box from all buildings and check
     * distance to nearest edge relative to the larger dimension.
     */
    private function isNearEdge(array $building, array $allBuildings, float $thresholdRatio): bool
    {
        $bounds = $this->bounds($allBuildings);
        if ($bounds === null) {
            return false;
        }

        [$minX, $minY, $maxX, $maxY] = $bounds;
        $width = max($maxX - $minX, 1);
        $height = max($maxY - $minY, 1);
        $maxDim = max($width, $height);

        $x = $building['x'];
        $y = $building['y'];

        $distLeft = $x - $minX;
        $distRight = $maxX - $x;
        $distTop = $y - $minY;
        $distBottom = $maxY - $y;

        return min($distLeft, $distRight, $distTop, $distBottom) / $maxDim <= $thresholdRatio;
    }

    /**
     * Suggest the best sides to enter based on defensive weight distribution.
     *
     * @return array<int, array{side: string, score: int, reason: string}>
     */
    private function suggestEntries(array $buildings, array $byType, ?array $townHall): array
    {
        $bounds = $this->bounds($buildings);
        if ($bounds === null) {
            return [];
        }

        [$minX, $minY, $maxX, $maxY] = $bounds;
        $midX = ($minX + $maxX) / 2;
        $midY = ($minY + $maxY) / 2;

        $sides = [
            'top' => 0,
            'bottom' => 0,
            'left' => 0,
            'right' => 0,
        ];

        $weights = $this->buildingWeights();

        foreach ($buildings as $b) {
            $weight = $weights[$b['type']] ?? 1;
            $x = $b['x'];
            $y = $b['y'];

            // Assign weight to the side the building is closer to.
            if ($y < $midY) {
                $sides['top'] += $weight;
            } else {
                $sides['bottom'] += $weight;
            }

            if ($x < $midX) {
                $sides['left'] += $weight;
            } else {
                $sides['right'] += $weight;
            }
        }

        // Bonus: if Town Hall is closer to a side, reduce that side's score (avoid starting there).
        if ($townHall) {
            if ($townHall['y'] < $midY) {
                $sides['top'] -= 3;
            } else {
                $sides['bottom'] -= 3;
            }
            if ($townHall['x'] < $midX) {
                $sides['left'] -= 3;
            } else {
                $sides['right'] -= 3;
            }
        }

        // Air-defense weight: sides with less air defense are better for air attacks.
        $airDefenseBySide = $this->airDefenseBySide($byType, $midX, $midY);
        foreach ($airDefenseBySide as $side => $count) {
            $sides[$side] -= $count * 2;
        }

        // Sort ascending: lower score = weaker side = better entry.
        asort($sides);

        $persian = [
            'top' => 'بالا',
            'bottom' => 'پایین',
            'left' => 'چپ',
            'right' => 'راست',
        ];

        $reasons = [
            'top' => 'تراکم دفاعی کمتر در نیمکره بالایی',
            'bottom' => 'تراکم دفاعی کمتر در نیمکره پایینی',
            'left' => 'تراکم دفاعی کمتر در نیمکره چپ',
            'right' => 'تراکم دفاعی کمتر در نیمکره راست',
        ];

        $suggestions = [];
        foreach ($sides as $side => $score) {
            $suggestions[] = [
                'side' => $persian[$side],
                'score' => (int) round($score),
                'reason' => $reasons[$side],
            ];
        }

        return array_slice($suggestions, 0, 2);
    }

    private function airDefenseBySide(array $byType, float $midX, float $midY): array
    {
        $sides = ['top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0];
        $airDefs = array_merge(
            $byType['air_defense'] ?? [],
            $byType['air_defense_old'] ?? []
        );

        foreach ($airDefs as $b) {
            if ($b['y'] < $midY) {
                $sides['top']++;
            } else {
                $sides['bottom']++;
            }
            if ($b['x'] < $midX) {
                $sides['left']++;
            } else {
                $sides['right']++;
            }
        }

        return $sides;
    }

    /**
     * @return array<int, int>|null
     */
    private function bounds(array $buildings): ?array
    {
        if (empty($buildings)) {
            return null;
        }

        $xs = array_column($buildings, 'x');
        $ys = array_column($buildings, 'y');

        return [
            (int) min($xs),
            (int) min($ys),
            (int) max($xs),
            (int) max($ys),
        ];
    }

    /**
     * Defensive weight per building type. Higher = more dangerous for attackers.
     *
     * @return array<string, int>
     */
    private function buildingWeights(): array
    {
        return [
            'inferno_tower' => 8,
            'inferno_tower_single' => 8,
            'inferno_tower_multi' => 7,
            'eagle_artillery' => 9,
            'scattershot' => 8,
            'monolith' => 8,
            'air_defense' => 5,
            'air_defense_old' => 5,
            'wizard_tower' => 4,
            'bomb_tower' => 4,
            'mortar' => 3,
            'x_bow' => 5,
            'x_bow_air' => 5,
            'archer_tower' => 3,
            'cannon' => 2,
            'hidden_tesla' => 3,
            'tesla' => 3,
            'builder_hut' => 1,
            'clan_castle' => 4,
            'town_hall' => 10,
            'townhall' => 10,
            'town_hall_weaponized' => 10,
            'barbarian_king' => 4,
            'archer_queen' => 5,
            'grand_warden' => 4,
            'royal_champion' => 4,
            'air_sweeper' => 3,
        ];
    }
}
