<?php

namespace App\Services\BaseClone;

/**
 * اعتبارسنجی سخت‌گیرانهٔ ویرایش دستی چیدمان (ویرایشگر مالک).
 *
 * برخلاف LayoutGridMapper هیچ ساختمانی جابه‌جا نمی‌شود؛ هر خطا (نوع ناشناخته،
 * خروج از نقشه، هم‌پوشانی، دیوار روی ساختمان، سقف تعداد) به‌صورت خطای per-item
 * برگردانده می‌شود تا کاربر خودش اصلاح کند. ابعاد/برچسب/رنگ/آیکون فقط از کاتالوگ
 * خوانده می‌شود و مقادیر ارسالی کلاینت نادیده گرفته می‌شوند.
 */
class LayoutEditValidator
{
    public const MAX_BUILDINGS = 300;

    public const MAX_WALLS = 400;

    /**
     * @param  array  $existing  چیدمان ذخیره‌شدهٔ فعلی (خروجی LayoutGridMapper یا ویرایش قبلی)
     * @param  array  $edit  بدنهٔ درخواست: buildings[{id,type,x,y,level?,placed?,user_fixed?}], walls[[x,y]]
     * @return array{ok: bool, layout?: array, errors?: array<string, string>}
     */
    public function validate(array $existing, array $edit, BuildingCatalog $catalog, int $gridSize): array
    {
        $errors = [];

        $previous = [];
        foreach ($existing['buildings'] ?? [] as $b) {
            if (isset($b['id'])) {
                $previous[(int) $b['id']] = $b;
            }
        }

        $incoming = is_array($edit['buildings'] ?? null) ? array_values($edit['buildings']) : [];
        $incomingWalls = is_array($edit['walls'] ?? null) ? array_values($edit['walls']) : [];

        if (count($incoming) > self::MAX_BUILDINGS) {
            $errors['buildings'] = 'حداکثر '.self::MAX_BUILDINGS.' ساختمان مجاز است.';
        }
        if (count($incomingWalls) > self::MAX_WALLS) {
            $errors['walls'] = 'حداکثر '.self::MAX_WALLS.' قطعه دیوار مجاز است.';
        }

        // occupancy[y][x] = شناسهٔ ساختمانی که خانه را اشغال کرده (۰ = خالی)
        $occupancy = array_fill(0, $gridSize, array_fill(0, $gridSize, 0));
        $seenIds = [];
        $buildings = [];

        foreach ($incoming as $i => $b) {
            if (! is_array($b)) {
                $errors["buildings.$i"] = 'ساختار ساختمان نامعتبر است.';

                continue;
            }

            $id = isset($b['id']) && is_numeric($b['id']) ? (int) $b['id'] : 0;
            if ($id < 1) {
                $errors["buildings.$i.id"] = 'شناسهٔ ساختمان نامعتبر است.';

                continue;
            }
            if (isset($seenIds[$id])) {
                $errors["buildings.$i.id"] = "شناسهٔ #{$id} تکراری است.";

                continue;
            }
            $seenIds[$id] = true;

            $type = $catalog->normalizeType(is_string($b['type'] ?? null) ? $b['type'] : null);
            if ($type === null || $type === BuildingCatalog::WALL) {
                $errors["buildings.$i.type"] = 'نوع ساختمان نامعتبر است.';

                continue;
            }

            $meta = $catalog->get($type);
            $size = (int) $meta['size'];

            if (! $this->isInt($b['x'] ?? null) || ! $this->isInt($b['y'] ?? null)) {
                $errors["buildings.$i"] = 'مختصات باید عدد صحیح باشد.';

                continue;
            }
            $x = (int) $b['x'];
            $y = (int) $b['y'];

            if ($x < 0 || $y < 0 || $x > $gridSize - $size || $y > $gridSize - $size) {
                $errors["buildings.$i"] = 'ساختمان خارج از نقشه است.';

                continue;
            }

            $placed = array_key_exists('placed', $b) ? (bool) $b['placed'] : true;

            if ($placed) {
                $other = $this->firstOccupant($occupancy, $x, $y, $size);
                if ($other !== null) {
                    $errors["buildings.$i"] = "هم‌پوشانی با ساختمان #{$other}";

                    continue;
                }
                $this->occupy($occupancy, $x, $y, $size, $id);
            }

            $buildings[] = $this->buildEntry($id, $type, $meta, $size, $x, $y, $placed, $b, $previous[$id] ?? null);
        }

        $walls = [];
        foreach ($incomingWalls as $j => $cell) {
            if (! is_array($cell) || count($cell) !== 2) {
                $errors["walls.$j"] = 'ساختار دیوار نامعتبر است.';

                continue;
            }
            [$wx, $wy] = array_values($cell);
            if (! $this->isInt($wx) || ! $this->isInt($wy)) {
                $errors["walls.$j"] = 'مختصات دیوار باید عدد صحیح باشد.';

                continue;
            }
            $wx = (int) $wx;
            $wy = (int) $wy;
            if ($wx < 0 || $wy < 0 || $wx >= $gridSize || $wy >= $gridSize) {
                $errors["walls.$j"] = 'دیوار خارج از نقشه است.';

                continue;
            }
            if ($occupancy[$wy][$wx] !== 0) {
                $errors["walls.$j"] = 'دیوار روی ساختمان #'.$occupancy[$wy][$wx].' قرار دارد.';

                continue;
            }
            $walls[$wx.','.$wy] = [$wx, $wy];
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        usort($buildings, fn ($a, $b) => $a['id'] <=> $b['id']);
        $walls = array_values($walls);

        $layout = $existing;
        $layout['type'] = 'layout';
        $layout['grid_size'] = $gridSize;
        $layout['buildings'] = $buildings;
        $layout['walls'] = $walls;
        $layout['stats'] = $this->buildStats($buildings, count($walls), $existing['stats'] ?? []);
        $layout['version'] = (int) ($existing['version'] ?? 1) + 1;
        $layout['source'] = 'user';
        $layout['edited_at'] = now()->toIso8601String();

        return ['ok' => true, 'layout' => $layout];
    }

    /**
     * ساخت رکورد ساختمان با همان شکل خروجی LayoutGridMapper؛ متادیتا فقط از کاتالوگ.
     */
    protected function buildEntry(int $id, string $type, array $meta, int $size, int $x, int $y, bool $placed, array $input, ?array $prev): array
    {
        $userFixed = (bool) ($input['user_fixed'] ?? $input['verified'] ?? false);
        $isNew = $prev === null;
        $moved = ! $isNew && (
            (int) ($prev['x'] ?? -1) !== $x
            || (int) ($prev['y'] ?? -1) !== $y
            || ($prev['type'] ?? null) !== $type
        );

        $entry = [
            'id' => $id,
            'type' => $type,
            'label' => $meta['label'],
            'category' => $meta['category'],
            'color' => $meta['color'],
            'icon' => $meta['icon'],
            'size' => $size,
            'x' => $x,
            'y' => $y,
            'placed' => $placed,
        ];

        if (isset($meta['sprite'])) {
            $entry['sprite'] = $meta['sprite'];
        }

        if (array_key_exists('level', $input)) {
            if ($input['level'] !== null && $this->isInt($input['level'])) {
                $entry['level'] = (int) $input['level'];
            }
        } elseif (isset($prev['level'])) {
            $entry['level'] = (int) $prev['level'];
        }

        if (array_key_exists('shift', $prev ?? [])) {
            $entry['shift'] = $moved ? 0 : (int) $prev['shift'];
        }

        // پرچم‌های مدل (مثلاً cap_trimmed) فقط تا وقتی معتبرند که ساختمان دست نخورده باشد؛
        // با این کار LayoutStats حذف‌شده‌های سقف را همچنان «trimmed» می‌شمارد نه «جا نشده».
        $placedUnchanged = ! $isNew && $placed === (bool) ($prev['placed'] ?? true);
        if ($placedUnchanged && ! $moved && ! $userFixed && is_array($prev['flags'] ?? null) && $prev['flags'] !== []) {
            $entry['flags'] = array_values($prev['flags']);
        }

        // نامطمئن (همان قرارداد LayoutGridMapper: جانشده همیشه نامطمئن است)؛ از رکورد قبلی می‌آید و
        // تأیید کاربر یا جابه‌جایی دستی آن را پاک می‌کند.
        $prevUncertain = $isNew ? false : (bool) ($prev['uncertain'] ?? ! ($prev['placed'] ?? true));
        $entry['uncertain'] = ! $placed || ($prevUncertain && ! $userFixed && ! $moved);

        $wasFixed = (bool) ($prev['user_fixed'] ?? $prev['verified'] ?? false);
        if ($userFixed || $wasFixed) {
            $entry['user_fixed'] = true;
        }

        $entry['source'] = ($isNew || $moved || $userFixed) ? 'user' : ($prev['source'] ?? 'ai');

        return $entry;
    }

    /**
     * آمار چیدمان با همان قرارداد LayoutStats (مشترک با LayoutGridMapper) به‌علاوهٔ شمارش تأییدشده‌ها؛
     * expected_total و walls_dropped از آمار قبلی (خروجی مدل) حفظ می‌شوند.
     *
     * @param  array<string, mixed>  $previousStats
     */
    public function buildStats(array $buildings, int $wallCount, array $previousStats = []): array
    {
        $fixed = 0;
        foreach ($buildings as $b) {
            if (! empty($b['user_fixed'])) {
                $fixed++;
            }
        }

        return LayoutStats::build($buildings, $wallCount, [
            'user_fixed_count' => $fixed,
            'expected_total' => $previousStats['expected_total'] ?? null,
            'walls_dropped' => (int) ($previousStats['walls_dropped'] ?? 0),
        ]);
    }

    protected function firstOccupant(array $occupancy, int $x0, int $y0, int $size): ?int
    {
        for ($y = $y0; $y < $y0 + $size; $y++) {
            for ($x = $x0; $x < $x0 + $size; $x++) {
                if ($occupancy[$y][$x] !== 0) {
                    return $occupancy[$y][$x];
                }
            }
        }

        return null;
    }

    protected function occupy(array &$occupancy, int $x0, int $y0, int $size, int $id): void
    {
        for ($y = $y0; $y < $y0 + $size; $y++) {
            for ($x = $x0; $x < $x0 + $size; $x++) {
                $occupancy[$y][$x] = $id;
            }
        }
    }

    protected function isInt(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
    }
}
