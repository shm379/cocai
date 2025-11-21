<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameProfile extends Model
{
    use HasFactory;

    protected $table = 'game_profiles';

    protected $fillable = [
        'user_id',
        'player_tag',
        'game_data', // این ستون JSON اطلاعات بازی را ذخیره می‌کند
    ];

    protected $casts = [
        'game_data' => 'array', // داده‌های JSON را به آرایه تبدیل می‌کند
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function trophyHistory()
    {
        return $this->hasMany(TrophyLog::class, 'game_profile_id');
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class,'game_profile_id');
    }
}
