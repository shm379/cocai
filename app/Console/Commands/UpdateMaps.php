<?php

namespace App\Console\Commands;

use App\Models\Map;
use App\Services\MapSources\MapImageHasher;
use App\Services\MapSources\MapImporter;
use App\Services\MapSources\MapSourceRegistry;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * به‌روزرسانی آرشیو نقشه‌ها از منابع (پیش‌فرض: همهٔ منابع، جدیدترین‌ها).
 */
class UpdateMaps extends Command
{
    protected $signature = 'maps:update
                            {--source=all : کلید منبع (clasher، ...) یا all}
                            {--th= : فقط این سطح تاون‌هال/بیلدرهال}
                            {--village= : home یا builder}
                            {--category= : war|trophy|farming|hybrid|progress|funny|cwl}
                            {--sort=new : ترتیب فهرست منبع (new یا like)}
                            {--limit=0 : حداکثر آیتم از هر منبع (۰ = همه)}
                            {--since= : فقط نقشه‌های منتشرشده بعد از این تاریخ (YYYY-MM-DD)}
                            {--hash : هش تصویر نقشه‌های جدید بلافاصله}
                            {--dry-run : فقط شمارش، بدون نوشتن}';

    protected $description = 'دریافت نقشه‌های جدید از Clasher.us و منابع دیگر و افزودن به آرشیو';

    public function handle(MapSourceRegistry $registry, MapImporter $importer, MapImageHasher $hasher): int
    {
        $sourceKey = (string) $this->option('source');
        try {
            $sources = $sourceKey === 'all' ? $registry->all() : [$registry->get($sourceKey)];
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $since = $this->option('since') ? Carbon::parse((string) $this->option('since')) : null;
        $dryRun = (bool) $this->option('dry-run');
        $options = [
            'th' => $this->option('th') ? (int) $this->option('th') : null,
            'village' => $this->option('village') ?: null,
            'category' => $this->option('category') ?: null,
            'sort' => (string) $this->option('sort'),
            'limit' => (int) $this->option('limit'),
            'since' => $since,
        ];

        $rows = [];
        $newIds = [];
        $failedSources = 0;
        $started = microtime(true);
        $before = Map::count();

        foreach ($sources as $source) {
            $this->info(($dryRun ? '[dry-run] ' : '')."منبع: {$source->label()} ({$source->key()})");
            $listRows = [];
            $options['progress'] = function (string $village, int $level, string $category, int $count) use (&$listRows) {
                $listRows[] = [$village, $level, $category, $count];
            };

            try {
                $stats = $importer->import($source->fetch($options), $source->key(), $dryRun);
            } catch (\Throwable $e) {
                $failedSources++;
                $this->error("  خطا: ".$e->getMessage());

                continue;
            }

            $rows[] = [$source->key(), $stats['fetched'], $stats['inserted'], $stats['updated'], $stats['skipped_invalid'], $stats['errors']];
            $newIds = array_merge($newIds, $stats['new_ids']);

            if ($this->getOutput()->isVerbose()) {
                $this->table(['village', 'level', 'category', 'fetched'], $listRows);
            }
        }

        $this->table(['منبع', 'دریافت', 'جدید', 'به‌روز', 'لینک نامعتبر', 'خطا'], $rows);
        $this->info(sprintf('کل آرشیو: %d → %d نقشه، %d ثانیه', $before, Map::count(), round(microtime(true) - $started)));

        if ($this->option('hash') && ! $dryRun && $newIds !== []) {
            $this->info('هش تصویر '.count($newIds).' نقشهٔ جدید…');
            $ok = 0;
            $bar = $this->output->createProgressBar(count($newIds));
            foreach (Map::whereIn('id', $newIds)->whereNull('image_hash')->cursor() as $map) {
                if ($hasher->hashMap($map)) {
                    $ok++;
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
            $this->info("هش شد: {$ok}");
        }

        return ($failedSources > 0 && $failedSources === count($sources)) ? self::FAILURE : self::SUCCESS;
    }
}
