<?php

namespace App\Services\BaseClone;

use App\Models\BaseClone;
use App\Models\User;
use App\Services\BaseClone\Games\GameAdapter;
use App\Services\BaseClone\Games\GameRegistry;
use App\Services\BaseClone\Games\LayoutGameAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * موتور چندبازیِ «بازسازی از روی تصویر»:
 *  1. هش ادراکی تصویر (برای تطبیق با آرشیو نقشه‌ها)
 *  2. واگذاری تحلیل به آداپتور بازی انتخاب‌شده (کلش آف کلنز، کلش رویال، ...)
 *  3. ذخیرهٔ نتیجه و ساخت لینک اشتراک‌گذاری
 */
class BaseCloneService
{
    public function __construct(
        protected GameRegistry $games,
        protected ImageHasher $hasher,
        protected LayoutMatcher $matcher,
    ) {
    }

    public function games(): GameRegistry
    {
        return $this->games;
    }

    public function isConfigured(?string $game = null): bool
    {
        $adapter = $game && $this->games->has($game) ? $this->games->get($game) : $this->games->default();

        return $adapter->isConfigured();
    }

    /**
     * @return array{ok: bool, clone?: BaseClone, matches: array, message?: string}
     */
    public function cloneFromUpload(User $user, UploadedFile $image, ?string $title = null, string $game = 'coc_home'): array
    {
        $adapter = $this->games->get($game);

        $path = $image->getRealPath();
        $hash = $path ? $this->hasher->hashFile($path) : null;

        // ۱) اول تطبیق با آرشیو (چند میلی‌ثانیه، بدون Vision): اگر همین بیس در آرشیو باشد،
        //    لینک واقعی بازی همان لحظه برمی‌گردد و بازسازی با AI اختیاری می‌شود.
        if ($hash !== null && $adapter instanceof LayoutGameAdapter) {
            $matches = $this->matcher->findMatches($hash);
            $best = $matches[0] ?? null;

            if ($best && ! empty($best['confident'])) {
                $th = self::thFromLink($best['copy_link'] ?? null);
                $layout = $this->pendingLayout($adapter, $best, $th);
                $storedPath = $image->store('base-clones', 'public');

                $clone = $user->baseClones()->create([
                    'slug' => $this->uniqueSlug(),
                    'game' => $adapter->key(),
                    'title' => $title ?: ($th ? "بیس تاون‌هال {$th} — یافت‌شده در آرشیو" : 'بیس یافت‌شده در آرشیو'),
                    'image_path' => $storedPath,
                    'th_level' => $th,
                    'image_hash' => $hash,
                    'layout' => $layout,
                    'copy_link' => $best['copy_link'],
                    'matched_map_id' => $best['id'],
                    'match_distance' => $best['distance'] ?? null,
                ]);

                return [
                    'ok' => true,
                    'clone' => $clone->load('matchedMap'),
                    'matches' => $matches,
                    'matched_first' => true,
                ];
            }
        }

        // ۲) در غیر این صورت: Vision → شبکه → تطبیق (هش + امضای چیدمان)
        $result = $adapter->analyze($image, $hash);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'خطا در تحلیل تصویر.',
                'reason' => $result['reason'] ?? 'unknown',
                'matches' => $result['matches'] ?? [],
            ];
        }

        $storedPath = $image->store('base-clones', 'public');

        $clone = $user->baseClones()->create([
            'slug' => $this->uniqueSlug(),
            'game' => $adapter->key(),
            'title' => $title ?: $this->defaultTitle($adapter, $result['layout']),
            'image_path' => $storedPath,
            'th_level' => $result['th_level'] ?? null,
            'image_hash' => $hash,
            'layout' => $result['layout'],
            'copy_link' => $result['copy_link'] ?? null,
            'matched_map_id' => $result['matched_map_id'] ?? null,
            'match_distance' => $result['match_distance'] ?? null,
        ]);

        return [
            'ok' => true,
            'clone' => $clone->load('matchedMap'),
            'matches' => $result['matches'] ?? [],
            'matched_first' => false,
        ];
    }

    /**
     * بازسازی چیدمان با AI برای بیسی که اول از آرشیو پیدا شده (یا هر بیس چیدمانی).
     *
     * @return array{ok: bool, clone?: BaseClone, matches?: array, message?: string, reason?: string}
     */
    public function reconstruct(BaseClone $clone): array
    {
        $adapter = $this->games->has($clone->game) ? $this->games->get($clone->game) : null;
        if (! $adapter instanceof LayoutGameAdapter) {
            return ['ok' => false, 'message' => 'این بازی چیدمان قابل بازسازی ندارد.', 'reason' => 'unsupported'];
        }

        $absolute = Storage::disk('public')->path($clone->image_path);
        if (! is_file($absolute)) {
            return ['ok' => false, 'message' => 'تصویر اصلی این بیس در دسترس نیست.', 'reason' => 'missing_image'];
        }

        $file = new UploadedFile($absolute, basename($absolute), null, null, true);
        $result = $adapter->analyze($file, $clone->image_hash);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'خطا در بازسازی چیدمان.',
                'reason' => $result['reason'] ?? 'unknown',
                'matches' => $result['matches'] ?? [],
            ];
        }

        $layout = $result['layout'];
        $layout['pending'] = false;
        if ($clone->matched_map_id && empty($layout['match'])) {
            $layout['match'] = $clone->layout['match'] ?? null;
        }

        $clone->update([
            'layout' => $layout,
            'th_level' => $result['th_level'] ?? $clone->th_level,
            'copy_link' => $clone->copy_link ?: ($result['copy_link'] ?? null),
            'matched_map_id' => $clone->matched_map_id ?: ($result['matched_map_id'] ?? null),
            'match_distance' => $clone->match_distance ?? ($result['match_distance'] ?? null),
        ]);

        return ['ok' => true, 'clone' => $clone->fresh('matchedMap'), 'matches' => $result['matches'] ?? []];
    }

    /** چیدمان موقت برای بیسی که از آرشیو پیدا شده و هنوز با AI بازسازی نشده. */
    protected function pendingLayout(LayoutGameAdapter $adapter, array $best, ?int $th): array
    {
        return [
            'type' => 'layout',
            'village' => $adapter->catalog()->key(),
            'grid_size' => $adapter->gridSize(),
            'version' => 2,
            'source' => 'archive',
            'pending' => true,
            'th_level' => $th,
            'buildings' => [],
            'walls' => [],
            'stats' => ['building_count' => 0, 'placed_count' => 0, 'unplaced_count' => 0, 'wall_count' => 0, 'uncertain_count' => 0, 'by_category' => [], 'by_type' => []],
            'match' => [
                'map_id' => $best['id'],
                'similarity' => $best['similarity'] ?? null,
                'method' => $best['method'] ?? 'hash',
                'signature_score' => $best['signature_score'] ?? null,
                'distance' => $best['distance'] ?? null,
                'confident' => true,
            ],
        ];
    }

    /** سطح تاون‌هال از پیشوند لینک بازی (TH15:WB:…). */
    public static function thFromLink(?string $link): ?int
    {
        return $link && preg_match('/id=TH(\d{1,2})(?:%3A|:)/i', $link, $m) ? (int) $m[1] : null;
    }

    protected function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(12));
        } while (BaseClone::where('slug', $slug)->exists());

        return $slug;
    }

    protected function defaultTitle(GameAdapter $adapter, array $layout): string
    {
        if (($layout['type'] ?? 'layout') === 'deck') {
            $avg = $layout['avg_elixir'] ?? null;
            $count = $layout['stats']['card_count'] ?? 0;

            return $avg !== null
                ? "دک کلش رویال ({$count} کارت، میانگین اکسیر {$avg})"
                : "دک کلش رویال ({$count} کارت)";
        }

        $hall = $adapter->key() === 'coc_builder' ? 'بیلدر هال' : 'تاون‌هال';
        $th = $layout['th_level'] ?? null;
        $count = $layout['stats']['placed_count'] ?? 0;

        return $th
            ? "بیس {$hall} {$th} (بازسازی از تصویر، {$count} ساختمان)"
            : "بیس {$adapter->meta()['short']} بازسازی‌شده از تصویر ({$count} ساختمان)";
    }
}
