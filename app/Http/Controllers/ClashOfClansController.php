<?php

namespace App\Http\Controllers;

use App\Services\ClashOfClansService;
use Illuminate\Http\Request;

class ClashOfClansController extends Controller
{
    protected $clashService;

    public function __construct(ClashOfClansService $clashService)
    {
        $this->clashService = $clashService;
    }

    public function getPlayer(Request $request)
    {
        $playerTag = $request->input('player_tag');

        try {
            $playerData = $this->clashService->getPlayerData($playerTag);
            return response()->json(['data' => $playerData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getClan(Request $request)
    {
        $clanTag = $request->input('clan_tag');

        try {
            $clanData = $this->clashService->getClanData($clanTag);
            return response()->json(['data' => $clanData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
