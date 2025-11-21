<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrophyLog extends Model
{
    use HasFactory;

    protected $fillable = ['game_profile_id', 'trophy_count'];

    // رابطه با GameProfile
    public function gameProfile()
    {
        return $this->belongsTo(GameProfile::class);
    }
}

