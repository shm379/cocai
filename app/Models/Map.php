<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    use HasFactory;

    /**
     * الگوی لینک واقعی OpenLayout بازی. شناسهٔ چیدمان یک ارجاع ۲۴ بایتی (۳۲ کاراکتر base64url)
     * است که فقط خود بازی صادر می‌کند؛ هر چیز دیگری (مثل شناسه‌های جای‌گذار seeder) در بازی باز نمی‌شود.
     * دونقطه هم به‌صورت خام (:) و هم url-encoded (%3A) پذیرفته می‌شود.
     */
    public const COPY_LINK_PATTERN = '~^https://link\.clashofclans\.com/.*action=OpenLayout&id=TH(\d{1,2})(?:%3A|:)(HV|WB|BB2?)(?:%3A|:)[A-Za-z0-9_-]{32}$~i';

    /** پیشوند SQL برای پیش‌فیلتر لینک‌های احتمالاً معتبر (بررسی دقیق با {@see isValidCopyLink}). */
    public const COPY_LINK_SQL_PREFIX = 'https://link.clashofclans.com/%action=OpenLayout&id=TH%';

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
        'image_hash',
        'map_link',
        'copy_link',
        'layout_signature',
        'signature_computed_at',
        'view_count',
        'download_count',
        'like_count',
        'report_count',
        'source',
        'external_id',
        'category',
        'published_at',
        'fetched_at',
        'created_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
        'layout_signature' => 'array',
        'signature_computed_at' => 'datetime',
    ];

    /**
     * رابطه بین Map و Topic (Many to Many)
     */
    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'map_topic', 'map_id', 'topic_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'map_favorites')->withTimestamps();
    }

    /**
     * آیا رشته یک لینک واقعی OpenLayout بازی است؟
     */
    public static function isValidCopyLink(?string $link): bool
    {
        return is_string($link) && preg_match(self::COPY_LINK_PATTERN, trim($link)) === 1;
    }

    /**
     * آیا این نقشه لینک کپی واقعی (قابل باز شدن در بازی) دارد؟
     */
    public function hasValidCopyLink(): bool
    {
        return self::isValidCopyLink($this->copy_link);
    }

    /**
     * اطلاعات چیدمان از روی شناسهٔ لینک: سطح هال و نوع دهکده.
     *
     * HV (Home Village) و WB (War Base) هر دو دهکدهٔ اصلی‌اند؛ BB/BB2 بیلدر بیس.
     *
     * @return array{th:int,village:string,kind:string}|null
     */
    public static function parseCopyLink(?string $link): ?array
    {
        if (! is_string($link) || preg_match(self::COPY_LINK_PATTERN, trim($link), $m) !== 1) {
            return null;
        }

        $kind = strtoupper($m[2]);

        return [
            'th' => (int) $m[1],
            'village' => str_starts_with($kind, 'BB') ? 'builder' : 'home',
            'kind' => $kind,
        ];
    }

    /**
     * سطح هال این نقشه از روی لینک (منبع قطعی) و در نبود آن از موضوع (Topic).
     */
    public function hallLevel(): ?int
    {
        $parsed = self::parseCopyLink($this->copy_link);
        if ($parsed !== null) {
            return $parsed['th'];
        }

        $topic = $this->relationLoaded('topics') ? $this->topics->firstWhere('hall_level', '!=', null) : null;

        return $topic?->hall_level;
    }

    /**
     * نوع دهکدهٔ این نقشه: 'home' یا 'builder' (از لینک؛ در نبود آن از موضوع).
     */
    public function village(): ?string
    {
        $parsed = self::parseCopyLink($this->copy_link);
        if ($parsed !== null) {
            return $parsed['village'];
        }

        $topic = $this->relationLoaded('topics') ? $this->topics->firstWhere('hall_type', '!=', null) : null;
        if ($topic === null) {
            return null;
        }

        return (int) $topic->hall_type === 1 ? 'builder' : 'home';
    }

    /**
     * پیش‌فیلتر SQL نقشه‌هایی که «احتمالاً» لینک واقعی دارند. چون الگوی دقیق در SQL قابل‌حمل نیست،
     * نتیجه باید با {@see hasValidCopyLink} در PHP دوباره بررسی شود.
     */
    public function scopeLikelyValidCopyLink(Builder $query): Builder
    {
        return $query->whereNotNull('copy_link')->where('copy_link', 'like', self::COPY_LINK_SQL_PREFIX);
    }
}
