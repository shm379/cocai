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
     */
    public function toPublicArray(): array
    {
        $map = $this->matchedMap;
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
            'layout' => $this->layout,
            'copy_link' => $this->copy_link ?: $map?->copy_link,
            'share_url' => $this->share_url,
            'matched_map' => $map ? [
                'id' => $map->id,
                'name' => $map->name,
                'copy_link' => $map->copy_link,
                'map_link' => $map->map_link,
                'image_url' => $map->image_url,
            ] : null,
            'match_distance' => $this->match_distance,
            'match_similarity' => $this->match_distance === null
                ? null
                : ImageHasher::similarity($this->match_distance),
            'view_count' => $this->view_count,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
