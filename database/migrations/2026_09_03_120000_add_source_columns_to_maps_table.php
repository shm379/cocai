<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->string('source', 32)->default('clasher')->index()->after('report_count');
            $table->string('external_id', 64)->nullable()->index()->after('source');
            $table->string('category', 32)->nullable()->after('external_id');
            $table->timestamp('published_at')->nullable()->after('category');
            $table->timestamp('fetched_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['external_id']);
            $table->dropColumn(['source', 'external_id', 'category', 'published_at', 'fetched_at']);
        });
    }
};
