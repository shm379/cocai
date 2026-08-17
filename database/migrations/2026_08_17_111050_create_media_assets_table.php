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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام فایل
            $table->string('disk')->default('public');
            $table->string('file_path'); // مسیر ذخیره‌سازی در دیسک
            $table->string('file_url')->nullable(); // آدرس مستقیم یا CDN
            $table->string('category')->default('general'); // maps, troops, heroes, banners, general
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // سایز به بایت
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
