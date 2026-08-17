<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaTierItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'town_hall_min',
        'town_hall_max',
        'tier',
        'win_rate_percentage',
        'difficulty_rating',
        'army_link',
        'image_url',
        'tactical_brief_fa',
        'units_payload',
        'equipment_payload',
        'is_featured',
        'views_count',
        'copies_count',
    ];

    protected $casts = [
        'units_payload' => 'array',
        'equipment_payload' => 'array',
        'is_featured' => 'boolean',
        'town_hall_min' => 'integer',
        'town_hall_max' => 'integer',
        'win_rate_percentage' => 'integer',
        'difficulty_rating' => 'integer',
    ];

    public function tierBadgeColor(): string
    {
        return match ($this->tier) {
            'S_PLUS' => 'danger',
            'S' => 'warning',
            'A' => 'success',
            'B' => 'info',
            default => 'gray',
        };
    }
}
