<?php

namespace App\Http\Controllers;

use App\Jobs\CrawlClasherMaps;
use App\Models\Map;
use App\Services\MapCrawlerService;
use Illuminate\Http\Request;

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
            $user->favoriteMaps()->attach($map->id);
            $isFavorite = true;
        }

        return response()->json([
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'به علاقه‌مندی‌ها اضافه شد.' : 'از علاقه‌مندی‌ها حذف شد.',
        ]);
    }

    /**
     * List user's favorite maps.
     */
    public function favorites(Request $request)
    {
        $maps = $request->user()->favoriteMaps()
            ->orderByDesc('map_favorites.created_at')
            ->paginate(12);

        return response()->json($maps);
    }
}
