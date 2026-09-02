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
                            {--limit=0 : حداکثر تعداد نقشه (۰ = همه)}';

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
            $url = $map->thumbnail_url ?: $map->image_url;

            try {
                $response = Http::timeout(20)->connectTimeout(8)->get($url);
                $hash = $response->ok() ? $hasher->hashBinary($response->body()) : null;
            } catch (\Throwable $e) {
                $hash = null;
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
}
