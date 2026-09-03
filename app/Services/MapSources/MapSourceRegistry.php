<?php

namespace App\Services\MapSources;

class MapSourceRegistry
{
    /** @var array<string, MapSourceAdapter> */
    protected array $sources = [];

    public function __construct(ClasherSource $clasher)
    {
        foreach ([$clasher] as $source) {
            $this->sources[$source->key()] = $source;
        }
    }

    public function register(MapSourceAdapter $source): void
    {
        $this->sources[$source->key()] = $source;
    }

    public function has(string $key): bool
    {
        return isset($this->sources[$key]);
    }

    public function get(string $key): MapSourceAdapter
    {
        if (! $this->has($key)) {
            throw new \InvalidArgumentException("منبع ناشناخته: {$key} (منابع موجود: ".implode(', ', $this->keys()).')');
        }

        return $this->sources[$key];
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->sources);
    }

    /** @return array<int, MapSourceAdapter> */
    public function all(): array
    {
        return array_values($this->sources);
    }
}
