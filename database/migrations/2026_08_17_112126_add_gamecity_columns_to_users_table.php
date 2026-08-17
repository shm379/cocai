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
            if (! Schema::hasColumn('users', 'gamecity_id')) {
                $table->string('gamecity_id')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile')->nullable()->index()->after('email');
            }
            if (! Schema::hasColumn('users', 'wallet_balance')) {
                $table->unsignedBigInteger('wallet_balance')->default(0)->after('mobile');
            }
            if (! Schema::hasColumn('users', 'crm_tier')) {
                $table->string('crm_tier')->default('standard')->after('wallet_balance'); // standard, vip, gold, diamond
            }
            if (! Schema::hasColumn('users', 'gamecity_meta')) {
                $table->json('gamecity_meta')->nullable()->after('crm_tier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gamecity_id', 'mobile', 'wallet_balance', 'crm_tier', 'gamecity_meta']);
        });
    }
};
