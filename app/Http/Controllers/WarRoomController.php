<?php

namespace App\Http\Controllers;

use App\Services\WarRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarRoomController extends Controller
{
    public function __construct(protected WarRoomService $warRoomService)
    {
    }

    /**
     * دریافت وضعیت زنده نقشه اتاق جنگ.
     */
    public function state(Request $request): JsonResponse
    {
        $clanTag = $request->query('clan_tag');

        if (empty($clanTag)) {
            $user = $request->user();
            $clanTag = $user->gameProfile?->game_data['clan']['tag'] ?? 'DEMO_CLAN';
        }

        $totalTargets = (int) $request->query('total_targets', 15);
        $totalTargets = max(5, min(50, $totalTargets));

        $state = $this->warRoomService->getWarMapState($clanTag, $totalTargets);

        return response()->json($state);
    }

    /**
     * رزرو / کال کردن هدف.
     */
    public function call(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clan_tag' => ['nullable', 'string', 'max:20'],
            'target_number' => ['required', 'integer', 'min:1', 'max:50'],
            'target_th_level' => ['nullable', 'integer', 'min:1', 'max:17'],
            'target_player_name' => ['nullable', 'string', 'max:100'],
            'target_player_tag' => ['nullable', 'string', 'max:20'],
            'tactical_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        if (empty($validated['clan_tag'])) {
            $validated['clan_tag'] = $user->gameProfile?->game_data['clan']['tag'] ?? 'DEMO_CLAN';
        }

        $result = $this->warRoomService->callTarget($user, $validated);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * ثبت ستاره و درصد تخریب حمله.
     */
    public function recordResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => ['required', 'integer'],
            'stars' => ['required', 'integer', 'min:0', 'max:3'],
            'percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $user = $request->user();
        $result = $this->warRoomService->recordAttackResult(
            $user,
            $validated['call_id'],
            $validated['stars'],
            $validated['percent']
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * لغو رزرو هدف.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $result = $this->warRoomService->cancelCall($user, $id);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * تخمین ۳ ستاره هوش مصنوعی برای مصاف دو تاون‌هال.
     */
    public function estimate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attacker_th' => ['required', 'integer', 'min:1', 'max:17'],
            'defender_th' => ['required', 'integer', 'min:1', 'max:17'],
        ]);

        $user = $request->user();
        $gameData = $user->gameProfile?->game_data ?? [];

        $estimation = $this->warRoomService->calculateMatchupEstimation(
            $validated['attacker_th'],
            $validated['defender_th'],
            $gameData
        );

        return response()->json([
            'ok' => true,
            'estimation' => $estimation,
        ]);
    }
}
