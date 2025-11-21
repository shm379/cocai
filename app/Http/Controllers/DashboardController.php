<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\Topic;
use App\Models\TrophyLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // بررسی `player_tag`
        if (!$user->player_tag) {
            return Inertia::render('Dashboard', [
                'user' => $user,
                'successMessage' => session('success'),
                'errorMessage' => session('error'),
            ]);
        }

        // دریافت اطلاعات بازی و وظایف روزانه
        $gameProfile = $user->gameProfile->game_data ?? [];

        // 1. تمام رکوردهای units را می‌گیریم و با key اسم یونیت در یک آرایه/کالکشن قرار می‌دهیم
        $allUnits = \App\Models\Unit::all()->keyBy('name');

        // 2. اگر در $gameProfile['troops'] لیستی از نیروها داریم، آیکون هرکدام را از جدول units پر می‌کنیم.
        if (!empty($gameProfile['troops']) && is_array($gameProfile['troops'])) {
            foreach ($gameProfile['troops'] as $index => $troop) {
                // troop.name در API بازی یا شیء شما ممکن است دقیقاً با name در جدول units یکی باشد.
                // اگر متفاوت است، باید تبدیل یا نگاشت کنید.
                $troopName = $troop['name'] ?? null;
                if ($troopName && isset($allUnits[$troopName])) {
                    // اگر در units ردیفی با نام همین نیرو وجود دارد
                    $gameProfile['troops'][$index]['icon'] = $allUnits[$troopName]->img;
                    $gameProfile['troops'][$index]['type'] = $allUnits[$troopName]->type;
                } else {
                    // در غیر این صورت، آیکون پیش‌فرض
                    $gameProfile['troops'][$index]['icon'] = '/images/default_icon.webp';
                }
            }
        }

        // 3. اگر نیاز است برای heroes یا spells هم همین کار را بکنید، شبیه بالا تکرار کنید
        if (!empty($gameProfile['heroes']) && is_array($gameProfile['heroes'])) {
            foreach ($gameProfile['heroes'] as $index => $hero) {
                $heroName = $hero['name'] ?? null;
                if ($heroName && isset($allUnits[$heroName])) {
                    $gameProfile['heroes'][$index]['icon'] = $allUnits[$heroName]->img;
                    $gameProfile['heroes'][$index]['type'] = $allUnits[$heroName]->type;
                } else {
                    $gameProfile['heroes'][$index]['icon'] = '/images/default_icon.webp';
                }
            }
        }

        if (!empty($gameProfile['spells']) && is_array($gameProfile['spells'])) {
            foreach ($gameProfile['spells'] as $index => $spell) {
                $spellName = $spell['name'] ?? null;
                if ($spellName && isset($allUnits[$spellName])) {
                    $gameProfile['spells'][$index]['icon'] = $allUnits[$spellName]->img;
                    $gameProfile['spells'][$index]['type'] = $allUnits[$spellName]->type;
                } else {
                    $gameProfile['spells'][$index]['icon'] = '/images/default_icon.webp';
                }
            }
        }
        $todayTask   = $user->tasks()->latest()->first()->task ?? 'هیچ تسکی تعریف نشده است.';
        $calendar    = $user->gameProfile->calendars ?? [];

        // تعداد آیتم‌های صفحه‌بندی
        $perPage = 12;

        // دریافت پارامترهای فیلتر و مرتب‌سازی از `Request`
        $sortBy    = $request->query('sortBy', 'latest');
        $topicId   = $request->query('topicId', null);
// در کنترلر
        $hallType = $request->query('hallType', null);

// تبدیل به عدد (مثلاً با (int))
        $hallType = is_null($hallType) ? null : (int) $hallType;
        $hallLevel = $request->query('hallLevel', null);   // سطح `TH` یا `BH` (مثلاً 10، 11، 12)

        // صفحه جاری برای TH و BH
        $thPage = $request->query('thPage', 1);
        $bhPage = $request->query('bhPage', 1);

        // **کوئری برای دریافت نقشه‌های Town Hall**
        $townHallMapsQuery = Map::whereHas('topics', function ($query) {
            $query->where('name', 'like', 'Town Hall%'); // جستجو در `topics` برای تاون‌هال
        });

        // **کوئری برای دریافت نقشه‌های Builder Hall**
        $builderHallMapsQuery = Map::whereHas('topics', function ($query) {
            $query->where('name', 'like', 'Builder Hall%'); // جستجو در `topics` برای بیلدرهال
        });

        // **اعمال فیلتر بر اساس `hallType` و `hallLevel`**
        if (!is_null($hallType)) {
            if ((int)$hallType === 0) {
                $townHallMapsQuery->whereHas('topics', function ($query) {
                    $query->where('hall_type', 0);
                });
            } else {
                $builderHallMapsQuery->whereHas('topics', function ($query) {
                    $query->where('hall_type', 1);
                });
            }
        }

        if (!is_null($hallLevel)) {
            if ((int)$hallType === 0) {
                $townHallMapsQuery->whereHas('topics', function ($query) use ($hallLevel) {
                    $query->where('hall_level', $hallLevel);
                });
            } else {
                $builderHallMapsQuery->whereHas('topics', function ($query) use ($hallLevel) {
                    $query->where('hall_level', $hallLevel);
                });
            }
        }

        // **اعمال مرتب‌سازی**
        switch ($sortBy) {
            case 'views':
                $townHallMapsQuery->orderByDesc('view_count');
                $builderHallMapsQuery->orderByDesc('view_count');
                break;
            case 'likes':
                $townHallMapsQuery->orderByDesc('like_count');
                $builderHallMapsQuery->orderByDesc('like_count');
                break;
            case 'oldest':
                $townHallMapsQuery->orderBy('created_at');
                $builderHallMapsQuery->orderBy('created_at');
                break;
            default:
                // latest
                $townHallMapsQuery->orderByDesc('created_at');
                $builderHallMapsQuery->orderByDesc('created_at');
        }

        // **اعمال صفحه‌بندی (پس از اعمال فیلتر)**
        $townHallMaps   = $townHallMapsQuery->paginate($perPage, ['*'], 'thPage', $thPage);
        $builderHallMaps = $builderHallMapsQuery->paginate($perPage, ['*'], 'bhPage', $bhPage);

        // **دریافت لیست `Topic` ها برای فیلتر کردن**
        $topics = Topic::orderBy('name')->get();

// دریافت تاریخچه تروفی برای `game_profile_id` فعلی
        $trophyHistory = $user->gameProfile->trophyHistory()
            ->orderBy('created_at', 'asc')
            ->get(['created_at', 'trophy_count'])
            ->map(function ($log) {
                return [
                    'date' => $log->created_at->format('Y-m-d'),
                    'trophy' => $log->trophy_count
                ];
            });

        return Inertia::render('Dashboard', [
            'user'           => $user,
            'gameProfile'    => $gameProfile,
            'todayTask'      => $todayTask,
            'successMessage' => session('success'),
            'errorMessage'   => session('error'),
            'calendar'       => $calendar,
            'townHallMaps'   => $townHallMaps,
            'builderHallMaps'=> $builderHallMaps,
            'topics'         => $topics,
            'selectedTopic'  => $topicId,
            'selectedSort'   => $sortBy,
            'selectedHallType'  => $hallType,
            'selectedHallLevel' => (int)$hallLevel,
            "trophyHistory"=> $trophyHistory,
        ]);
    }
}

