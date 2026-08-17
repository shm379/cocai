<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'اشتراک ۱ ماهه فرمانده برنزی',
                'slug' => 'bronze-monthly',
                'description' => 'دسترسی کامل به تحلیل‌های پیشرفته، آنالیزور بیس و ربات هوش مصنوعی',
                'price' => 49000,
                'original_price' => 79000,
                'duration_days' => 30,
                'features' => [
                    'دسترسی نامحدود به مربی هوش مصنوعی NabuGate',
                    'تحلیل وضعیت راش و محاسبه زمان ارتقاها',
                    'آنالیز تصویری نقشه‌ها در آزمایشگاه استراتژی',
                    'تسک و تقویم روزانه هوشمند',
                    'هاب ارتش‌های متای وار و فارم',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'اشتراک ۳ ماهه فرمانده نقره‌ای (پرفروش)',
                'slug' => 'silver-quarterly',
                'description' => 'بهترین انتخاب برای واربازها و کلن لیدرها با ۴۰٪ تخفیف ویژه',
                'price' => 119000,
                'original_price' => 199000,
                'duration_days' => 90,
                'features' => [
                    'تمام امکانات پلن برنزی',
                    'تولید لینک مستقیم ارتش به بازی با ۱ کلیک',
                    'برنامه‌ریز تارگت‌های وار و محاسبه‌گر CWL',
                    'تجهیزات و پت‌های متای ۲۰۲۶',
                    'پشتیبانی VIP در پنل فرماندهی',
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'اشتراک سالانه فرمانده طلایی VIP',
                'slug' => 'gold-yearly',
                'description' => 'دسترسی ۱ ساله به تمامی بازی‌های سوپرسل (CoC, CR, Brawl Stars, Squad Busters)',
                'price' => 349000,
                'original_price' => 599000,
                'duration_days' => 365,
                'features' => [
                    'دسترسی ۳۶۵ روزه نامحدود و بدون قفل',
                    'هاب جامع تمامی بازی‌های سوپرسل',
                    'سوییچ نامحدود بین اکانت‌ها و مینی‌اکانت‌ها',
                    'اولویت پردازش هوش مصنوعی در سرور اختصاصی',
                    'دسترسی زودهنگام به قابلیت‌های جدید و آپدیت‌های کلش',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
