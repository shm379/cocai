<?php

namespace App\Services\BaseClone;

/**
 * کاتالوگ کارت‌های کلش رویال (شناسهٔ رسمی، اکسیر، نوع) + تطبیق نام تحمل‌پذیر.
 *
 * منبع: database/data/cr/cards.json (قابل به‌روزرسانی با `php artisan cr:cards`).
 * شناسه‌ها همان‌هایی هستند که لینک رسمی «کپی دک» بازی استفاده می‌کند.
 */
class CardCatalog
{
    protected const ALIASES = [
        'pekka' => 'pekka', 'minipekka' => 'mini-pekka', 'mk' => 'mega-knight', 'megaknight' => 'mega-knight',
        'ewiz' => 'electro-wizard', 'ewizard' => 'electro-wizard', 'edrag' => 'electro-dragon', 'egiant' => 'electro-giant',
        'espirit' => 'electro-spirit', 'log' => 'the-log', 'snowball' => 'giant-snowball', 'rg' => 'royal-giant',
        'mm' => 'mega-minion', 'gg' => 'goblin-gang', '3m' => 'three-musketeers', 'threemusketeer' => 'three-musketeers',
        'ebarbs' => 'elite-barbarians', 'elitebarbs' => 'elite-barbarians', 'ig' => 'ice-golem', 'bd' => 'baby-dragon',
        'babydrag' => 'baby-dragon', 'id' => 'inferno-dragon', 'it' => 'inferno-tower', 'wb' => 'wall-breakers',
        'wallbreaker' => 'wall-breakers', 'sk' => 'skeleton-king', 'aq' => 'archer-queen', 'gk' => 'golden-knight',
        'lp' => 'little-prince', 'barbbarrel' => 'barbarian-barrel', 'hog' => 'hog-rider', 'lumber' => 'lumberjack',
        'valk' => 'valkyrie', 'musk' => 'musketeer', 'nw' => 'night-witch', 'mw' => 'mother-witch',
        'skarmy' => 'skeleton-army', 'skellyarmy' => 'skeleton-army', 'ghost' => 'royal-ghost', 'ram' => 'battle-ram',
        'ramrider' => 'ram-rider', 'xbow' => 'x-bow', 'crossbow' => 'x-bow', 'pump' => 'elixir-collector',
        'collector' => 'elixir-collector', 'drill' => 'goblin-drill', 'fb' => 'fireball', 'nado' => 'tornado',
        'eq' => 'earthquake', 'quake' => 'earthquake', 'gy' => 'graveyard', 'rd' => 'royal-delivery', 'curse' => 'goblin-curse',
        'fc' => 'firecracker', 'dg' => 'dart-goblin', 'marcher' => 'magic-archer', 'ma' => 'magic-archer',
        'exe' => 'executioner', 'lava' => 'lava-hound', 'lh' => 'lava-hound', 'loon' => 'balloon',
        'egolem' => 'elixir-golem', 'gs' => 'giant-skeleton', 'rh' => 'royal-hogs', 'rr' => 'royal-recruits',
        'horde' => 'minion-horde', 'skellydragons' => 'skeleton-dragons', 'skeletondragon' => 'skeleton-dragons',
        'wiz' => 'wizard', 'iwiz' => 'ice-wizard', 'skellies' => 'skeletons', 'skeleton' => 'skeletons',
        'archer' => 'archers', 'goblin' => 'goblins', 'barbs' => 'barbarians', 'barbarian' => 'barbarians',
        'minion' => 'minions', 'dp' => 'dark-prince', 'bush' => 'suspicious-bush', 'demolisher' => 'goblin-demolisher',
        'rascal' => 'rascals', 'bat' => 'bats', 'guard' => 'guards', 'zappy' => 'zappies', 'goblinbarrel' => 'goblin-barrel',
        'tp' => 'tower-princess', 'princesstower' => 'tower-princess', 'duchess' => 'dagger-duchess', 'chef' => 'royal-chef',
    ];

    /** @var array<int, array{key:string,id:int,name:string,elixir:int,rarity:string,type:string,verified:bool}> */
    protected array $cards;

    /** @var array<string, int> normalized name/key → index */
    protected array $index = [];

    public function __construct(?string $path = null)
    {
        $path ??= database_path('data/cr/cards.json');
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $this->cards = array_values(array_filter($data['cards'] ?? [], fn ($c) => isset($c['id'], $c['name'])));

        foreach ($this->cards as $i => $card) {
            $this->index[self::normalize($card['name'])] = $i;
            $this->index[self::normalize($card['key'] ?? '')] = $i;
        }
    }

    public function all(): array
    {
        return $this->cards;
    }

    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * یافتن کارت از روی نام (تحمل خطای املایی، نام مستعار، پیشوند Evolved).
     *
     * @return array{card: array, evolution: bool}|null
     */
    public function find(?string $name): ?array
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $evolution = false;
        $clean = trim($name);
        if (preg_match('/\b(evolved|evolution|evo)\b/i', $clean)) {
            $evolution = true;
            $clean = preg_replace('/\b(evolved|evolution|evo)\b/i', ' ', $clean) ?? $clean;
        }

        $norm = self::normalize($clean);
        if ($norm === '') {
            return null;
        }

        $card = $this->lookup($norm)
            ?? $this->lookup(preg_replace('/^the/', '', $norm) ?? $norm)
            ?? $this->lookup(self::normalize(self::ALIASES[$norm] ?? ''))
            ?? $this->fuzzy($norm);

        return $card ? ['card' => $card, 'evolution' => $evolution] : null;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->cards as $card) {
            if ((int) $card['id'] === $id) {
                return $card;
            }
        }

        return null;
    }

    protected function lookup(string $norm): ?array
    {
        if ($norm === '') {
            return null;
        }

        return isset($this->index[$norm]) ? $this->cards[$this->index[$norm]] : null;
    }

    protected function fuzzy(string $norm): ?array
    {
        if (strlen($norm) < 4) {
            return null;
        }

        $best = null;
        $bestDistance = PHP_INT_MAX;
        foreach ($this->index as $key => $i) {
            if ($key === '' || abs(strlen($key) - strlen($norm)) > 2) {
                continue;
            }
            $d = levenshtein($norm, $key);
            if ($d < $bestDistance) {
                $bestDistance = $d;
                $best = $this->cards[$i];
            }
        }

        return $bestDistance <= 2 ? $best : null;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace('&', 'and', $value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
