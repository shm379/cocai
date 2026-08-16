<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'name',
        'hall_type',
        'hall_level',
    ];

    protected $casts = [
        'hall_type' => 'integer',
        'hall_level' => 'integer',
    ];
}
