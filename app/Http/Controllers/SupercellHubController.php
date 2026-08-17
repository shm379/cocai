<?php

namespace App\Http\Controllers;

use App\Services\ClashOfClansService;
use App\Services\ProgressionService;
use App\Services\Supercell\BrawlStarsService;
use App\Services\Supercell\ClashRoyaleService;
use App\Services\Supercell\SquadBustersService;
use App\Services\Supercell\BoomBeachService;
use Illuminate\Http\Request;

class SupercellHubController extends Controller
{
    public function __construct(
        protected ClashOfClansService $cocService,
        protected ProgressionService $cocProgression,
        protected ClashRoyaleService $crService,
        protected BrawlStarsService $bsService,
        protected SquadBustersService $sbService,
        protected BoomBeachService $bbService,
    ) {
    }

    public function getProfile(Request $request)
    {
        $request->validate([
            'game' => ['required', 'string', 'in:coc,clash_royale,brawl_stars,squad_busters,boom_beach'],
            'player_tag' => ['required', 'string', 'max:20'],
        ]);

        $game = $request->game;
        $tag = $request->player_tag;

        try {
            return match ($game) {
                'coc' => response()->json([
                    'game' => 'coc',
                    'data' => $this->cocProgression->analyze($this->cocService->getPlayerData($tag)),
                ]),
                'clash_royale' => response()->json([
                    'game' => 'clash_royale',
                    'data' => $this->crService->analyze($this->crService->getPlayerData($tag)),
                ]),
                'brawl_stars' => response()->json([
                    'game' => 'brawl_stars',
                    'data' => $this->bsService->analyze($this->bsService->getPlayerData($tag)),
                ]),
                'squad_busters' => response()->json([
                    'game' => 'squad_busters',
                    'data' => $this->sbService->analyze($this->sbService->getPlayerData($tag)),
                ]),
                'boom_beach' => response()->json([
                    'game' => 'boom_beach',
                    'data' => $this->bbService->analyze($this->bbService->getPlayerData($tag)),
                ]),
            };
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
