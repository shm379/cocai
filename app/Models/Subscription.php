<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'payment_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function daysRemaining(): int
    {
        if (! $this->isValid()) {
            return 0;
        }

        return (int) max(0, Carbon::now()->diffInDays($this->ends_at, false));
    }
}
