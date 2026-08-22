<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MapFavorite extends Pivot
{
    protected $table = 'map_favorites';

    protected $casts = [
        'tags' => 'json',
    ];

    protected $fillable = [
        'user_id',
        'map_id',
        'notes',
        'tags',
    ];
}
