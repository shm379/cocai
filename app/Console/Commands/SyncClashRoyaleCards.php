<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * به‌روزرسانی کاتالوگ کارت‌های کلش رویال (database/data/cr/cards.json).
 *
 * اولویت با API رسمی (نیازمند CLASH_ROYALE_API_TOKEN) است؛ در غیر این صورت از
 * دادهٔ عمومی RoyaleAPI استفاده می‌شود. کارت‌های دریافتی از API رسمی «verified» می‌شوند.
 */
class SyncClashRoyaleCards extends Command
{
    protected $signature = 'cr:cards {--source=auto : official | royaleapi | auto}';

    protected $description = 'به‌روزرسانی شناسهٔ کارت‌های کلش رویال برای لینک کپی دک';

    protected const ROYALEAPI_URL = 'https://royaleapi.github.io/cr-api-data/json/cards.json';

    public function handle(): int
    {
        $path = database_path('data/cr/cards.json');
        $existing = is_file($path) ? (json_decode((string) file_get_contents($path), true)['cards'] ?? []) : [];
        $byId = [];
        foreach ($existing as $card) {
            $byId[(int) $card['id']] = $card;
        }

        $source = $this->option('source');
        $token = config('services.clash_royale.token');
        $fetched = null;
        $usedSource = null;

        if (in_array($source, ['auto', 'official'], true) && ! empty($token)) {
            $fetched = $this->fetchOfficial($token);
            $usedSource = 'official';
        }

        if ($fetched === null && in_array($source, ['auto', 'royaleapi'], true)) {
            $fetched = $this->fetchRoyaleApi();
            $usedSource = 'royaleapi';
        }

        if ($fetched === null) {
            $this->error('دریافت کارت‌ها ناموفق بود (توکن API یا دسترسی اینترنت را بررسی کنید).');

            return self::FAILURE;
        }

        $added = 0;
        $updated = 0;
        foreach ($fetched as $card) {
            $id = (int) $card['id'];
            if (isset($byId[$id])) {
                $byId[$id] = array_merge($byId[$id], $card);
                $updated++;
            } else {
                $byId[$id] = $card;
                $added++;
            }
        }

        ksort($byId);
        file_put_contents($path, json_encode([
            'source' => $usedSource.' (synced by cr:cards)',
            'updated_at' => now()->toDateString(),
            'cards' => array_values($byId),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info("منبع: {$usedSource} — افزوده: {$added}، به‌روز: {$updated}، کل: ".count($byId));

        return self::SUCCESS;
    }

    protected function fetchOfficial(string $token): ?array
    {
        try {
            $response = Http::timeout(20)->withToken($token)->acceptJson()->get('https://api.clashroyale.com/v1/cards');
        } catch (\Throwable $e) {
            $this->warn('API رسمی: '.$e->getMessage());

            return null;
        }

        if (! $response->ok()) {
            $this->warn('API رسمی پاسخ '.$response->status().' داد.');

            return null;
        }

        $out = [];
        foreach (['items' => null, 'supportItems' => 'Tower Troop'] as $bucket => $forcedType) {
            foreach ((array) $response->json($bucket, []) as $item) {
                if (! isset($item['id'], $item['name'])) {
                    continue;
                }
                $out[] = [
                    'key' => str($item['name'])->lower()->replace(['.', "'"], '')->slug()->toString(),
                    'id' => (int) $item['id'],
                    'name' => $item['name'],
                    'elixir' => (int) ($item['elixirCost'] ?? 0),
                    'rarity' => ucfirst(strtolower($item['rarity'] ?? 'common')),
                    'type' => $forcedType ?? $this->typeFromId((int) $item['id']),
                    'verified' => true,
                ];
            }
        }

        return $out;
    }

    protected function fetchRoyaleApi(): ?array
    {
        try {
            $response = Http::timeout(20)->acceptJson()->get(self::ROYALEAPI_URL);
        } catch (\Throwable $e) {
            $this->warn('RoyaleAPI: '.$e->getMessage());

            return null;
        }

        if (! $response->ok() || ! is_array($response->json())) {
            return null;
        }

        $out = [];
        foreach ($response->json() as $item) {
            if (! isset($item['id'], $item['name'])) {
                continue;
            }
            $out[] = [
                'key' => $item['key'] ?? str($item['name'])->slug()->toString(),
                'id' => (int) $item['id'],
                'name' => $item['name'],
                'elixir' => (int) ($item['elixir'] ?? 0),
                'rarity' => $item['rarity'] ?? 'Common',
                'type' => $item['type'] ?? $this->typeFromId((int) $item['id']),
                'verified' => true,
            ];
        }

        return $out;
    }

    protected function typeFromId(int $id): string
    {
        return match (intdiv($id, 1000000)) {
            26 => 'Troop',
            27 => 'Building',
            28 => 'Spell',
            159 => 'Tower Troop',
            default => 'Troop',
        };
    }
}
