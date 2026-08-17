<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrategyLabSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'image_path',
        'buildings',
        'analysis',
    ];

    protected function casts(): array
    {
        return [
            'buildings' => 'array',
            'analysis' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
