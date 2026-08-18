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
        Schema::create('war_target_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('clan_tag')->index();
            $table->string('clan_name')->nullable();
            $table->unsignedInteger('target_number')->index();
            $table->string('target_player_tag')->nullable();
            $table->string('target_player_name')->nullable();
            $table->unsignedTinyInteger('target_th_level')->default(15);
            $table->string('caller_name');
            $table->string('caller_tag');
            $table->unsignedTinyInteger('caller_th_level')->default(15);
            $table->string('status')->default('called')->index(); // called, attacked, cleared, expired, canceled
            $table->unsignedTinyInteger('attack_result_stars')->nullable();
            $table->unsignedTinyInteger('attack_destruction_percent')->nullable();
            $table->json('recommended_army')->nullable();
            $table->unsignedTinyInteger('win_probability')->default(75);
            $table->text('tactical_notes')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('war_target_calls');
    }
};
