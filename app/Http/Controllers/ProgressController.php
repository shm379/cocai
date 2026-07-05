<?php

namespace App\Http\Controllers;

use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgressController extends Controller
{
    public function __construct(protected ProgressionService $progression)
    {
    }

    /**
     * صفحهٔ تحلیل پیشرفت: هیروها، لَب، امتیاز راش، صف آپگرید، ارتش‌های متا.
     */
    public function show(Request $request)
    {
        return Inertia::render('Dashboard/ProgressPage', [
            'analysis' => $this->analysisFor($request->user()),
        ]);
    }

    public function api(Request $request)
    {
        return response()->json($this->analysisFor($request->user()));
    }

    private function analysisFor($user): array
    {
        $gameData = $user->gameProfile->game_data ?? [];

        if (empty($gameData)) {
            return ['ok' => false, 'reason' => 'no_profile'];
        }

        return $this->progression->analyze($gameData);
    }
}
