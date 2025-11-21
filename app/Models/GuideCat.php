<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideCat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function guides()
    {
        return $this->belongsToMany(Guide::class, 'guide_guide_cat');
    }
}
