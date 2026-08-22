<?php

namespace App\Http\Controllers;

use App\Jobs\CrawlClasherMaps;
use App\Models\Map;
use App\Services\MapCrawlerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MapController extends Controller
{
    public function __construct(protected MapCrawlerService $crawler)
    {
    }

    public function crawlMaps(Request $request)
    {
        if ($request->boolean('sync')) {
            $result = $this->crawler->crawlAll();

            return response()->json($result);
        }

        CrawlClasherMaps::dispatch();

        return response()->json([
            'ok' => true,
            'message' => 'کراول نقشه‌ها در صف پردازش قرار گرفت. پس از چند دقیقه نقشه‌ها به‌روز می‌شوند.',
        ]);
    }

    /**
     * Toggle favorite status for a map.
     */
    public function toggleFavorite(Request $request, Map $map)
    {
        $user = $request->user();

        if ($user->favoriteMaps()->where('maps.id', $map->id)->exists()) {
            $user->favoriteMaps()->detach($map->id);
            $isFavorite = false;
        } else {
            $user->favoriteMaps()->attach($map->id, $this->favoritePayload($request));
            $isFavorite = true;
        }

        return response()->json([
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'به علاقه‌مندی‌ها اضافه شد.' : 'از علاقه‌مندی‌ها حذف شد.',
        ]);
    }

    /**
     * Update notes and tags on an existing favorite.
     */
    public function updateFavorite(Request $request, Map $map)
    {
        $user = $request->user();

        $favorite = $user->favoriteMaps()
            ->where('maps.id', $map->id)
            ->first();

        if (! $favorite) {
            return response()->json([
                'ok' => false,
                'message' => 'این نقشه در علاقه‌مندی‌های شما وجود ندارد.',
            ], 404);
        }

        $payload = $this->favoritePayload($request);
        $user->favoriteMaps()->updateExistingPivot($map->id, $payload);

        return response()->json([
            'ok' => true,
            'notes' => $payload['notes'] ?? null,
            'tags' => $payload['tags'] ?? [],
            'message' => 'یادداشت و برچسب‌ها به‌روز شد.',
        ]);
    }

    /**
     * List user's favorite maps with optional tag filter.
     */
    public function favorites(Request $request)
    {
        $query = $request->user()->favoriteMaps()
            ->withPivot(['notes', 'tags'])
            ->orderByDesc('map_favorites.created_at');

        if ($request->filled('tag')) {
            $tag = trim($request->query('tag'));
            $query->whereJsonContains('map_favorites.tags', $tag);
        }

        $maps = $query->paginate(12);

        return response()->json($maps);
    }

    /**
     * Build the pivot payload for notes/tags.
     */
    private function favoritePayload(Request $request): array
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:30'],
        ]);

        $tags = $validated['tags'] ?? [];
        $tags = array_values(array_unique(array_filter(array_map('trim', $tags))));

        return [
            'notes' => $validated['notes'] ?? null,
            'tags' => $tags ?: null,
        ];
    }
}
