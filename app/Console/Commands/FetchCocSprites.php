<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * دریافت/کپی اسپرایت‌های ساختمان‌ها بر اساس مانیفست database/data/coc/sprites.json.
 *
 * منابع:
 *  - local: فایل‌های بسته‌بندی‌شدهٔ public/images/coc/units (آینهٔ clasher.us)
 *  - fandom: ویکی Clash of Clans از طریق MediaWiki API (هرگز میزبان HTML صدا زده نمی‌شود؛ ۴۰۳ می‌دهد)
 *  - procedural: کاشی زمین که با GD ساخته می‌شود
 *
 * خروجی: public/images/coc/buildings/{home|builder|walls|ground}/<type>.png (PNG واقعی، عرض ≤ max_width).
 */
class FetchCocSprites extends Command
{
    protected $signature = 'coc:sprites
        {--only= : فقط این نوع‌ها (با کاما جدا شود؛ مثل town_hall,cannon,walls,ground)}
        {--force : بازنویسی فایل‌های موجود}';

    protected $description = 'دریافت اسپرایت‌های ساختمان‌های کلش (دهکدهٔ اصلی و بیلدر بیس) برای رندر بیس کلونر';

    protected const API = 'https://clashofclans.fandom.com/api.php';

    protected const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    protected const BATCH = 50;

    protected const PNG_MAGIC = "\x89PNG\r\n\x1a\n";

    protected int $maxWidth = 256;

    public function handle(): int
    {
        $manifestPath = database_path('data/coc/sprites.json');
        $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;

        if (! is_array($manifest)) {
            $this->error('مانیفست database/data/coc/sprites.json یافت نشد یا نامعتبر است.');

            return self::FAILURE;
        }

        $this->maxWidth = (int) ($manifest['max_width'] ?? 256);
        $outDir = public_path(trim((string) ($manifest['public_path'] ?? '/images/coc/buildings'), '/'));
        $unitsDir = public_path('images/coc/units');
        $force = (bool) $this->option('force');
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        $jobs = $this->collectJobs($manifest, $only);
        if ($jobs === []) {
            $this->warn('هیچ موردی برای پردازش پیدا نشد.');

            return self::SUCCESS;
        }

        // ۱) نام‌های ویکی را دسته‌ای (حداکثر ۵۰ عنوان در هر درخواست) به URL تبدیل می‌کنیم.
        $titles = [];
        foreach ($jobs as $job) {
            if ($job['source'] === 'fandom' && ($force || ! is_file($outDir.'/'.$job['file']))) {
                $titles[$job['title']] = true;
            }
        }
        $resolved = $titles === [] ? [] : $this->resolveTitles(array_keys($titles));

        // ۲) هر مورد را پردازش می‌کنیم.
        $rows = [];
        $missing = [];
        foreach ($jobs as $job) {
            $target = $outDir.'/'.$job['file'];
            $ref = $job['title'] ?? $job['local'] ?? '—';

            if (! $force && is_file($target)) {
                $rows[] = [$job['key'], $job['source'], $ref, 'موجود', $this->dims($target)];

                continue;
            }

            try {
                $bytes = match ($job['source']) {
                    'local' => $this->fromLocal($unitsDir.'/'.$job['local']),
                    'fandom' => $this->fromFandom($job['title'], $resolved),
                    'procedural' => $this->procedural($job),
                    default => throw new \RuntimeException("منبع ناشناخته: {$job['source']}"),
                };
            } catch (Throwable $e) {
                $rows[] = [$job['key'], $job['source'], $ref, 'ناموفق: '.$e->getMessage(), '—'];
                $missing[] = $job['key'].' ← '.$ref.' ('.$e->getMessage().')';

                continue;
            }

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }
            file_put_contents($target, $bytes);
            $rows[] = [$job['key'], $job['source'], $ref, 'دریافت شد', $this->dims($target)];
        }

        $this->table(['نوع', 'منبع', 'مرجع', 'وضعیت', 'ابعاد'], $rows);

        $ok = count($rows) - count($missing);
        $this->info("پردازش‌شده: {$ok}/".count($rows).' — خروجی: '.$outDir);

        if ($missing !== []) {
            $this->warn('موارد حل‌نشده ('.count($missing).'):');
            foreach ($missing as $m) {
                $this->line('  - '.$m);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{key:string,file:string,source:string,title?:string,local?:string,width?:int,height?:int}>
     */
    protected function collectJobs(array $manifest, array $only): array
    {
        $jobs = [];
        $wanted = fn (string $type, string $group) => $only === [] || in_array($type, $only, true) || in_array($group, $only, true);

        foreach (['home', 'builder', 'walls', 'ground'] as $group) {
            foreach ($manifest[$group] ?? [] as $type => $entry) {
                if (! is_array($entry) || ! $wanted((string) $type, $group)) {
                    continue;
                }
                $jobs[] = $this->job("{$group}/{$type}", $entry);
                foreach ($entry['levels'] ?? [] as $level => $lv) {
                    if (is_array($lv)) {
                        $jobs[] = $this->job("{$group}/{$type}@{$level}", $lv);
                    }
                }
            }
        }

        return $jobs;
    }

    protected function job(string $key, array $entry): array
    {
        return array_filter([
            'key' => $key,
            'file' => (string) $entry['file'],
            'source' => (string) ($entry['source'] ?? 'local'),
            'title' => $entry['title'] ?? null,
            'local' => $entry['local'] ?? null,
            'width' => $entry['width'] ?? null,
            'height' => $entry['height'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /**
     * نگاشت عنوان → ['url' => …, 'width' => …] از طریق MediaWiki API (با تلاش مجدد و backoff).
     *
     * @param  array<int, string>  $titles
     * @return array<string, array{url:string,width:int,height:int}>
     */
    protected function resolveTitles(array $titles): array
    {
        $out = [];

        foreach (array_chunk($titles, self::BATCH) as $chunk) {
            $query = implode('|', array_map(fn ($t) => 'File:'.$t, $chunk));
            $response = $this->http()->get(self::API, [
                'action' => 'query',
                'titles' => $query,
                'prop' => 'imageinfo',
                'iiprop' => 'url|size',
                'format' => 'json',
            ]);

            if (! $response->ok()) {
                $this->warn('پاسخ API ویکی: HTTP '.$response->status());

                continue;
            }

            $data = $response->json();
            $normalized = [];
            foreach ($data['query']['normalized'] ?? [] as $n) {
                $normalized[$n['to']] = $n['from'];
            }

            foreach ($data['query']['pages'] ?? [] as $page) {
                $title = (string) ($page['title'] ?? '');
                $requested = $normalized[$title] ?? $title;
                $requested = preg_replace('/^File:/', '', $requested) ?? $requested;
                $info = $page['imageinfo'][0] ?? null;
                if (! is_array($info) || empty($info['url'])) {
                    continue;
                }
                $out[$requested] = [
                    'url' => (string) $info['url'],
                    'width' => (int) ($info['width'] ?? 0),
                    'height' => (int) ($info['height'] ?? 0),
                ];
                // نام با فاصله و زیرخط هر دو قابل جست‌وجو باشند.
                $out[str_replace('_', ' ', $requested)] = $out[$requested];
            }
        }

        return $out;
    }

    protected function fromFandom(string $title, array $resolved): string
    {
        $info = $resolved[$title] ?? $resolved[str_replace('_', ' ', $title)] ?? null;
        if ($info === null) {
            throw new \RuntimeException('عنوان در ویکی پیدا نشد');
        }

        $base = strtok($info['url'], '?');
        $url = $info['width'] > $this->maxWidth
            ? $base.'/scale-to-width-down/'.$this->maxWidth.'?format=original'
            : $base.'?format=original';

        $response = $this->http()->get($url);
        if (! $response->ok()) {
            throw new \RuntimeException('دانلود ناموفق (HTTP '.$response->status().')');
        }

        $bytes = $response->body();
        if (! $this->isPng($bytes)) {
            // CDN گاهی WebP می‌دهد؛ اگر GD بتواند، به PNG تبدیل می‌کنیم.
            if (str_starts_with($bytes, 'RIFF') && function_exists('imagecreatefromwebp')) {
                return $this->downscale($bytes, true);
            }
            throw new \RuntimeException('پاسخ PNG نیست');
        }

        return $this->downscale($bytes);
    }

    protected function fromLocal(string $path): string
    {
        if (! is_file($path)) {
            throw new \RuntimeException('فایل محلی وجود ندارد');
        }

        $bytes = (string) file_get_contents($path);
        $isWebp = str_starts_with($bytes, 'RIFF');
        if (! $isWebp && ! $this->isPng($bytes)) {
            throw new \RuntimeException('فایل محلی PNG/WebP نیست');
        }

        [$w] = @getimagesizefromstring($bytes) ?: [0];
        if (! $isWebp && $w <= $this->maxWidth) {
            return $bytes; // بدون تغییر کپی می‌شود.
        }

        return $this->downscale($bytes, $isWebp);
    }

    /**
     * کاهش عرض به max_width با حفظ آلفا و خروجی PNG (تنها تغییر اندازه؛ بدون دستکاری اثر).
     */
    protected function downscale(string $bytes, bool $webp = false): string
    {
        $src = $webp ? @imagecreatefromwebp('data://image/webp;base64,'.base64_encode($bytes)) : @imagecreatefromstring($bytes);
        if ($src === false) {
            throw new \RuntimeException('GD نتوانست تصویر را بخواند');
        }

        if (! imageistruecolor($src)) {
            imagepalettetotruecolor($src);
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $dst = $src;

        if ($w > $this->maxWidth) {
            $nw = $this->maxWidth;
            $nh = max(1, (int) round($h * $nw / $w));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        ob_start();
        imagepng($dst, null, 9);

        return (string) ob_get_clean();
    }

    /**
     * کاشی لوزی چمن (رویه‌ای) برای زمین؛ دارایی Supercell نیست.
     */
    protected function procedural(array $job): string
    {
        $w = (int) ($job['width'] ?? 128);
        $h = (int) ($job['height'] ?? 64);
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

        mt_srand(42);
        $cx = $w / 2;
        $cy = $h / 2;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                // داخل لوزی: |dx|/(w/2) + |dy|/(h/2) <= 1
                if (abs($x + 0.5 - $cx) / $cx + abs($y + 0.5 - $cy) / $cy > 1) {
                    continue;
                }
                $n = mt_rand(-9, 9);
                $r = max(0, min(255, 92 + $n));
                $g = max(0, min(255, 150 + $n + (($x + $y) % 7 === 0 ? 8 : 0)));
                $b = max(0, min(255, 58 + $n));
                imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
            }
        }

        ob_start();
        imagepng($img, null, 9);

        return (string) ob_get_clean();
    }

    protected function http(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'image/png,application/json;q=0.9,*/*;q=0.8'])
            ->timeout(60)
            ->retry([1000, 3000, 6000], throw: false);
    }

    protected function isPng(string $bytes): bool
    {
        return str_starts_with($bytes, self::PNG_MAGIC);
    }

    protected function dims(string $path): string
    {
        $size = @getimagesize($path);
        $kb = round(filesize($path) / 1024);

        return $size ? "{$size[0]}×{$size[1]} ({$kb}KB)" : "? ({$kb}KB)";
    }
}
