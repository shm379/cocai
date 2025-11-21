<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'type',
        'content',
    ];

    public function categories()
    {
        return $this->belongsToMany(GuideCat::class, 'guide_guide_cat');
    }
}

