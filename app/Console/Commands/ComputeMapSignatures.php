<?php

namespace App\Console\Commands;

use App\Models\Map;
use App\Services\AI\LayoutVisionExtractor;
use App\Services\BaseClone\Games\CocLayoutAdapter;
use App\Services\BaseClone\Games\GameRegistry;
use App\Services\BaseClone\ImageHasher;
use App\Services\BaseClone\LayoutGridMapper;
use App\Services\BaseClone\LayoutSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * محاسبهٔ امضای چیدمان ({@see LayoutSignature}) برای نقشه‌های آرشیو تا Base Cloner بتواند
 * بیس آپلودشده را مستقل از تصویر (زوم/برش/اسکین) با آرشیو تطبیق دهد و لینک واقعی بازی برگرداند.
 *
 * هر نقشه یک فراخوانی Vision روی gateway است (~۳۵ تا ۶۵ ثانیه، ~۶٫۵ هزار توکن تصویر)؛ به همین
 * دلیل پیش‌فرض --limit=20 است و فقط نقشه‌هایی پردازش می‌شوند که لینک واقعی بازی دارند
 * (بدون لینک، تطبیق فایده‌ای ندارد). ترتیب: محبوب‌ترین‌ها اول.
 */
class ComputeMapSignatures extends Command
{
    protected $signature = 'maps:signature
                            {--limit=20 : حداکثر تعداد نقشه در این اجرا (۰ = بدون سقف؛ هزینهٔ gateway را در نظر بگیرید)}
                            {--th= : فقط این سطح تاون‌هال/بیلدرهال (از روی لینک بازی)}
                            {--force : محاسبهٔ مجدد برای نقشه‌هایی که قبلاً امضا دارند}';

    protected $description = 'محاسبهٔ امضای چیدمان نقشه‌های آرشیو (Vision → شبکهٔ ۴۴×۴۴) برای تطبیق در Base Cloner';

    public function handle(LayoutVisionExtractor $vision, GameRegistry $games, ImageHasher $hasher): int
    {
        if (! $vision->isConfigured()) {
            $this->error('تنظیمات AI Vision (NABU_API_KEY) انجام نشده است.');

            return self::FAILURE;
        }

        $maps = $this->selectMaps();
        if ($maps->isEmpty()) {
            $this->info('نقشه‌ای برای محاسبهٔ امضا وجود ندارد.');

            return self::SUCCESS;
        }

        $this->info("پردازش {$maps->count()} نقشه (هر کدام یک فراخوانی Vision)...");

        $rows = [];
        $ok = 0;
        $failed = 0;
        $started = microtime(true);

        foreach ($maps as $map) {
            $t0 = microtime(true);
            $info = Map::parseCopyLink($map->copy_link);
            $adapterKey = ($info['village'] ?? 'home') === 'builder' ? 'coc_builder' : 'coc_home';
            $adapter = $games->get($adapterKey);

            $row = ['id' => $map->id, 'th' => $info['th'] ?? '-', 'village' => $info['village'] ?? '-', 'buildings' => '-', 'walls' => '-', 'seconds' => 0, 'status' => ''];

            try {
                if (! $adapter instanceof CocLayoutAdapter) {
                    throw new \RuntimeException('آداپتور چیدمان کلش در دسترس نیست.');
                }

                $result = $this->computeSignature($map, $adapter, $vision, $hasher, $info);
                $row['buildings'] = $result['buildings'];
                $row['walls'] = $result['walls'];
                $row['status'] = 'ok';
                $ok++;
            } catch (\Throwable $e) {
                $row['status'] = 'خطا: '.mb_substr($e->getMessage(), 0, 60);
                $failed++;
            }

            $row['seconds'] = round(microtime(true) - $t0, 1);
            $rows[] = $row;
            $this->line(sprintf('  #%d TH%s %s → %s (%ss)', $row['id'], $row['th'], $row['village'], $row['status'], $row['seconds']));
        }

        $this->newLine();
        $this->table(['ID', 'TH', 'دهکده', 'ساختمان', 'دیوار', 'ثانیه', 'وضعیت'], array_map('array_values', $rows));
        $total = round(microtime(true) - $started, 1);
        $this->info("امضا شد: {$ok}، ناموفق: {$failed}، مجموع زمان: {$total} ثانیه");

        return self::SUCCESS;
    }

    /**
     * نقشه‌های واجد شرایط: لینک واقعی بازی، تصویر، (بدون امضا مگر --force)، فیلتر TH، سقف تعداد.
     *
     * @return \Illuminate\Support\Collection<int, Map>
     */
    protected function selectMaps()
    {
        $limit = max(0, (int) $this->option('limit'));
        $th = $this->option('th') !== null && $this->option('th') !== '' ? (int) $this->option('th') : null;

        $query = Map::query()
            ->likelyValidCopyLink()
            ->where(function ($q) {
                $q->whereNotNull('image_url')->orWhereNotNull('thumbnail_url');
            })
            ->orderByDesc('like_count')
            ->orderBy('id');

        if (! $this->option('force')) {
            $query->whereNull('layout_signature');
        }

        if ($th !== null) {
            // پیش‌فیلتر SQL روی شناسهٔ لینک (TH15: یا TH15%3A)؛ بررسی دقیق در PHP.
            $query->where(function ($q) use ($th) {
                $q->where('copy_link', 'like', "%id=TH{$th}:%")
                    ->orWhere('copy_link', 'like', "%id=TH{$th}\\%3A%");
            });
        }

        $selected = collect();
        foreach ($query->lazy(200) as $map) {
            if (! $map->hasValidCopyLink()) {
                continue;
            }
            if ($th !== null && Map::parseCopyLink($map->copy_link)['th'] !== $th) {
                continue;
            }
            $selected->push($map);
            if ($limit > 0 && $selected->count() >= $limit) {
                break;
            }
        }

        return $selected;
    }

    /**
     * دانلود تصویر، Vision، نگاشت به شبکه، ساخت امضا و ذخیره.
     *
     * @return array{buildings:int,walls:int}
     */
    protected function computeSignature(Map $map, CocLayoutAdapter $adapter, LayoutVisionExtractor $vision, ImageHasher $hasher, ?array $linkInfo): array
    {
        $url = $map->image_url ?: $map->thumbnail_url;
        $response = Http::timeout(30)->connectTimeout(10)->get($url);
        if (! $response->ok() || $response->body() === '') {
            throw new \RuntimeException('دانلود تصویر ناموفق بود ('.$response->status().')');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cocai-map-');
        if ($tmp === false) {
            throw new \RuntimeException('ساخت فایل موقت ناموفق بود.');
        }

        try {
            file_put_contents($tmp, $response->body());

            // هش تصویر هم (اگر ندارد) با همین دانلود محاسبه می‌شود.
            if (! $map->image_hash) {
                $map->image_hash = $hasher->hashFile($tmp);
            }

            $catalog = $adapter->catalog();
            $extracted = $vision->withCatalog($catalog)->extractLayout($tmp);
            if (! ($extracted['ok'] ?? false)) {
                throw new \RuntimeException($extracted['message'] ?? 'Vision ناموفق');
            }

            $visionData = $extracted['data'];
            $size = @getimagesize($tmp);
            $visionData['image_size'] = $size ? [$size[0], $size[1]] : ($visionData['image_size'] ?? null);

            $layout = (new LayoutGridMapper($catalog))->map($visionData, $catalog->gridSize());
            $layout['village'] = $catalog->key();

            $signature = LayoutSignature::fromLayout($layout);
            // نوع دهکده از لینک بازی قطعی است. سطح هال هم برای دهکدهٔ اصلی از لینک (TH<n>) گرفته می‌شود و
            // بر تشخیص مدل ارجح است؛ برای بیلدر بیس معنای پیشوند TH در لینک تأیید نشده، پس تشخیص مدل می‌ماند.
            if ($linkInfo !== null) {
                $signature['village'] = $linkInfo['village'];
                if ($linkInfo['village'] === 'home') {
                    $signature['th'] = $linkInfo['th'];
                }
            }

            $map->layout_signature = $signature;
            $map->signature_computed_at = now();
            $map->save();

            return [
                'buildings' => LayoutSignature::buildingCount($signature),
                'walls' => (int) ($signature['counts']['wall'] ?? 0),
            ];
        } finally {
            @unlink($tmp);
        }
    }
}
