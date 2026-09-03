<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * امضای چیدمان (خروجی LayoutSignature) برای تطبیق ساختاری بیس آپلودشده با آرشیو،
     * مستقل از زوم/برش/اسکین تصویر. با دستور maps:signature پر می‌شود.
     */
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->json('layout_signature')->nullable()->after('image_hash');
            $table->timestamp('signature_computed_at')->nullable()->after('layout_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->dropColumn(['layout_signature', 'signature_computed_at']);
        });
    }
};
