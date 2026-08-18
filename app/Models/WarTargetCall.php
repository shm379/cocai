<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarTargetCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clan_tag',
        'clan_name',
        'target_number',
        'target_player_tag',
        'target_player_name',
        'target_th_level',
        'caller_name',
        'caller_tag',
        'caller_th_level',
        'status',
        'attack_result_stars',
        'attack_destruction_percent',
        'recommended_army',
        'win_probability',
        'tactical_notes',
        'expires_at',
    ];

    protected $casts = [
        'target_number' => 'integer',
        'target_th_level' => 'integer',
        'caller_th_level' => 'integer',
        'attack_result_stars' => 'integer',
        'attack_destruction_percent' => 'integer',
        'win_probability' => 'integer',
        'recommended_army' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * بررسی اینکه آیا کال منقضی شده است یا خیر.
     */
    public function isExpired(): bool
    {
        return $this->status === 'called' && $this->expires_at && $this->expires_at->isPast();
    }
}
