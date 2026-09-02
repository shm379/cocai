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
        Schema::create('base_clones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 32)->unique();
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->unsignedTinyInteger('th_level')->nullable();
            $table->string('image_hash', 16)->nullable()->index();
            $table->json('layout');
            $table->foreignId('matched_map_id')->nullable()->constrained('maps')->nullOnDelete();
            $table->unsignedTinyInteger('match_distance')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_clones');
    }
};
