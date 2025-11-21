<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task',
        'completed',
    ];

    /**
     * رابطه با مدل User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
