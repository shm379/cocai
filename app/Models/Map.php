<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    use HasFactory;

    /**
     * جدول مرتبط با مدل.
     *
     * @var string
     */
    protected $table = 'maps';
    public $timestamps = false;

    /**
     * مقادیر قابل پر کردن.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'image_url',
        'thumbnail_url',
        'map_link',
        'copy_link',
        'view_count',
        'download_count',
        'like_count',
        'report_count',
        'created_at',
    ];

    /**
     * رابطه بین Map و Topic (Many to Many)
     */
    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'map_topic', 'map_id', 'topic_id');
    }
}
