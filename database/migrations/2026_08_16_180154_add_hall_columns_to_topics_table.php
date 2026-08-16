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
        Schema::table('topics', function (Blueprint $table) {
            if (! Schema::hasColumn('topics', 'hall_type')) {
                $table->tinyInteger('hall_type')->nullable()->after('name');
            }

            if (! Schema::hasColumn('topics', 'hall_level')) {
                $table->tinyInteger('hall_level')->nullable()->after('hall_type');
            }
        });

        Schema::table('topics', function (Blueprint $table) {
            try {
                $table->index(['hall_type', 'hall_level']);
            } catch (\Throwable $e) {
                // Index may already exist.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            if (Schema::hasColumn('topics', 'hall_level')) {
                $table->dropColumn('hall_level');
            }

            if (Schema::hasColumn('topics', 'hall_type')) {
                $table->dropColumn('hall_type');
            }
        });
    }
};
