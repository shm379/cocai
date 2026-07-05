<?php

namespace App\Services\GameData;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * دادهٔ مرجع بازی (سقف هیروها per TH، اولویت لَب، ارتش‌های متا).
 * منبع: database/data/coc/*.json — نسخهٔ داده در فیلد data_version.
 */
class GameDataService
{
    private const CACHE_TTL = 3600;

    public function units(): array
    {
        return Cache::remember('coc.units', self::CACHE_TTL, function () {
            return json_decode(File::get(database_path('data/coc/units.json')), true) ?: [];
        });
    }

    public function armies(): array
    {
        return Cache::remember('coc.armies', self::CACHE_TTL, function () {
            return json_decode(File::get(database_path('data/coc/armies.json')), true) ?: [];
        });
    }

    public function dataVersion(): string
    {
        return $this->units()['data_version'] ?? 'unknown';
    }

    /**
     * سقف لِوِل هیرو در تاون‌هال داده‌شده. null یعنی در این TH باز نشده.
     */
    public function heroCap(string $name, int $th): ?int
    {
        $hero = $this->units()['heroes'][$name] ?? null;
        if (! $hero || $th < ($hero['unlock_th'] ?? PHP_INT_MAX)) {
            return null;
        }

        // اگر TH بالاتر از آخرین کلید جدول بود، بزرگ‌ترین سقف موجود را بده
        $caps = $hero['max_by_th'];

        return $caps[(string) $th] ?? max($caps);
    }

    /**
     * سقف هیرو در تاون‌هال قبلی — مبنای تشخیص بیس راش‌شده.
     */
    public function heroCapPreviousTh(string $name, int $th): ?int
    {
        return $th > 1 ? $this->heroCap($name, $th - 1) : null;
    }

    public function heroNames(): array
    {
        return array_keys($this->units()['heroes'] ?? []);
    }

    public function unitMeta(string $name): ?array
    {
        return $this->units()['units'][$name] ?? null;
    }

    /**
     * ارتش‌های پیشنهادی (war/farm) برای یک TH. برای THهای زیر ۷ نزدیک‌ترین را بده.
     */
    public function armiesForTh(int $th): array
    {
        $byTh = $this->armies()['th'] ?? [];
        $key = (string) max(7, min(17, $th));

        return $byTh[$key] ?? ['war' => [], 'farm' => []];
    }

    /**
     * نام یونیت‌ها/طلسم‌هایی که در ارتش‌های جنگی TH بازیکن استفاده می‌شوند —
     * این‌ها اولویت اول لَب هستند.
     */
    public function warUnitNamesForTh(int $th): array
    {
        $names = [];
        foreach ($this->armiesForTh($th)['war'] ?? [] as $army) {
            $names = array_merge($names, $army['units'] ?? [], $army['spells'] ?? []);
        }

        return array_values(array_unique($names));
    }
}
