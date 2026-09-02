<?php

namespace App\Http\Controllers;

use App\Models\BaseClone;
use App\Services\BaseClone\BaseCloneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * بازسازی بیس از روی تصویر (Base Cloner) و صفحهٔ عمومی اشتراک‌گذاری.
 */
class BaseCloneController extends Controller
{
    public function __construct(protected BaseCloneService $service)
    {
    }

    /**
     * فهرست بیس‌های بازسازی‌شدهٔ کاربر.
     */
    public function index(Request $request)
    {
        $clones = $request->user()
            ->baseClones()
            ->with('matchedMap')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'ok' => true,
            'clones' => $clones->map(fn (BaseClone $c) => $c->toPublicArray())->values(),
        ]);
    }

    /**
     * فهرست موتورهای بازی (فعال و به‌زودی) برای UI.
     */
    public function games()
    {
        return response()->json([
            'ok' => true,
            'games' => $this->service->games()->metaForUi(),
        ]);
    }

    /**
     * آپلود تصویر، بازسازی چیدمان/دک و ساخت لینک اشتراک‌گذاری.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:8192'],
            'title' => ['nullable', 'string', 'max:120'],
            'game' => ['nullable', 'string', Rule::in($this->service->games()->keys())],
        ]);

        $game = $validated['game'] ?? 'coc_home';

        if (! $this->service->isConfigured($game)) {
            return response()->json([
                'ok' => false,
                'message' => 'AI Vision پیکربندی نشده است.',
            ], 503);
        }

        $result = $this->service->cloneFromUpload(
            $request->user(),
            $validated['image'],
            $validated['title'] ?? null,
            $game,
        );

        if (! $result['ok']) {
            // خطای زیرساخت (gateway/توکن/مدل) 503 است تا از خطای تصویر کاربر جدا شود.
            $infra = in_array($result['reason'] ?? '', ['connection', 'auth', 'model', 'server', 'timeout'], true);

            return response()->json([
                'ok' => false,
                'message' => $result['message'],
                'reason' => $result['reason'] ?? 'unknown',
                'matches' => $result['matches'],
            ], $infra ? 503 : 422);
        }

        return response()->json([
            'ok' => true,
            'clone' => $result['clone']->toPublicArray(),
            'matches' => $result['matches'],
        ], 201);
    }

    /**
     * صفحهٔ عمومی بیس بازسازی‌شده (بدون نیاز به ورود).
     */
    public function show(Request $request, BaseClone $clone)
    {
        $clone->load('matchedMap');

        $isOwner = $request->user()?->id === $clone->user_id;
        if (! $isOwner) {
            $clone->increment('view_count');
        }

        return Inertia::render('BaseClone/Show', [
            'clone' => $clone->toPublicArray(),
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * حذف یک بیس بازسازی‌شده.
     */
    public function destroy(Request $request, BaseClone $clone)
    {
        if ($clone->user_id !== $request->user()->id) {
            return response()->json([
                'ok' => false,
                'message' => 'شما اجازهٔ حذف این بیس را ندارید.',
            ], 403);
        }

        Storage::disk('public')->delete($clone->image_path);
        $clone->delete();

        return response()->json(['ok' => true, 'message' => 'بیس حذف شد.']);
    }
}
