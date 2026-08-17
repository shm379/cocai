<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meta_tier_items', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان متای برتر
            $table->string('category'); // army, equipment, base_defense, attack_combo
            $table->tinyInteger('town_hall_min')->default(11);
            $table->tinyInteger('town_hall_max')->default(18);
            $table->enum('tier', ['S_PLUS', 'S', 'A', 'B'])->default('S_PLUS'); // رتبه متا
            $table->unsignedTinyInteger('win_rate_percentage')->default(95); // درصد برد تخمینی مثلا ۹۶٪
            $table->unsignedTinyInteger('difficulty_rating')->default(2); // ۱ آسان تا ۵ سخت
            $table->string('army_link')->nullable(); // لینک مستقیم ارتش به بازی
            $table->string('image_url')->nullable();
            $table->text('tactical_brief_fa'); // خلاصه تاکتیکی و نحوه اجرای حمله برای ۳ ستاره
            $table->json('units_payload')->nullable(); // لیست نیروها و اسپل‌ها
            $table->json('equipment_payload')->nullable(); // لیست تجهیزات هیرو
            $table->boolean('is_featured')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('copies_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_tier_items');
    }
};
