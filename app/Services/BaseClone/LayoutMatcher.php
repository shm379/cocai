<?php

namespace App\Services\BaseClone;

use App\Models\Map;

/**
 * یافتن نقشه‌های آرشیو (Clasher.us) که با بیس آپلودشده یکی یا بسیار مشابه‌اند.
 *
 * تنها راه به‌دست‌آوردن لینک کپی داخل بازی همین است: لینک OpenLayout یک ارجاع
 * ۲۴ بایتی به چیدمانی است که در سرور Supercell ذخیره شده و از روی تصویر قابل ساخت نیست.
 * بنابراین فقط نقشه‌هایی برگردانده می‌شوند که لینک واقعی دارند ({@see Map::hasValidCopyLink}).
 *
 * دو معیار با هم ترکیب می‌شوند:
 *   - dHash تصویر (فاصلهٔ همینگ؛ حساس به زوم/برش/اسکین، ولی برای «همان تصویر» قطعی است)
 *   - امضای چیدمان ({@see LayoutSignature}؛ مستقل از تصویر، نیازمند maps:signature روی آرشیو)
 * پیش‌فیلتر: نوع دهکده باید یکی باشد؛ سطح هال ±۱ (برای امضا الزامی، برای هش فقط اگر تصویر
 * تقریباً یکسان نباشد — چون در آن صورت تشخیص TH مدل اشتباه بوده است).
 */
class LayoutMatcher
{
    /** فاصلهٔ همینگی که «همان بیس» محسوب می‌شود. */
    // با آرشیو ۱۳ هزارتایی، بیس‌های متفاوتِ یک سایت تا فاصلهٔ ۸–۱۰ بیت شبیه هم می‌شوند؛ «مطمئن» فقط تا ۴ بیت.
    public const CONFIDENT_DISTANCE = 4;

    /** بهترین تطبیق هش فقط وقتی مطمئن است که دست‌کم این‌قدر از نفر دوم جلوتر باشد. */
    public const HASH_MARGIN = 4;

    /** حداکثر فاصله برای نمایش به‌عنوان «مشابه احتمالی». */
    public const MAX_DISTANCE = 16;

    /** حداکثر اختلاف سطح هال در پیش‌فیلتر. */
    public const TH_TOLERANCE = 1;

    /** حداقل ساختمان جای‌گرفته تا امضای چیدمان برای امتیازدهی به کار رود. */
    public const MIN_SIGNATURE_BUILDINGS = 5;

    /**
     * @param  string|null  $hash  dHash تصویر آپلودی
     * @param  array|null  $layout  خروجی LayoutGridMapper (یا امضای آمادهٔ LayoutSignature با کلید 'cells')
     * @return array<int, array{id:int,name:string,copy_link:string,map_link:?string,image_url:?string,thumbnail_url:?string,distance:?int,signature_score:?float,similarity:int,method:string,confident:bool}>
     */
    public function findMatches(?string $hash, ?array $layout = null, int $limit = 3): array
    {
        $signature = null;
        if (is_array($layout)) {
            $signature = isset($layout['cells']) && isset($layout['wall_mask']) ? $layout : LayoutSignature::fromLayout($layout);
        }

        $queryTh = $signature['th'] ?? null;
        $queryVillage = $signature['village'] ?? null;

        // امضای تقریباً خالی (مثلاً چند ساختمان از یک برش) قابل اتکا نیست؛ فقط پیش‌فیلتر TH/دهکده از آن می‌ماند.
        if ($signature !== null && LayoutSignature::buildingCount($signature) < self::MIN_SIGNATURE_BUILDINGS) {
            $signature = null;
        }

        if ($hash === null && $signature === null) {
            return [];
        }

        $matches = [];

        $query = Map::query()
            ->likelyValidCopyLink()
            ->where(function ($q) use ($hash, $signature) {
                if ($hash !== null) {
                    $q->whereNotNull('image_hash');
                }
                if ($signature !== null) {
                    $hash !== null ? $q->orWhereNotNull('layout_signature') : $q->whereNotNull('layout_signature');
                }
            })
            ->select(['id', 'name', 'copy_link', 'map_link', 'image_url', 'thumbnail_url', 'image_hash', 'layout_signature']);

        $query->chunkById(500, function ($maps) use ($hash, $signature, $queryTh, $queryVillage, &$matches) {
            foreach ($maps as $map) {
                if (! $map->hasValidCopyLink()) {
                    continue;
                }

                $info = Map::parseCopyLink($map->copy_link);
                if ($queryVillage !== null && $info['village'] !== $queryVillage) {
                    continue;
                }
                $thOk = $queryTh === null || abs($info['th'] - $queryTh) <= self::TH_TOLERANCE;

                $distance = null;
                if ($hash !== null && $map->image_hash) {
                    $d = ImageHasher::distance($hash, $map->image_hash);
                    // خارج از TH±۱ فقط تصویر تقریباً یکسان پذیرفته می‌شود.
                    if ($d <= self::MAX_DISTANCE && ($thOk || $d <= self::CONFIDENT_DISTANCE)) {
                        $distance = $d;
                    }
                }

                $sigScore = null;
                if ($signature !== null && $thOk && is_array($map->layout_signature)) {
                    $s = LayoutSignature::score($signature, $map->layout_signature);
                    if (LayoutSignature::isSimilar($s)) {
                        $sigScore = $s;
                    }
                }

                if ($distance === null && $sigScore === null) {
                    continue;
                }

                $hashSim = $distance === null ? null : ImageHasher::similarity($distance) / 100;
                $method = $distance !== null && $sigScore !== null ? 'both' : ($sigScore !== null ? 'signature' : 'hash');

                $matches[] = [
                    'id' => $map->id,
                    'name' => $map->name,
                    'copy_link' => $map->copy_link,
                    'map_link' => $map->map_link,
                    'image_url' => $map->image_url,
                    'thumbnail_url' => $map->thumbnail_url,
                    'distance' => $distance,
                    'signature_score' => $sigScore,
                    'similarity' => (int) round(max($hashSim ?? 0.0, $sigScore ?? 0.0) * 100),
                    'method' => $method,
                    'confident' => ($distance !== null && $distance <= self::CONFIDENT_DISTANCE)
                        || ($sigScore !== null && LayoutSignature::isConfident($sigScore)),
                ];
            }
        });

        // اطمینان مبتنی بر هش باید یکتا باشد: اگر دو نقشه به یک اندازه نزدیک باشند، هیچ‌کدام «همین بیس» نیست.
        $hashDistances = array_values(array_filter(array_map(fn ($m) => $m['distance'], $matches), fn ($d) => $d !== null));
        sort($hashDistances);
        $best = $hashDistances[0] ?? null;
        $second = $hashDistances[1] ?? null;
        $hashUnique = $best !== null && ($second === null || $second - $best >= self::HASH_MARGIN);

        foreach ($matches as &$m) {
            $hashConfident = $m['distance'] !== null && $m['distance'] <= self::CONFIDENT_DISTANCE && $hashUnique && $m['distance'] === $best;
            $sigConfident = $m['signature_score'] !== null && LayoutSignature::isConfident($m['signature_score']);
            $m['confident'] = $hashConfident || $sigConfident;
        }
        unset($m);

        usort($matches, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity']
                ?: (int) $b['confident'] <=> (int) $a['confident']
                ?: $a['id'] <=> $b['id'];
        });

        return array_slice($matches, 0, $limit);
    }
}
