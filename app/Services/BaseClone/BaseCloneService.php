<?php

namespace App\Services\BaseClone;

use App\Models\BaseClone;
use App\Models\User;
use App\Services\BaseClone\Games\GameAdapter;
use App\Services\BaseClone\Games\GameRegistry;
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

        $result = $adapter->analyze($image, $hash);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'خطا در تحلیل تصویر.',
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
        ];
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
