<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('base_clones', function (Blueprint $table) {
            $table->string('game', 32)->default('coc_home')->index()->after('slug');
            $table->string('copy_link', 1000)->nullable()->after('layout');
        });
    }

    public function down(): void
    {
        Schema::table('base_clones', function (Blueprint $table) {
            $table->dropIndex(['game']);
            $table->dropColumn(['game', 'copy_link']);
        });
    }
};
