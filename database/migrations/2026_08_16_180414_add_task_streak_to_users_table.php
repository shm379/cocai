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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'task_streak')) {
                $table->unsignedSmallInteger('task_streak')->default(0);
            }

            if (! Schema::hasColumn('users', 'task_last_completed_at')) {
                $table->timestamp('task_last_completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'task_last_completed_at')) {
                $table->dropColumn('task_last_completed_at');
            }

            if (Schema::hasColumn('users', 'task_streak')) {
                $table->dropColumn('task_streak');
            }
        });
    }
};
