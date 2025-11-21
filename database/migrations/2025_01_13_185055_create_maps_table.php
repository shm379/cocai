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
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('map_link')->nullable()->unique();
            $table->string('copy_link')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('report_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
