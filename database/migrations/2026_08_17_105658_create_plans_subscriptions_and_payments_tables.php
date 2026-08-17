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
        // ۱. جدول پلن‌های اشتراک
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام پلن مثلا اشتراک ۱ ماهه VIP
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price'); // مبلغ به تومان
            $table->unsignedInteger('original_price')->nullable(); // مبلغ قبل از تخفیف
            $table->unsignedInteger('duration_days')->default(30); // مدت اعتبار به روز
            $table->json('features')->nullable(); // لیست امکانات پلن
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ۲. جدول تراکنش‌های پرداخت
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway'); // zibal, payping, zarinpal
            $table->unsignedInteger('amount'); // تومان
            $table->string('track_id')->nullable()->index(); // Authority / TrackId
            $table->string('ref_number')->nullable()->index(); // شماره پیگیری بانکی
            $table->string('card_pan')->nullable(); // شماره کارت ماسک شده
            $table->enum('status', ['pending', 'paid', 'failed', 'canceled'])->default('pending')->index();
            $table->string('ip_address')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        // ۳. جدول اشتراک‌های فعال کاربران
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->index();
            $table->enum('status', ['active', 'expired', 'canceled'])->default('active')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('plans');
    }
};
