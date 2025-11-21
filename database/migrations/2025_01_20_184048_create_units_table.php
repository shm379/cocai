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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->nullable();
            $table->string('gear_up_cost')->nullable();
            $table->string('gear_up_time')->nullable();
            $table->string('size_on_board')->nullable();
            $table->string('damage_type')->nullable();
            $table->integer('hv_cannon_level_required')->nullable();
            $table->integer('bb_double_cannon_level_required')->nullable();
            $table->string('unit_type_targeted')->nullable();
            $table->string('elixir_type')->nullable();
            $table->string('range')->nullable();
            $table->boolean('has_gear')->default(false);
            $table->string('game')->nullable();
            $table->text('summary')->nullable(); // اضافه شدن خلاصه
            $table->json('trivia')->nullable(); // اضافه شدن جزئیات بیشتر
            $table->json('icon_des')->nullable();
            $table->json('levels')->nullable();
            $table->json('levels_gear')->nullable();
            $table->json('offensive_strategy')->nullable();
            $table->json('defensive_strategy')->nullable();
            $table->string('img')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
