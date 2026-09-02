<?php

namespace App\Services\BaseClone\Games;

/**
 * فهرست موتورهای بازی. برای افزودن بازی جدید فقط یک آداپتور اضافه کنید.
 */
class GameRegistry
{
    /** @var array<string, GameAdapter> */
    protected array $adapters = [];

    public function __construct(
        CocHomeAdapter $cocHome,
        CocBuilderAdapter $cocBuilder,
        ClashRoyaleDeckAdapter $clashRoyale,
    ) {
        foreach ([$cocHome, $cocBuilder, $clashRoyale] as $adapter) {
            $this->adapters[$adapter->key()] = $adapter;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->adapters[$key]);
    }

    public function get(string $key): GameAdapter
    {
        if (! $this->has($key)) {
            throw new \InvalidArgumentException("Unknown game adapter: {$key}");
        }

        return $this->adapters[$key];
    }

    public function default(): GameAdapter
    {
        return $this->adapters['coc_home'];
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->adapters);
    }

    /** @return array<int, GameAdapter> */
    public function all(): array
    {
        return array_values($this->adapters);
    }

    /**
     * بازی‌هایی که هنوز موتور ندارند (برای نمایش «به‌زودی» در UI).
     */
    public static function comingSoon(): array
    {
        return [
            ['key' => 'brawl_stars', 'label' => 'براول استارز', 'short' => 'براول استارز', 'game' => 'brawl_stars', 'icon' => '🌵', 'color' => 'yellow', 'result_type' => null, 'hint' => 'براول استارز لینک کپی یا چیدمان قابل اشتراک ندارد. تشخیص بیلد براولر (گجت/استارپاور/گیر) از روی عکس به‌زودی اضافه می‌شود.', 'coming_soon' => true],
            ['key' => 'boom_beach', 'label' => 'بوم بیچ', 'short' => 'بوم بیچ', 'game' => 'boom_beach', 'icon' => '🏝️', 'color' => 'teal', 'result_type' => null, 'hint' => 'بازسازی چیدمان بیس بوم بیچ به‌زودی.', 'coming_soon' => true],
            ['key' => 'squad_busters', 'label' => 'اسکواد باسترز', 'short' => 'اسکواد باسترز', 'game' => 'squad_busters', 'icon' => '⚡', 'color' => 'purple', 'result_type' => null, 'hint' => 'اسکواد باسترز چیدمان یا دک قابل کپی ندارد.', 'coming_soon' => true],
        ];
    }

    /**
     * متادیتای همهٔ موتورها برای UI (فعال‌ها + به‌زودی‌ها).
     */
    public function metaForUi(): array
    {
        $active = array_map(fn (GameAdapter $a) => $a->meta() + ['coming_soon' => false, 'configured' => $a->isConfigured()], $this->all());

        return array_merge($active, static::comingSoon());
    }
}
