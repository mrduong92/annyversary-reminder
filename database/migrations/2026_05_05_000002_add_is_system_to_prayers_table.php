<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prayers', function (Blueprint $table) {
            // Văn khấn hệ thống (is_system=true) dùng chung cho tất cả user, user_id = null
            $table->boolean('is_system')->default(false)->after('ai_generated');
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('prayers', function (Blueprint $table) {
            $table->dropColumn('is_system');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
