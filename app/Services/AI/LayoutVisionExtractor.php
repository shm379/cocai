<?php

namespace App\Services\AI;

use App\Services\BaseClone\BuildingCatalog;
use Illuminate\Support\Facades\Log;

/**
 * استخراج چیدمان کامل بیس از روی تصویر با مدل Vision.
 *
 * برخلاف BaseVisionAnalyzer که فقط ساختمان‌های کلیدی را برای تحلیل نقاط ضعف
 * می‌گیرد، این کلاس همهٔ ساختمان‌ها (با جعبهٔ اسپرایت)، قطعات دیوار، جعبهٔ لوزی
 * قابل‌مشاهده و گوشه‌های نقشه را می‌خواهد تا LayoutGridMapper بتواند چیدمان ۴۴×۴۴ را بازسازی کند.
 *
 * قرارداد جدید (نسخهٔ ۲، فشرده، همهٔ اعداد صحیح ۰..۱۰۰۰ از عرض/ارتفاع تصویر، x چپ→راست و y بالا→پایین):
 *   {"th":15,"p":"iso","d":[x0,y0,x1,y1]|null,"c":[8 عدد]|null,"b":[[type,x0,y0,x1,y1(,level)]...],"w":[[x1,y1,x2,y2]...]}
 * قرارداد قدیمی (درصد، ردیف‌های [type,x,y(,level)] و اسکیمای بلند با اشیاء) همچنان پذیرفته می‌شود.
 * خروجی همیشه به درصد تصویر نرمال می‌شود (x,y مرکز، box=[x0,y0,x1,y1]، diamond_box، grid_corners، walls).
 */
class LayoutVisionExtractor extends BaseVisionAnalyzer
{
    public const SCHEMA_VERSION = 2;

    public function __construct(protected BuildingCatalog $catalog)
    {
        parent::__construct();
    }

    /**
     * نسخه‌ای از استخراج‌کننده با کاتالوگ دیگر (مثلاً بیلدر بیس).
     */
    public function withCatalog(BuildingCatalog $catalog): static
    {
        $clone = clone $this;
        $clone->catalog = $catalog;

        return $clone;
    }

    /**
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array{ok: bool, data?: array, message?: string, raw_content?: string, model?: string}
     */
    public function extractLayout($image): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'تنظیمات AI Vision انجام نشده است.'];
        }

        $base64 = $this->imageToBase64($image);
        if ($base64 === null) {
            return ['ok' => false, 'message' => 'خطا در خواندن تصویر.'];
        }

        $response = $this->callVisionModel($base64);
        if ($response === null || empty($response['content'])) {
            return [
                'ok' => false,
                'message' => $this->lastErrorMessage(),
                'reason' => $this->lastError()['reason'] ?? 'empty',
            ];
        }

        $data = $this->parseLayoutJson($response['content'], $this->lastImageSize());
        if ($data === null) {
            return [
                'ok' => false,
                'message' => 'خروجی مدل Vision قابل تفسیر نبود. لطفاً تصویر واضح‌تری از کل بیس آپلود کنید.',
                'raw_content' => $response['content'],
            ];
        }

        if ($data['buildings'] === []) {
            return [
                'ok' => false,
                'message' => 'هیچ ساختمانی در تصویر تشخیص داده نشد. مطمئن شوید تصویر، نمای کامل یک بیس کلش است.',
                'raw_content' => $response['content'],
            ];
        }

        return [
            'ok' => true,
            'data' => $data,
            'raw_content' => $response['content'],
            'model' => $response['model'] ?? $this->model,
        ];
    }

    /**
     * تفسیر تحمل‌پذیر JSON مدل (حذف code fence، برش تا آخرین آکولاد، بازیابی پاسخ قطع‌شده)
     * و نرمال‌سازی به درصد تصویر.
     *
     * @param  array{0:int,1:int}|null  $imageSize  ابعاد تصویر اصلی برای بررسی جابه‌جایی محورها
     */
    public function parseLayoutJson(string $content, ?array $imageSize = null): ?array
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $content) ?? $content;
            $content = trim($content);
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false) {
            Log::warning('LayoutVisionExtractor: no JSON object in response.', ['content' => mb_substr($content, 0, 500)]);

            return null;
        }

        // پاسخ قطع‌شده ممکن است اصلاً «}» نداشته باشد؛ در آن صورت مستقیم به بازیابی می‌رویم.
        $data = ($end !== false && $end > $start)
            ? json_decode(substr($content, $start, $end - $start + 1), true)
            : null;
        if (! is_array($data)) {
            // پاسخ ناقص (مثلاً قطع‌شده در سقف توکن) → بازیابی هر چه قابل خواندن است.
            $data = $this->salvage($content);
            Log::warning('LayoutVisionExtractor: invalid JSON, salvaged.', [
                'length' => mb_strlen($content),
                'salvaged_buildings' => count($data['buildings'] ?? []),
                'head' => mb_substr($content, 0, 200),
                'tail' => mb_substr($content, -200),
            ]);

            if ($data === null) {
                return null;
            }
        }

        $data = $this->expandCompact($data);
        $permille = ($data['_units'] ?? 'pct') === 'permille';
        $scale = $permille ? 0.1 : 1.0;

        $buildings = [];
        foreach ((array) ($data['buildings'] ?? []) as $b) {
            if (! is_array($b)) {
                continue;
            }
            $type = $this->catalog->normalizeType(is_string($b['type'] ?? null) ? $b['type'] : null);
            if ($type === null || $type === BuildingCatalog::WALL) {
                continue;
            }

            $box = null;
            if (isset($b['box']) && is_array($b['box']) && count($b['box']) >= 4) {
                $raw = array_values($b['box']);
                if (is_numeric($raw[0]) && is_numeric($raw[1]) && is_numeric($raw[2]) && is_numeric($raw[3])) {
                    $box = [(float) $raw[0], (float) $raw[1], (float) $raw[2], (float) $raw[3]];
                    // جعبهٔ وارونه را مرتب می‌کنیم؛ جعبهٔ بدون مساحت دور ریخته می‌شود.
                    $box = [min($box[0], $box[2]), min($box[1], $box[3]), max($box[0], $box[2]), max($box[1], $box[3])];
                    if ($box[2] - $box[0] <= 0 || $box[3] - $box[1] <= 0) {
                        $box = null;
                    }
                }
            }

            if ($box !== null && (! isset($b['x'], $b['y']) || ! is_numeric($b['x']) || ! is_numeric($b['y']))) {
                $b['x'] = ($box[0] + $box[2]) / 2;
                $b['y'] = ($box[1] + $box[3]) / 2;
            }
            if (! isset($b['x'], $b['y']) || ! is_numeric($b['x']) || ! is_numeric($b['y'])) {
                continue;
            }

            $entry = [
                'type' => $type,
                'x' => round((float) $b['x'] * $scale, 2),
                'y' => round((float) $b['y'] * $scale, 2),
            ];
            if ($box !== null) {
                $entry['box'] = array_map(fn ($v) => round($v * $scale, 2), $box);
            }
            if (isset($b['level']) && is_numeric($b['level'])) {
                $entry['level'] = (int) $b['level'];
            }
            $buildings[] = $entry;
        }

        $walls = [];
        foreach ((array) ($data['walls'] ?? []) as $w) {
            if (! is_array($w)) {
                continue;
            }
            $ok = true;
            foreach (['x1', 'y1', 'x2', 'y2'] as $k) {
                if (! isset($w[$k]) || ! is_numeric($w[$k])) {
                    $ok = false;
                    break;
                }
            }
            if (! $ok) {
                continue;
            }
            $walls[] = [
                'x1' => round((float) $w['x1'] * $scale, 2),
                'y1' => round((float) $w['y1'] * $scale, 2),
                'x2' => round((float) $w['x2'] * $scale, 2),
                'y2' => round((float) $w['y2'] * $scale, 2),
            ];
        }

        $corners = is_array($data['grid_corners'] ?? null) ? $data['grid_corners'] : null;
        if ($corners !== null && $permille) {
            foreach ($corners as $name => $c) {
                if (is_array($c) && isset($c['x'], $c['y']) && is_numeric($c['x']) && is_numeric($c['y'])) {
                    $corners[$name] = ['x' => round((float) $c['x'] * $scale, 2), 'y' => round((float) $c['y'] * $scale, 2)];
                }
            }
        }

        $diamond = null;
        if (isset($data['diamond_box']) && is_array($data['diamond_box']) && count($data['diamond_box']) >= 4) {
            $d = array_values($data['diamond_box']);
            if (is_numeric($d[0]) && is_numeric($d[1]) && is_numeric($d[2]) && is_numeric($d[3])) {
                $diamond = [
                    round(min((float) $d[0], (float) $d[2]) * $scale, 2),
                    round(min((float) $d[1], (float) $d[3]) * $scale, 2),
                    round(max((float) $d[0], (float) $d[2]) * $scale, 2),
                    round(max((float) $d[1], (float) $d[3]) * $scale, 2),
                ];
                if ($diamond[2] - $diamond[0] <= 0 || $diamond[3] - $diamond[1] <= 0) {
                    $diamond = null;
                }
            }
        }

        $thBox = is_array($data['town_hall_box'] ?? null) ? $data['town_hall_box'] : null;
        if ($thBox !== null && $permille) {
            // در حالت permille، جعبهٔ تاون‌هال هم مثل بقیهٔ مختصات به درصد تبدیل می‌شود.
            foreach (['x', 'y', 'w', 'h'] as $k) {
                if (isset($thBox[$k]) && is_numeric($thBox[$k])) {
                    $thBox[$k] = round((float) $thBox[$k] * $scale, 2);
                }
            }
        }

        $result = [
            'schema_version' => $permille ? self::SCHEMA_VERSION : 1,
            'town_hall_level' => is_numeric($data['town_hall_level'] ?? null) ? (int) $data['town_hall_level'] : null,
            'perspective' => ($data['perspective'] ?? 'isometric') === 'top_down' ? 'top_down' : 'isometric',
            'grid_corners' => $corners,
            'town_hall_box' => $thBox,
            'diamond_box' => $diamond,
            'buildings' => $buildings,
            'walls' => $walls,
            'image_size' => $imageSize,
            'axes_swapped' => false,
        ];

        if ($permille && $this->detectSwappedAxes($result, $imageSize)) {
            Log::warning('LayoutVisionExtractor: swapped axes detected (yxyx); swapping.', ['image_size' => $imageSize]);
            $result = $this->swapAxes($result);
        }

        return $result;
    }

    /**
     * تشخیص جابه‌جایی محورها ([y,x] به جای [x,y]) با نشانه‌های هندسی:
     * لوزی دهکده همیشه پهن‌تر از بلند است، شیب دیوارها |dy/dx| کمتر از ۱ (۰٫۵ در بازی، ~۰٫۸ در رندرها)
     * و جعبهٔ ساختمان‌های ۳×۳ به‌طور معمول پهن‌تر از بلند (نشانهٔ ضعیف).
     */
    public function detectSwappedAxes(array $data, ?array $imageSize): bool
    {
        [$w, $h] = ($imageSize && $imageSize[0] > 0 && $imageSize[1] > 0) ? [(float) $imageSize[0], (float) $imageSize[1]] : [1000.0, 1000.0];
        $px = fn (float $x, float $y): array => [$x / 100 * $w, $y / 100 * $h];

        $diamondVote = null;
        if (is_array($data['diamond_box'] ?? null)) {
            [$x0, $y0] = $px($data['diamond_box'][0], $data['diamond_box'][1]);
            [$x1, $y1] = $px($data['diamond_box'][2], $data['diamond_box'][3]);
            $dw = $x1 - $x0;
            $dh = $y1 - $y0;
            // لوزی‌ای که بیش از ۹۰٪ تصویر را می‌پوشاند فقط محدودهٔ برش است، نه دهکده؛ رأی ندارد.
            if ($dw > 0 && $dh > 0 && $dw * $dh <= 0.9 * $w * $h) {
                $diamondVote = $dh > $dw * 1.05;
            }
        }

        $wallVote = null;
        $slopes = [];
        foreach ($data['walls'] ?? [] as $s) {
            [$ax, $ay] = $px($s['x1'], $s['y1']);
            [$bx, $by] = $px($s['x2'], $s['y2']);
            $dx = abs($bx - $ax);
            $dy = abs($by - $ay);
            if ($dx < 1 && $dy < 1) {
                continue;
            }
            if ($dx < 1e-6) {
                $slopes[] = 99.0;

                continue;
            }
            $slopes[] = $dy / $dx;
        }
        if (count($slopes) >= 5) {
            sort($slopes);
            $median = $slopes[intdiv(count($slopes), 2)];
            $wallVote = $median > 1.1 ? true : ($median < 0.9 ? false : null);
        }

        $boxVote = null;
        $ratios = [];
        foreach ($data['buildings'] ?? [] as $b) {
            if (! isset($b['box']) || $this->catalog->size($b['type']) !== 3) {
                continue;
            }
            [$x0, $y0] = $px($b['box'][0], $b['box'][1]);
            [$x1, $y1] = $px($b['box'][2], $b['box'][3]);
            if ($x1 > $x0 && $y1 > $y0) {
                $ratios[] = ($y1 - $y0) / ($x1 - $x0);
            }
        }
        if (count($ratios) >= 5) {
            sort($ratios);
            $boxVote = $ratios[intdiv(count($ratios), 2)] > 1.3;
        }

        // رأی لوزی به‌تنهایی کافی نیست: در آپلود عمودی (portrait) «d» فقط محدودهٔ تصویر است و
        // بلندتر از پهن می‌شود بی‌آن‌که محورها جابه‌جا شده باشند. جابه‌جایی فقط با دو نشانهٔ مستقل.
        if ($diamondVote !== null) {
            return $diamondVote && ($wallVote === true || $boxVote === true);
        }
        if ($wallVote !== null) {
            return $wallVote;
        }

        return $boxVote === true;
    }

    /**
     * جابه‌جایی x↔y در همهٔ مختصات (وقتی مدل ترتیب [y,x] داده است).
     */
    protected function swapAxes(array $data): array
    {
        foreach ($data['buildings'] as &$b) {
            [$b['x'], $b['y']] = [$b['y'], $b['x']];
            if (isset($b['box'])) {
                $b['box'] = [$b['box'][1], $b['box'][0], $b['box'][3], $b['box'][2]];
            }
        }
        unset($b);
        foreach ($data['walls'] as &$s) {
            $s = ['x1' => $s['y1'], 'y1' => $s['x1'], 'x2' => $s['y2'], 'y2' => $s['x2']];
        }
        unset($s);
        if (is_array($data['diamond_box'])) {
            $d = $data['diamond_box'];
            $data['diamond_box'] = [$d[1], $d[0], $d[3], $d[2]];
        }
        if (is_array($data['grid_corners'])) {
            foreach ($data['grid_corners'] as $name => $c) {
                if (is_array($c) && isset($c['x'], $c['y'])) {
                    $data['grid_corners'][$name] = ['x' => $c['y'], 'y' => $c['x']];
                }
            }
        }
        if (is_array($data['town_hall_box'] ?? null)) {
            $t = $data['town_hall_box'];
            $data['town_hall_box'] = array_merge($t, [
                'x' => $t['y'] ?? null, 'y' => $t['x'] ?? null,
                'w' => $t['h'] ?? null, 'h' => $t['w'] ?? null,
            ]);
        }
        $data['axes_swapped'] = true;

        return $data;
    }

    /**
     * تبدیل اسکیمای فشرده (th/p/d/c/thb/b/w با آرایه) به اسکیمای کامل؛ اسکیمای کامل دست‌نخورده می‌ماند.
     * اگر نشانه‌های قرارداد جدید (کلید «d» یا ردیف‌های ۵-۶تایی) دیده شود، _units = permille می‌شود.
     */
    protected function expandCompact(array $data): array
    {
        $permille = array_key_exists('d', $data) || ($data['_units'] ?? null) === 'permille';

        if (array_key_exists('th', $data) && ! array_key_exists('town_hall_level', $data)) {
            $data['town_hall_level'] = $data['th'];
        }
        if (isset($data['p']) && ! isset($data['perspective'])) {
            $data['perspective'] = $data['p'] === 'top' ? 'top_down' : 'isometric';
        }
        if (isset($data['c']) && is_array($data['c']) && count($data['c']) >= 8 && ! isset($data['grid_corners'])) {
            $c = array_values($data['c']);
            $data['grid_corners'] = [
                'top' => ['x' => $c[0], 'y' => $c[1]],
                'right' => ['x' => $c[2], 'y' => $c[3]],
                'bottom' => ['x' => $c[4], 'y' => $c[5]],
                'left' => ['x' => $c[6], 'y' => $c[7]],
            ];
        }
        if (isset($data['d']) && is_array($data['d']) && count($data['d']) >= 4 && ! isset($data['diamond_box'])) {
            $data['diamond_box'] = array_slice(array_values($data['d']), 0, 4);
        }
        if (isset($data['thb']) && is_array($data['thb']) && count($data['thb']) >= 4 && ! isset($data['town_hall_box'])) {
            $t = array_values($data['thb']);
            $data['town_hall_box'] = ['x' => $t[0], 'y' => $t[1], 'w' => $t[2], 'h' => $t[3]];
        }
        if (isset($data['b']) && is_array($data['b']) && ! isset($data['buildings'])) {
            $data['buildings'] = array_map(fn ($row) => $this->rowToBuilding($row), $data['b']);
        }
        if (isset($data['w']) && is_array($data['w']) && ! isset($data['walls'])) {
            $data['walls'] = array_map(fn ($row) => $this->rowToWall($row), $data['w']);
        }

        foreach ((array) ($data['buildings'] ?? []) as $b) {
            if (is_array($b) && isset($b['box'])) {
                $permille = true;
                break;
            }
        }
        $data['_units'] = $permille ? 'permille' : 'pct';

        return $data;
    }

    /**
     * ردیف فشرده → شیء ساختمان. ردیف ۵-۶تایی [type,x0,y0,x1,y1(,level)] جعبه دارد؛
     * ردیف ۳-۴تایی [type,x,y(,level)] قرارداد قدیمی است.
     *
     * @param  mixed  $row
     */
    protected function rowToBuilding($row): array
    {
        if (is_array($row) && array_is_list($row)) {
            $n = count($row);
            if ($n >= 5 && is_numeric($row[1]) && is_numeric($row[2]) && is_numeric($row[3]) && is_numeric($row[4])) {
                $b = ['type' => $row[0], 'box' => [$row[1], $row[2], $row[3], $row[4]]];
                if (isset($row[5]) && is_numeric($row[5])) {
                    $b['level'] = $row[5];
                }

                return $b;
            }
            if ($n >= 3) {
                $b = ['type' => $row[0], 'x' => $row[1], 'y' => $row[2]];
                if (isset($row[3]) && is_numeric($row[3])) {
                    $b['level'] = $row[3];
                }

                return $b;
            }

            return [];
        }

        return is_array($row) ? $row : [];
    }

    /** @param  mixed  $row */
    protected function rowToWall($row): array
    {
        if (is_array($row) && array_is_list($row) && count($row) >= 4) {
            return ['x1' => $row[0], 'y1' => $row[1], 'x2' => $row[2], 'y2' => $row[3]];
        }

        return is_array($row) ? $row : [];
    }

    /**
     * بازیابی داده از JSON ناقص (قطع‌شده در سقف توکن): فیلدهای اسکالر و هر ساختمان/دیوار کامل با regex.
     * هر سه اسکیما (فشردهٔ جدید با جعبه، فشردهٔ قدیمی و کامل) پشتیبانی می‌شود.
     */
    protected function salvage(string $content): ?array
    {
        $data = [];

        if (preg_match('/"(?:th|town_hall_level)"\s*:\s*(\d+)/', $content, $m)) {
            $data['town_hall_level'] = (int) $m[1];
        }
        if (preg_match('/"p"\s*:\s*"(iso|top)"/', $content, $m)) {
            $data['perspective'] = $m[1] === 'top' ? 'top_down' : 'isometric';
        } elseif (preg_match('/"perspective"\s*:\s*"(isometric|top_down)"/', $content, $m)) {
            $data['perspective'] = $m[1];
        }
        if (preg_match('/"d"\s*:\s*(\[[-\d.,\s]+\]|null)/', $content, $m)) {
            $data['_units'] = 'permille';
            $d = $m[1] === 'null' ? null : json_decode($m[1], true);
            if (is_array($d) && count($d) >= 4) {
                $data['diamond_box'] = array_slice($d, 0, 4);
            }
        }
        if (preg_match('/"c"\s*:\s*(\[[-\d.,\s]+\])/', $content, $m)) {
            $c = json_decode($m[1], true);
            if (is_array($c) && count($c) >= 8) {
                $data['grid_corners'] = [
                    'top' => ['x' => $c[0], 'y' => $c[1]], 'right' => ['x' => $c[2], 'y' => $c[3]],
                    'bottom' => ['x' => $c[4], 'y' => $c[5]], 'left' => ['x' => $c[6], 'y' => $c[7]],
                ];
            }
        } elseif (preg_match('/"grid_corners"\s*:\s*(\{(?:[^{}]|\{[^{}]*\})*\})/', $content, $m)) {
            $corners = json_decode($m[1], true);
            $data['grid_corners'] = is_array($corners) ? $corners : null;
        }
        if (preg_match('/"thb"\s*:\s*(\[[-\d.,\s]+\])/', $content, $m)) {
            $t = json_decode($m[1], true);
            if (is_array($t) && count($t) >= 4) {
                $data['town_hall_box'] = ['x' => $t[0], 'y' => $t[1], 'w' => $t[2], 'h' => $t[3]];
            }
        } elseif (preg_match('/"town_hall_box"\s*:\s*(\{[^{}]*\})/', $content, $m)) {
            $box = json_decode($m[1], true);
            $data['town_hall_box'] = is_array($box) ? $box : null;
        }

        $buildings = [];
        $walls = [];

        // اسکیمای فشرده: ["type",x0,y0,x1,y1(,level)] (جدید) یا ["type",x,y(,level)] (قدیمی)
        $num = '(-?\d+(?:\.\d+)?)';
        $rowPattern = '/\[\s*"([a-z_]+)"\s*,\s*'.$num.'\s*,\s*'.$num.'(?:\s*,\s*'.$num.'\s*,\s*'.$num.')?(?:\s*,\s*(\d+))?\s*\]/';
        if (preg_match_all($rowPattern, $content, $rows, PREG_SET_ORDER)) {
            foreach ($rows as $r) {
                if (isset($r[4]) && $r[4] !== '' && isset($r[5]) && $r[5] !== '') {
                    $b = ['type' => $r[1], 'box' => [(float) $r[2], (float) $r[3], (float) $r[4], (float) $r[5]]];
                    if (isset($r[6]) && $r[6] !== '') {
                        $b['level'] = (int) $r[6];
                    }
                    $data['_units'] = 'permille';
                } else {
                    $b = ['type' => $r[1], 'x' => (float) $r[2], 'y' => (float) $r[3]];
                    if (isset($r[6]) && $r[6] !== '') {
                        $b['level'] = (int) $r[6];
                    }
                }
                $buildings[] = $b;
            }
        }
        if (preg_match('/"w"\s*:\s*\[(.*)$/s', $content, $wm)
            && preg_match_all('/\[\s*'.$num.'\s*,\s*'.$num.'\s*,\s*'.$num.'\s*,\s*'.$num.'\s*\]/', $wm[1], $rows, PREG_SET_ORDER)) {
            foreach ($rows as $r) {
                $walls[] = ['x1' => (float) $r[1], 'y1' => (float) $r[2], 'x2' => (float) $r[3], 'y2' => (float) $r[4]];
            }
        }

        // اسکیمای کامل: اشیاء {type,x,y} و {x1,y1,x2,y2}
        if ($buildings === [] && preg_match_all('/\{[^{}]*\}/', $content, $objects)) {
            foreach ($objects[0] as $raw) {
                $obj = json_decode($raw, true);
                if (! is_array($obj)) {
                    continue;
                }
                if (isset($obj['type'], $obj['x'], $obj['y'])) {
                    $buildings[] = $obj;
                } elseif (isset($obj['x1'], $obj['y1'], $obj['x2'], $obj['y2'])) {
                    $walls[] = $obj;
                }
            }
        }

        if ($buildings === []) {
            return null;
        }

        $data['buildings'] = $buildings;
        $data['walls'] = $walls;

        return $data;
    }

    protected function systemPrompt(): string
    {
        $types = $this->catalog->promptTypeList();
        $village = strtoupper($this->catalog->villageLabel());
        $grid = $this->catalog->gridSize();

        $prompt = <<<'PROMPT'
You are a Clash of Clans __VILLAGE__ layout digitizer. The user provides a screenshot or picture of a base. Return ONLY a minified JSON object (no markdown, no code fences, no commentary) with exactly this COMPACT schema. Angle brackets are placeholders you must replace with values measured from THIS image; never copy placeholder text or invent numbers.

{"th":<int|null>,"p":"iso"|"top","d":[<x0>,<y0>,<x1>,<y1>]|null,"c":[<top_x>,<top_y>,<right_x>,<right_y>,<bottom_x>,<bottom_y>,<left_x>,<left_y>]|null,"b":[["<type>",<x0>,<y0>,<x1>,<y1>],["<type>",<x0>,<y0>,<x1>,<y1>,<level>]],"w":[[<x1>,<y1>,<x2>,<y2>]]}

COORDINATES: every number is an INTEGER from 0 to 1000 measured on THIS image: x = 0 at the left edge and 1000 at the right edge, y = 0 at the top edge and 1000 at the bottom edge. A box is [x0,y0,x1,y1] = left, top, right, bottom (x0 < x1, y0 < y1). Never use pixels, percentages or [y,x] order.

Keys:
- "th": Town Hall level, or null if unreadable.
- "p": "iso" for in-game screenshots and 3D renders, "top" for flat top-down diagrams.
- "d": bounding box of the visible village diamond (the grass / buildable area), or null if it is not visible.
- "c": the 4 corners of the whole __GRID__x__GRID__ village diamond in order top, right, bottom, left. Give it ONLY if all four corners are clearly visible inside the image; otherwise null. Never extrapolate.
- "b": one row per building INSTANCE: a TIGHT box around the WHOLE sprite, including its roof/top and its base on the ground; the box must have the real width and height of that sprite (bigger buildings get bigger boxes). Add the level as a 6th value only when clearly readable. Allowed types: __TYPES__.
- "w": straight wall runs as [x1,y1,x2,y2] from the centre of the first wall piece to the centre of the last one. Walls only run along the two diagonal directions of the village grid. Merge consecutive pieces in a straight line into one run; never output a run with identical endpoints. Empty array if no walls.

Rules:
- Do NOT include traps (bomb, spring trap, air bomb, seeking air mine, skeleton trap, tornado trap, giga bomb), hero banners, decorations, statues, obstacles (trees, rocks, gem boxes), Clan Capital or other-village items, or anything outside the village.
- Be complete but honest: a Town Hall 12+ base usually has 60-80 buildings excluding walls. List every cannon, archer tower, storage, collector, camp and builder hut separately. Never invent buildings you cannot see and never list the same building twice.
- Output MINIFIED JSON on one line, keys in the order th, p, d, c, b, w.
PROMPT;

        return str_replace(['__TYPES__', '__VILLAGE__', '__GRID__'], [$types, $village, (string) $grid], $prompt);
    }

    protected function userPrompt(): string
    {
        return 'Digitize this Clash of Clans base layout. Return the full JSON: diamond box, corners (only if fully visible), a tight box for every building instance and the wall runs.';
    }

    protected function maxTokens(): int
    {
        // provider پشت nabu-vision این عدد را «کل بودجه» (ورودی + تصویر ≈ ۶٬۵۰۰ توکن + خروجی) حساب می‌کند؛
        // با ۸۰۰۰ فقط ~۲۰۰ توکن خروجی می‌ماند. ۲۴۰۰۰ برای ~۱۲۰ ردیف جعبه + دیوارها (~۵k توکن) کافی است.
        return 24000;
    }

    protected function temperature(): float
    {
        return 0.1;
    }
}
