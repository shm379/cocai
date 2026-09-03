<?php

namespace App\Console\Commands;

use App\Models\Map;
use App\Services\BaseClone\ImageHasher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * محاسبهٔ هش ادراکی تصویر نقشه‌های آرشیو تا Base Cloner بتواند تصویر آپلودی را
 * با آن‌ها تطبیق دهد و لینک کپی داخل بازی را برگرداند.
 */
class HashMapImages extends Command
{
    protected $signature = 'maps:hash
                            {--force : محاسبهٔ مجدد برای نقشه‌هایی که قبلاً هش دارند}
                            {--limit=0 : حداکثر تعداد نقشه (۰ = همه)}
                            {--shard= : تقسیم کار بین چند فرایند، به شکل k/n (مثلاً 0/4)}';

    protected $description = 'محاسبهٔ هش ادراکی (dHash) تصویر نقشه‌ها برای تطبیق در Base Cloner';

    public function handle(ImageHasher $hasher): int
    {
        $query = Map::query()
            ->where(function ($q) {
                $q->whereNotNull('thumbnail_url')->orWhereNotNull('image_url');
            });

        if (! $this->option('force')) {
            $query->whereNull('image_hash');
        }

        if ($shard = (string) $this->option('shard')) {
            if (! preg_match('/^(\d+)\/(\d+)$/', $shard, $m) || (int) $m[2] < 1 || (int) $m[1] >= (int) $m[2]) {
                $this->error('فرمت --shard باید k/n باشد (k < n).');

                return self::FAILURE;
            }
            $query->whereRaw('(id % ?) = ?', [(int) $m[2], (int) $m[1]]);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $maps = $query->get(['id', 'image_url', 'thumbnail_url', 'image_hash']);

        if ($maps->isEmpty()) {
            $this->info('نقشه‌ای برای هش کردن وجود ندارد.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($maps->count());
        $ok = 0;
        $failed = 0;

        foreach ($maps as $map) {
            // تصویر کامل همان فریم اسکرین‌شات کاربر است (فاصلهٔ هش ۰)؛ بندانگشتی ۳۵۰px برش/مقیاس دیگری دارد (فاصله ~۱۲).
            // img.clasher.us مسیر /fullo/ را پاسخ نمی‌دهد ولی /full/ را می‌دهد.
            $hash = null;
            foreach ($this->candidateUrls($map) as $url) {
                try {
                    $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (cocai maps:hash)'])
                        ->timeout(25)->connectTimeout(8)->get($url);
                    if ($response->ok() && strlen($response->body()) > 2000) {
                        $hash = $hasher->hashBinary($response->body());
                    }
                } catch (\Throwable $e) {
                    $hash = null;
                }
                if ($hash !== null) {
                    break;
                }
            }

            if ($hash !== null) {
                $map->image_hash = $hash;
                $map->save();
                $ok++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("هش شد: {$ok}، ناموفق: {$failed}");

        return self::SUCCESS;
    }

    /**
     * ترتیب تلاش: تصویر کامل (full)، سپس image_url خام، در نهایت بندانگشتی.
     *
     * @return array<int, string>
     */
    protected function candidateUrls(Map $map): array
    {
        $urls = [];
        if ($map->image_url) {
            $urls[] = str_replace('/images/fullo/', '/images/full/', $map->image_url);
            $urls[] = $map->image_url;
        }
        if ($map->thumbnail_url) {
            $urls[] = $map->thumbnail_url;
        }

        return array_values(array_unique($urls));
    }
}
