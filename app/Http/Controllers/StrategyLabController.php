<?php

namespace App\Http\Controllers;

use App\Models\StrategyLabSession;
use App\Services\AI\BaseVisionAnalyzer;
use App\Services\StrategyLabAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StrategyLabController extends Controller
{
    public function __construct(
        private StrategyLabAnalyzer $analyzer,
        private BaseVisionAnalyzer $visionAnalyzer,
    ) {
    }

    /**
     * Render the strategy lab page.
     */
    public function index(Request $request)
    {
        $sessions = $request->user()
            ->strategyLabSessions()
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'image_path', 'created_at']);

        return Inertia::render('Dashboard/StrategyLabPage', [
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'image_url' => Storage::url($s->image_path),
                'created_at' => $s->created_at->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Store a new strategy-lab session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'image' => ['required', 'image', 'max:5120'],
            'buildings' => ['required', 'array'],
            'buildings.*.id' => ['required', 'integer'],
            'buildings.*.type' => ['required', 'string', 'max:60'],
            'buildings.*.x' => ['required', 'numeric'],
            'buildings.*.y' => ['required', 'numeric'],
        ]);

        $path = $request->file('image')->store('strategy-lab', 'public');

        $session = $request->user()->strategyLabSessions()->create([
            'title' => $validated['title'] ?? null,
            'image_path' => $path,
            'buildings' => $validated['buildings'],
        ]);

        return response()->json([
            'id' => $session->id,
            'title' => $session->title,
            'image_url' => Storage::url($session->image_path),
            'buildings' => $session->buildings,
            'created_at' => $session->created_at->toDateTimeString(),
        ], 201);
    }

    /**
     * Show a single session.
     */
    public function show(Request $request, StrategyLabSession $session)
    {
        $this->authorizeAccess($request, $session);

        return response()->json([
            'id' => $session->id,
            'title' => $session->title,
            'image_url' => Storage::url($session->image_path),
            'buildings' => $session->buildings,
            'analysis' => $session->analysis,
            'created_at' => $session->created_at->toDateTimeString(),
        ]);
    }

    /**
     * Analyze a session and cache the result.
     */
    public function analyze(Request $request, StrategyLabSession $session)
    {
        $this->authorizeAccess($request, $session);

        $analysis = $this->analyzer->analyze($session->buildings ?? []);
        $session->update(['analysis' => $analysis]);

        return response()->json($analysis);
    }

    /**
     * Delete a session.
     */
    public function destroy(Request $request, StrategyLabSession $session)
    {
        $this->authorizeAccess($request, $session);

        Storage::disk('public')->delete($session->image_path);
        $session->delete();

        return response()->json(['message' => 'جلسه حذف شد.']);
    }

    /**
     * Run analysis on raw building data without storing a session.
     */
    public function quickAnalyze(Request $request)
    {
        $validated = $request->validate([
            'buildings' => ['required', 'array'],
            'buildings.*.id' => ['required', 'integer'],
            'buildings.*.type' => ['required', 'string', 'max:60'],
            'buildings.*.x' => ['required', 'numeric'],
            'buildings.*.y' => ['required', 'numeric'],
        ]);

        return response()->json(
            $this->analyzer->analyze($validated['buildings'])
        );
    }

    /**
     * Detect buildings from an uploaded base screenshot using AI Vision.
     */
    public function detectByVision(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        if (! $this->visionAnalyzer->isConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => 'AI Vision پیکربندی نشده است.',
            ], 503);
        }

        $result = $this->visionAnalyzer->detectBuildings($validated['image']);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'خطا در تحلیل تصویر.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'buildings' => $result['buildings'],
            'analysis' => $this->analyzer->analyze($result['buildings']),
        ]);
    }

    private function authorizeAccess(Request $request, StrategyLabSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'session' => ['شما اجازه دسترسی به این جلسه را ندارید.'],
            ]);
        }
    }
}
