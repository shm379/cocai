<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBaseCloneLayoutRequest;
use App\Models\BaseClone;
use App\Services\BaseClone\BaseCloneService;
use App\Services\BaseClone\Games\LayoutGameAdapter;
use App\Services\BaseClone\LayoutEditValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'clones' => $clones->map(fn (BaseClone $c) => $c->toPublicArray(true))->values(),
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
            'clone' => $result['clone']->toPublicArray(true),
            'matches' => $result['matches'],
            'matched_first' => (bool) ($result['matched_first'] ?? false),
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
            'clone' => $clone->toPublicArray($isOwner),
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * بازسازی چیدمان با AI برای بیسی که اول از آرشیو پیدا شده (فقط مالک).
     */
    public function reconstruct(Request $request, BaseClone $clone)
    {
        if ($clone->user_id !== $request->user()->id) {
            return response()->json(['ok' => false, 'message' => 'شما اجازهٔ این کار را ندارید.'], 403);
        }

        if (! $this->service->isConfigured($clone->game)) {
            return response()->json(['ok' => false, 'message' => 'AI Vision پیکربندی نشده است.', 'reason' => 'connection'], 503);
        }

        $result = $this->service->reconstruct($clone);

        if (! $result['ok']) {
            $infra = in_array($result['reason'] ?? '', ['connection', 'auth', 'model', 'server', 'timeout'], true);

            return response()->json([
                'ok' => false,
                'message' => $result['message'],
                'reason' => $result['reason'] ?? 'unknown',
                'matches' => $result['matches'] ?? [],
            ], $infra ? 503 : 422);
        }

        return response()->json([
            'ok' => true,
            'clone' => $result['clone']->toPublicArray(true),
            'matches' => $result['matches'],
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

    /**
     * کاتالوگ ساختمان‌های یک بازی چیدمان‌محور برای ویرایشگر (ابعاد/برچسب/رنگ/آیکون/اسپرایت).
     */
    public function catalog(Request $request)
    {
        $game = (string) $request->query('game', 'coc_home');
        $registry = $this->service->games();

        if (! $registry->has($game)) {
            return response()->json([
                'ok' => false,
                'message' => 'بازی ناشناخته است.',
            ], 422);
        }

        $adapter = $registry->get($game);
        if (! $adapter instanceof LayoutGameAdapter) {
            return response()->json([
                'ok' => false,
                'message' => 'این بازی چیدمان قابل ویرایش ندارد.',
            ], 422);
        }

        $catalog = $adapter->catalog();
        $types = [];
        // all() فقط ثابت ITEMS را برمی‌گرداند؛ مسیر اسپرایت را get() از مانیفست اضافه می‌کند.
        foreach (array_keys($catalog->all()) as $type) {
            $meta = $catalog->get($type);
            $types[$type] = [
                'size' => (int) $meta['size'],
                'label' => $meta['label'],
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'category' => $meta['category'],
                'sprite' => $meta['sprite'] ?? null,
            ];
        }

        return response()->json([
            'ok' => true,
            'game' => $game,
            'village' => $catalog->key(),
            'grid_size' => $adapter->gridSize(),
            'types' => $types,
            'wall_sprites' => $catalog->wallSprites(),
            'ground_sprite' => $catalog->groundSprite(),
            'limits' => [
                'buildings' => LayoutEditValidator::MAX_BUILDINGS,
                'walls' => LayoutEditValidator::MAX_WALLS,
            ],
        ]);
    }

    /**
     * نسخهٔ JSON یک بیس برای مالک (بارگذاری مجدد ویرایشگر پس از تعارض نسخه).
     */
    public function showJson(Request $request, BaseClone $clone)
    {
        if ($clone->user_id !== $request->user()->id) {
            return response()->json([
                'ok' => false,
                'message' => 'شما به این بیس دسترسی ندارید.',
            ], 403);
        }

        $clone->load('matchedMap');

        return response()->json([
            'ok' => true,
            'clone' => $clone->toPublicArray(true),
        ]);
    }

    /**
     * ذخیرهٔ ویرایش دستی چیدمان (فقط مالک) با اعتبارسنجی سخت‌گیرانه و کنترل نسخهٔ خوش‌بینانه.
     */
    public function updateLayout(UpdateBaseCloneLayoutRequest $request, BaseClone $clone, LayoutEditValidator $validator)
    {
        $registry = $this->service->games();
        $game = $clone->game ?: 'coc_home';
        $adapter = $registry->has($game) ? $registry->get($game) : null;

        if (! $adapter instanceof LayoutGameAdapter || (($clone->layout['type'] ?? 'layout') !== 'layout')) {
            return response()->json([
                'ok' => false,
                'message' => 'این رکورد چیدمان قابل ویرایش ندارد.',
            ], 422);
        }

        $payload = $request->validated();
        $requestedVersion = (int) $payload['version'];

        $outcome = DB::transaction(function () use ($clone, $adapter, $validator, $payload, $requestedVersion) {
            /** @var BaseClone $fresh */
            $fresh = BaseClone::query()->whereKey($clone->getKey())->lockForUpdate()->firstOrFail();
            $layout = is_array($fresh->layout) ? $fresh->layout : [];
            $currentVersion = (int) ($layout['version'] ?? 1);

            if ($requestedVersion !== $currentVersion) {
                return ['status' => 'conflict', 'clone' => $fresh, 'current_version' => $currentVersion];
            }

            $result = $validator->validate($layout, $payload, $adapter->catalog(), $adapter->gridSize());
            if (! $result['ok']) {
                return ['status' => 'invalid', 'errors' => $result['errors']];
            }

            $data = ['layout' => $result['layout']];
            if (array_key_exists('title', $payload) && trim((string) $payload['title']) !== '') {
                $data['title'] = trim((string) $payload['title']);
            }
            $fresh->update($data);

            return ['status' => 'ok', 'clone' => $fresh];
        });

        if ($outcome['status'] === 'conflict') {
            $outcome['clone']->load('matchedMap');

            return response()->json([
                'ok' => false,
                'reason' => 'version',
                'message' => 'این چیدمان در جای دیگری تغییر کرده است؛ نسخهٔ جدید را بارگذاری کنید.',
                'current_version' => $outcome['current_version'],
                'clone' => $outcome['clone']->toPublicArray(true),
            ], 409);
        }

        if ($outcome['status'] === 'invalid') {
            return response()->json([
                'ok' => false,
                'reason' => 'layout',
                'message' => 'چیدمان ارسالی نامعتبر است؛ موارد مشخص‌شده را اصلاح کنید.',
                'errors' => $outcome['errors'],
            ], 422);
        }

        $outcome['clone']->load('matchedMap');

        return response()->json([
            'ok' => true,
            'message' => 'چیدمان ذخیره شد.',
            'clone' => $outcome['clone']->toPublicArray(true),
        ]);
    }
}
