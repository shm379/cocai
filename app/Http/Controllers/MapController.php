<?php

namespace App\Http\Controllers;

use App\Services\MapCrawlerService;

class MapController extends Controller
{
    public function crawlMaps()
    {
        $crawlerService = new MapCrawlerService();
        $crawlerService->crawlMaps();

        return response()->json(['message' => 'نقشه‌ها با موفقیت کراول شدند!']);
    }
}
