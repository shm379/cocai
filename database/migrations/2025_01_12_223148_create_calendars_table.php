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
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ارجاع به کاربر
            $table->unsignedBigInteger('game_profile_id')->unique(); // ارجاع به کاربر
            $table->integer('day'); // شماره روز
            $table->text('task'); // شرح وظیفه
            $table->timestamps();

            // ارجاع به جدول users برای مرتبط کردن با گیم پروفایل
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('game_profile_id')->references('id')->on('geme_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
