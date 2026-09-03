<?php

namespace App\Services\BaseClone;

/**
 * کاتالوگ ساختمان‌های دهکدهٔ اصلی (Home Village).
 *
 * ابعاد بر حسب خانهٔ شبکه (tile) است: تاون‌هال، ارتش‌کمپ و ایگل ۴×۴،
 * تسلا/اینفرنو/سوییپر/کلبهٔ بیلدر ۲×۲ و بقیه ۳×۳. دیوار ۱×۱ است.
 */
class BuildingCatalog
{
    public const WALL = 'wall';

    /** مسیر مانیفست اسپرایت‌ها (نسبت به ریشهٔ پروژه). */
    protected const SPRITE_MANIFEST = 'database/data/coc/sprites.json';

    /** @var array<string, mixed>|null مانیفست بارگذاری‌شده (یک‌بار برای همهٔ نمونه‌ها) */
    protected static ?array $spriteManifest = null;

    /** @var array<string, bool> کش وجود فایل اسپرایت روی دیسک */
    protected static array $spriteExists = [];

    /**
     * @var array<string, array{size:int,label:string,color:string,category:string,icon:string}>
     */
    protected const ITEMS = [
        // هسته و هیروها
        'town_hall' => ['size' => 4, 'label' => 'تاون‌هال', 'color' => '#f59e0b', 'category' => 'core', 'icon' => '🏰'],
        'clan_castle' => ['size' => 3, 'label' => 'کلن کستل', 'color' => '#a855f7', 'category' => 'core', 'icon' => '🏯'],
        'hero_hall' => ['size' => 4, 'label' => 'هیرو هال', 'color' => '#c084fc', 'category' => 'core', 'icon' => '🏛️'],
        'barbarian_king' => ['size' => 3, 'label' => 'بربرین کینگ', 'color' => '#eab308', 'category' => 'hero', 'icon' => '👑'],
        'archer_queen' => ['size' => 3, 'label' => 'آرچر کوئین', 'color' => '#ec4899', 'category' => 'hero', 'icon' => '🏹'],
        'grand_warden' => ['size' => 3, 'label' => 'گرند واردن', 'color' => '#22d3ee', 'category' => 'hero', 'icon' => '🧙'],
        'royal_champion' => ['size' => 3, 'label' => 'رویال چمپیون', 'color' => '#fb7185', 'category' => 'hero', 'icon' => '🛡️'],
        'minion_prince' => ['size' => 3, 'label' => 'مینیون پرینس', 'color' => '#818cf8', 'category' => 'hero', 'icon' => '🦇'],

        // دفاع‌ها
        'cannon' => ['size' => 3, 'label' => 'کنون', 'color' => '#6b7280', 'category' => 'defense', 'icon' => '💣'],
        'archer_tower' => ['size' => 3, 'label' => 'آرچر تاور', 'color' => '#84cc16', 'category' => 'defense', 'icon' => '🗼'],
        'mortar' => ['size' => 3, 'label' => 'مورتار', 'color' => '#57534e', 'category' => 'defense', 'icon' => '🎯'],
        'air_defense' => ['size' => 3, 'label' => 'ایر دیفنس', 'color' => '#ef4444', 'category' => 'defense', 'icon' => '🚀'],
        'wizard_tower' => ['size' => 3, 'label' => 'ویزارد تاور', 'color' => '#8b5cf6', 'category' => 'defense', 'icon' => '🔮'],
        'air_sweeper' => ['size' => 2, 'label' => 'ایر سوییپر', 'color' => '#38bdf8', 'category' => 'defense', 'icon' => '🌬️'],
        'hidden_tesla' => ['size' => 2, 'label' => 'تسلا', 'color' => '#facc15', 'category' => 'defense', 'icon' => '⚡'],
        'bomb_tower' => ['size' => 3, 'label' => 'بمب تاور', 'color' => '#7c2d12', 'category' => 'defense', 'icon' => '💥'],
        'x_bow' => ['size' => 3, 'label' => 'ایکس‌بو', 'color' => '#0ea5e9', 'category' => 'defense', 'icon' => '🏹'],
        'inferno_tower' => ['size' => 2, 'label' => 'اینفرنو', 'color' => '#f97316', 'category' => 'defense', 'icon' => '🔥'],
        'eagle_artillery' => ['size' => 4, 'label' => 'ایگل آرتیلری', 'color' => '#dc2626', 'category' => 'defense', 'icon' => '🦅'],
        'scattershot' => ['size' => 3, 'label' => 'اسکترشات', 'color' => '#b45309', 'category' => 'defense', 'icon' => '🪨'],
        'spell_tower' => ['size' => 3, 'label' => 'اسپل تاور', 'color' => '#a21caf', 'category' => 'defense', 'icon' => '✨'],
        'monolith' => ['size' => 3, 'label' => 'مونولیت', 'color' => '#1e293b', 'category' => 'defense', 'icon' => '🗿'],
        'multi_archer_tower' => ['size' => 3, 'label' => 'مولتی آرچر تاور', 'color' => '#65a30d', 'category' => 'defense', 'icon' => '🏹'],
        'ricochet_cannon' => ['size' => 3, 'label' => 'ریکوشت کنون', 'color' => '#4b5563', 'category' => 'defense', 'icon' => '💣'],
        'multi_gear_tower' => ['size' => 3, 'label' => 'مولتی گیر تاور', 'color' => '#71717a', 'category' => 'defense', 'icon' => '⚙️'],
        'firespitter' => ['size' => 3, 'label' => 'فایر اسپیتر', 'color' => '#ea580c', 'category' => 'defense', 'icon' => '🐉'],

        // منابع
        'gold_mine' => ['size' => 3, 'label' => 'معدن طلا', 'color' => '#fde047', 'category' => 'resource', 'icon' => '⛏️'],
        'elixir_collector' => ['size' => 3, 'label' => 'کلکتور الکسیر', 'color' => '#f0abfc', 'category' => 'resource', 'icon' => '🧪'],
        'dark_elixir_drill' => ['size' => 3, 'label' => 'دریل دارک', 'color' => '#4c1d95', 'category' => 'resource', 'icon' => '🛢️'],
        'gold_storage' => ['size' => 3, 'label' => 'انبار طلا', 'color' => '#ca8a04', 'category' => 'resource', 'icon' => '💰'],
        'elixir_storage' => ['size' => 3, 'label' => 'انبار الکسیر', 'color' => '#d946ef', 'category' => 'resource', 'icon' => '🧫'],
        'dark_elixir_storage' => ['size' => 3, 'label' => 'انبار دارک', 'color' => '#312e81', 'category' => 'resource', 'icon' => '🪣'],

        // ارتش
        'army_camp' => ['size' => 4, 'label' => 'ارتش‌کمپ', 'color' => '#16a34a', 'category' => 'army', 'icon' => '⛺'],
        'barracks' => ['size' => 3, 'label' => 'بارکس', 'color' => '#15803d', 'category' => 'army', 'icon' => '🏚️'],
        'dark_barracks' => ['size' => 3, 'label' => 'دارک بارکس', 'color' => '#166534', 'category' => 'army', 'icon' => '🏚️'],
        'laboratory' => ['size' => 3, 'label' => 'آزمایشگاه', 'color' => '#0d9488', 'category' => 'army', 'icon' => '⚗️'],
        'spell_factory' => ['size' => 3, 'label' => 'اسپل فکتوری', 'color' => '#7e22ce', 'category' => 'army', 'icon' => '🧿'],
        'dark_spell_factory' => ['size' => 3, 'label' => 'دارک اسپل فکتوری', 'color' => '#581c87', 'category' => 'army', 'icon' => '🧿'],
        'workshop' => ['size' => 3, 'label' => 'ورکشاپ', 'color' => '#78350f', 'category' => 'army', 'icon' => '🛠️'],
        'pet_house' => ['size' => 3, 'label' => 'پت هاوس', 'color' => '#65a30d', 'category' => 'army', 'icon' => '🐾'],
        'blacksmith' => ['size' => 3, 'label' => 'آهنگری', 'color' => '#334155', 'category' => 'army', 'icon' => '🔨'],

        // سایر
        'builder_hut' => ['size' => 2, 'label' => 'کلبهٔ بیلدر', 'color' => '#9ca3af', 'category' => 'other', 'icon' => '🏠'],
        'helper_hut' => ['size' => 2, 'label' => 'کلبهٔ هلپر', 'color' => '#a3a3a3', 'category' => 'other', 'icon' => '🏡'],
    ];

    /** @var array<string, string> */
    protected const ALIASES = [
        'townhall' => 'town_hall', 'th' => 'town_hall',
        'cc' => 'clan_castle', 'clancastle' => 'clan_castle',
        'king' => 'barbarian_king', 'queen' => 'archer_queen', 'warden' => 'grand_warden', 'champion' => 'royal_champion',
        'prince' => 'minion_prince',
        'x_bow_air' => 'x_bow', 'xbow' => 'x_bow', 'x-bow' => 'x_bow',
        'inferno_tower_single' => 'inferno_tower', 'inferno_tower_multi' => 'inferno_tower', 'inferno' => 'inferno_tower',
        'tesla' => 'hidden_tesla', 'eagle' => 'eagle_artillery', 'wizard' => 'wizard_tower', 'archer' => 'archer_tower',
        'sweeper' => 'air_sweeper', 'scatter' => 'scattershot', 'multi_archer' => 'multi_archer_tower',
        'multi_gear' => 'multi_gear_tower', 'ricochet' => 'ricochet_cannon',
        'gold_collector' => 'gold_mine', 'de_drill' => 'dark_elixir_drill', 'de_storage' => 'dark_elixir_storage',
        'siege_workshop' => 'workshop', 'lab' => 'laboratory', 'camp' => 'army_camp',
        'walls' => self::WALL, 'wall_segment' => self::WALL,
    ];

    /** شناسهٔ دهکده برای پرامپت و UI. */
    public function key(): string
    {
        return 'home';
    }

    /** نام دهکده در پرامپت مدل Vision. */
    public function villageLabel(): string
    {
        return 'Home Village';
    }

    /** ابعاد شبکهٔ قابل ساخت. */
    public function gridSize(): int
    {
        return 44;
    }

    public function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $key = strtolower(trim($type));
        $key = preg_replace('/[\s\-]+/', '_', $key) ?? $key;

        if (isset(static::ALIASES[$key])) {
            $key = static::ALIASES[$key];
        }

        if ($key === self::WALL || isset(static::ITEMS[$key])) {
            return $key;
        }

        return null;
    }

    public function has(string $type): bool
    {
        return isset(static::ITEMS[$type]);
    }

    public function size(string $type): int
    {
        return static::ITEMS[$type]['size'] ?? 1;
    }

    /**
     * @return array{size:int,label:string,color:string,category:string,icon:string,sprite:?string}
     */
    public function get(string $type): array
    {
        $meta = static::ITEMS[$type] ?? [
            'size' => 1,
            'label' => $type,
            'color' => '#6b7280',
            'category' => 'other',
            'icon' => '▪️',
        ];

        $meta['sprite'] = $this->spriteFor($type);

        return $meta;
    }

    /**
     * مسیر عمومی اسپرایت ساختمان (مثل /images/coc/buildings/home/cannon.png) یا null اگر فایل موجود نباشد.
     *
     * برای town_hall/builder_hall اگر $level داده شود و فایل همان سطح موجود باشد، همان برمی‌گردد؛
     * در غیر این صورت فایل پیش‌فرض نوع. دیوار (wall) به پست دیوار همان دهکده نگاشت می‌شود.
     */
    public function spriteFor(string $type, ?int $level = null): ?string
    {
        $entry = $this->spriteEntry($type);
        if ($entry === null) {
            return null;
        }

        if ($level !== null && isset($entry['levels'][(string) $level]['file'])) {
            $url = $this->spriteUrl($entry['levels'][(string) $level]['file']);
            if ($url !== null) {
                return $url;
            }
        }

        return isset($entry['file']) ? $this->spriteUrl((string) $entry['file']) : null;
    }

    /**
     * اسپرایت‌های دیوار این دهکده: ['post' => url|null, 'middle' => url|null].
     *
     * @return array{post:?string,middle:?string}
     */
    public function wallSprites(): array
    {
        $walls = static::spriteManifest()['walls'] ?? [];
        $key = $this->key();

        $post = isset($walls[$key]['file']) ? $this->spriteUrl((string) $walls[$key]['file']) : null;
        if ($post === null && isset($walls[$key.'_legacy']['file'])) {
            $post = $this->spriteUrl((string) $walls[$key.'_legacy']['file']);
        }
        $middle = isset($walls[$key.'_middle']['file']) ? $this->spriteUrl((string) $walls[$key.'_middle']['file']) : null;

        return ['post' => $post, 'middle' => $middle];
    }

    /** اسپرایت کاشی زمین (لوزی چمن) یا null. */
    public function groundSprite(): ?string
    {
        $ground = static::spriteManifest()['ground']['grass']['file'] ?? null;

        return $ground ? $this->spriteUrl((string) $ground) : null;
    }

    /** متن سلب مسئولیت Supercell از مانیفست (fa/en). */
    public function spriteAttribution(): array
    {
        return static::spriteManifest()['attribution'] ?? [];
    }

    /**
     * @return array<string, mixed>|null ورودی مانیفست برای این نوع در دهکدهٔ جاری
     */
    protected function spriteEntry(string $type): ?array
    {
        $manifest = static::spriteManifest();
        $village = $this->key();

        if ($type === self::WALL) {
            $walls = $manifest['walls'] ?? [];
            $entry = $walls[$village] ?? null;
            if (is_array($entry) && $this->spriteUrl((string) ($entry['file'] ?? '')) === null && isset($walls[$village.'_legacy'])) {
                $entry = $walls[$village.'_legacy'];
            }

            return is_array($entry) ? $entry : null;
        }

        $entry = $manifest[$village][$type] ?? null;

        return is_array($entry) ? $entry : null;
    }

    /** مسیر عمومی فایل اگر روی دیسک موجود باشد؛ در غیر این صورت null. */
    protected function spriteUrl(string $file): ?string
    {
        if ($file === '') {
            return null;
        }

        $publicPath = rtrim((string) (static::spriteManifest()['public_path'] ?? '/images/coc/buildings'), '/');
        $relative = 'public'.$publicPath.'/'.ltrim($file, '/');

        if (! array_key_exists($relative, static::$spriteExists)) {
            static::$spriteExists[$relative] = is_file(static::projectPath($relative));
        }

        return static::$spriteExists[$relative] ? $publicPath.'/'.ltrim($file, '/') : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function spriteManifest(): array
    {
        if (static::$spriteManifest === null) {
            $path = static::projectPath(self::SPRITE_MANIFEST);
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
            static::$spriteManifest = is_array($data) ? $data : [];
        }

        return static::$spriteManifest;
    }

    /** مسیر مطلق نسبت به ریشهٔ پروژه (بدون وابستگی به کانتینر لاراول). */
    protected static function projectPath(string $relative): string
    {
        $root = function_exists('app') && app()->bound('path.base') ? base_path() : dirname(__DIR__, 3);

        return $root.DIRECTORY_SEPARATOR.ltrim($relative, '/');
    }

    /**
     * @return array<string, array{size:int,label:string,color:string,category:string,icon:string}>
     */
    public function all(): array
    {
        return static::ITEMS;
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys(static::ITEMS);
    }

    /**
     * لیست typeها برای قرار گرفتن در پرامپت مدل Vision.
     */
    public function promptTypeList(): string
    {
        return implode(', ', $this->keys());
    }
}
