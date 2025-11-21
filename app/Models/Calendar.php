<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // شناسه کاربر
        'game_profile_id', // شناسه کاربر
        'day',     // شماره روز
        'task',    // شرح وظیفه
    ];

    // ارتباط با مدل User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
