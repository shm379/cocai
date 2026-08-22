<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPassword;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) ($this->is_admin ?? false);
    }

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
        'is_admin',
        'player_tag',
        'task_streak',
        'task_last_completed_at',
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
            'task_last_completed_at' => 'datetime',
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
            ->latest()
            ->first();

        // ستون متن تسک `task` است
        return $todayTask ? $todayTask->task : 'هیچ تسکی برای امروز تعیین نشده است';
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }

    public function favoriteMaps()
    {
        return $this->belongsToMany(Map::class, 'map_favorites')
            ->using(MapFavorite::class)
            ->withPivot(['notes', 'tags'])
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        // ادمین‌ها همیشه دسترسی نامحدود دارند
        if ($this->is_admin) {
            return true;
        }

        return $this->activeSubscription() !== null;
    }

    public function subscriptionDaysLeft(): int
    {
        if ($this->is_admin) {
            return 999;
        }

        $sub = $this->activeSubscription();
        return $sub ? $sub->daysRemaining() : 0;
    }

    public function strategyLabSessions()
    {
        return $this->hasMany(StrategyLabSession::class);
    }

    /**
     * Increment task completion streak based on calendar days.
     * Resets if last completion was before yesterday.
     */
    public function recordTaskCompletion(): void
    {
        $last = $this->task_last_completed_at;

        if ($last === null) {
            $this->task_streak = 1;
        } elseif ($last->isYesterday()) {
            $this->task_streak += 1;
        } elseif ($last->isToday()) {
            // Already counted today; do nothing.
        } else {
            $this->task_streak = 1;
        }

        $this->task_last_completed_at = now();
        $this->save();
    }

    /**
     * ارسال نوتیفیکیشن بازیابی رمز عبور با قالب و لاگ‌گذاری سفارشی.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPassword($token));
    }
}
