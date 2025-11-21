<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'player_tag'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * روابط کاربر با سایر جداول
     */
    public function gameProfiles()
    {
        return $this->hasMany(GameProfile::class);
    }
    public function gameProfile()
    {
        return $this->hasOne(GameProfile::class);
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

        /**
     * Accessor برای player_tag
     */
    public function getPlayerTagAttribute()
    {
        // اگر GameProfile وجود داشته باشد، player_tag از آن بخواند
        return $this->gameProfile->player_tag ?? null;
    }

    public function getTodayTaskAttribute()
    {
        // محاسبه یا دریافت تسک امروز برای کاربر
        // به عنوان مثال فرض کنید که تسک‌های امروز برای کاربر از جدول `tasks` گرفته می‌شود.
        $todayTask = Task::where('user_id', $this->id)
            ->whereDate('created_at', now()->toDateString()) // جستجوی تسک‌های امروز
            ->first();

        // اگر تسک امروز موجود است، مقدار آن را برمی‌گردانیم
        return $todayTask ? $todayTask->description : 'هیچ تسکی برای امروز تعیین نشده است';
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }

}
