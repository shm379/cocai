<?php

namespace App\Models;

use App\Services\BaseClone\Games\GameRegistry;
use App\Services\BaseClone\ImageHasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * بیس بازسازی‌شده از روی تصویر (Base Clone).
 *
 * هر رکورد شامل تصویر اصلی، چیدمان ۴۴×۴۴ استخراج‌شده و (در صورت وجود)
 * نقشهٔ منطبق در آرشیو است تا لینک کپی داخل بازی در دسترس باشد.
 */
class BaseClone extends Model
{
    use HasFactory;

    /** کلیدهای ریشهٔ چیدمان که فقط مالک می‌بیند (خروجی خام مدل، سنجهٔ کیفیت). */
    public const PRIVATE_LAYOUT_KEYS = ['raw', 'quality', 'debug', 'vision'];

    /** کلیدهای هر ساختمان که فقط مالک می‌بیند. */
    public const PRIVATE_BUILDING_KEYS = ['raw', 'crop', 'bbox'];

    protected $fillable = [
        'user_id',
        'slug',
        'game',
        'title',
        'image_path',
        'th_level',
        'image_hash',
        'layout',
        'copy_link',
        'matched_map_id',
        'match_distance',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'th_level' => 'integer',
            'match_distance' => 'integer',
            'view_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matchedMap()
    {
        return $this->belongsTo(Map::class, 'matched_map_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getShareUrlAttribute(): string
    {
        return route('base-clone.show', $this->slug);
    }

    /**
     * نمایش عمومی (بدون اطلاعات حساس کاربر).
     *
     * @param  bool  $forOwner  برای مالک، کلیدهای داخلی چیدمان (raw/quality) هم برگردانده می‌شود.
     */
    public function toPublicArray(bool $forOwner = false): array
    {
        $map = $this->matchedMap;
        $layout = $this->presentLayout($forOwner);
        $game = $this->game ?: 'coc_home';
        $registry = app(GameRegistry::class);
        $meta = $registry->has($game) ? $registry->get($game)->meta() : ['label' => $game, 'short' => $game, 'icon' => '🎮', 'result_type' => 'layout'];

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'game' => $game,
            'game_label' => $meta['label'],
            'game_short' => $meta['short'],
            'game_icon' => $meta['icon'],
            'result_type' => $this->layout['type'] ?? $meta['result_type'] ?? 'layout',
            'title' => $this->title,
            'th_level' => $this->th_level,
            'image_url' => Storage::url($this->image_path),
            'layout' => $layout,
            'layout_version' => is_array($layout) ? ($layout['version'] ?? null) : null,
            'can_edit' => $forOwner && (($layout['type'] ?? 'layout') === 'layout'),
            // لینک بازی فقط اگر واقعی باشد (Map::isValidCopyLink)؛ لینک دک کلش رویال ساختار دیگری دارد و عبور می‌کند.
            'copy_link' => $this->publicCopyLink($map, $layout),
            'share_url' => $this->share_url,
            'matched_map' => $map && $map->hasValidCopyLink() ? [
                'id' => $map->id,
                'name' => $map->name,
                'copy_link' => $map->copy_link,
                'map_link' => $map->map_link,
                'image_url' => $map->image_url,
            ] : null,
            'match_distance' => $this->match_distance,
            'match_similarity' => $this->match_distance !== null
                ? ImageHasher::similarity($this->match_distance)
                : (isset($layout['match']['similarity']) ? (int) $layout['match']['similarity'] : null),
            'match_method' => $layout['match']['method'] ?? ($this->match_distance !== null ? 'hash' : null),
            'view_count' => $this->view_count,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    /**
     * لینک کپی قابل نمایش: برای چیدمان کلش فقط لینک واقعی OpenLayout (خودِ رکورد یا نقشهٔ منطبق)؛
     * برای نتایج غیرچیدمان (دک کلش رویال) لینک ذخیره‌شده بدون تغییر.
     */
    protected function publicCopyLink(?Map $map, ?array $layout): ?string
    {
        if (($layout['type'] ?? 'layout') !== 'layout') {
            return $this->copy_link ?: null;
        }

        if (Map::isValidCopyLink($this->copy_link)) {
            return $this->copy_link;
        }

        return $map && $map->hasValidCopyLink() ? $map->copy_link : null;
    }

    /**
     * چیدمان برای خروجی: نسخه/منبع همیشه حاضر است؛ کلیدهای داخلی از غیرمالک پنهان می‌شود.
     */
    protected function presentLayout(bool $forOwner): ?array
    {
        $layout = $this->layout;
        if (! is_array($layout)) {
            return $layout;
        }

        if (($layout['type'] ?? 'layout') === 'layout') {
            $layout['version'] = (int) ($layout['version'] ?? 1);
            $layout['source'] = $layout['source'] ?? 'ai';
        }

        if ($forOwner) {
            return $layout;
        }

        foreach (self::PRIVATE_LAYOUT_KEYS as $key) {
            unset($layout[$key]);
        }

        if (isset($layout['buildings']) && is_array($layout['buildings'])) {
            foreach ($layout['buildings'] as &$building) {
                if (is_array($building)) {
                    foreach (self::PRIVATE_BUILDING_KEYS as $key) {
                        unset($building[$key]);
                    }
                }
            }
            unset($building);
        }

        return $layout;
    }
}
