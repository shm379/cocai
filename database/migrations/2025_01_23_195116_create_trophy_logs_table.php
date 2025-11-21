<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('trophy_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_profile_id');
            $table->integer('trophy_count');
            $table->timestamps();
            $table->foreign('game_profile_id')->references('id')->on('game_profiles');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trophy_logs');
    }
};
