<?php

namespace App\Http\Controllers;

use App\Services\AI\AttackSimulatorService;
use App\Services\AI\DefenseVulnerabilityScanner;
use Illuminate\Http\Request;

class AiTacticalController extends Controller
{
    public function __construct(
        protected AttackSimulatorService $attackSimulator,
        protected DefenseVulnerabilityScanner $defenseScanner,
        protected \App\Services\AI\CwlManagerService $cwlManager,
        protected \App\Services\AI\LiveAttackCompanionService $liveAttack
    ) {
    }

    /**
     * تولید پلن زنده و دستورالعمل ثانیه‌ای اتک روی موبایل (Live Attack In-Game HUD)
     */
    public function liveAttackPlan(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'کاربر احراز هویت نشده است.'], 401);
        }

        $scoutData = $request->all();
        $result = $this->liveAttack->generateLiveHudPlan($user, $scoutData);

        return response()->json($result);
    }

    /**
     * تحلیل و استراتژی وارلیگ CWL
     */
    public function analyzeCwl(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'کاربر احراز هویت نشده است.'], 401);
        }

        $league = $request->input('league', 'masters_1');
        $rosterSize = (int) $request->input('roster_size', 15);

        $result = $this->cwlManager->analyzeCwl($user, $league, $rosterSize);

        return response()->json($result);
    }

    /**
     * شبیه‌سازی و تولید بلوپرینت ۳ مرحله‌ای اتک وار با هوش مصنوعی
     */
    public function simulateAttack(Request $request)
    {
        $request->validate([
            'target_town_hall' => 'required|integer|min:9|max:18',
            'army_type' => 'nullable|string',
            'base_archetype' => 'nullable|string',
        ]);

        $user = auth()->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'لطفاً وارد حساب کاربری خود شوید.'], 401);
        }

        $targetTh = (int) $request->input('target_town_hall', 15);
        $armyType = $request->input('army_type', 'root_rider_smash');
        $baseType = $request->input('base_archetype', 'box_base');

        $result = $this->attackSimulator->generateTacticalBlueprint($user, $targetTh, $armyType, $baseType);

        return response()->json($result);
    }

    /**
     * اسکن جامع آسیب‌پذیری‌های دفاعی پایگاه
     */
    public function scanDefense()
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'کاربر احراز هویت نشده است.'], 401);
        }

        $result = $this->defenseScanner->scan($user);

        return response()->json($result);
    }
}
