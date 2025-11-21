<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gear_up_cost',
        'gear_up_time',
        'size_on_board',
        'damage_type',
        'hv_cannon_level_required',
        'bb_double_cannon_level_required',
        'unit_type_targeted',
        'elixir_type',
        'range',
        'has_gear',
        'game',
        'summary',
        'trivia',
        'icon_des',
        'levels',
        'levels_gear',
        'offensive_strategy',
        'defensive_strategy',
        'img',
    ];

    protected $casts = [
        'trivia' => 'array',
        'icon_des' => 'array',
        'levels' => 'array',
        'levels_gear' => 'array',
        'offensive_strategy' => 'array',
        'defensive_strategy' => 'array',
    ];
}
