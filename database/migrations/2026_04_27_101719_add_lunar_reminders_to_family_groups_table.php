<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_groups', function (Blueprint $table) {
            // Nhắc ngày Rằm (15) và Mùng 1 âm lịch hàng tháng
            $table->boolean('remind_ram')->default(false)->after('is_default');
            $table->boolean('remind_mung_mot')->default(false)->after('remind_ram');
        });
    }

    public function down(): void
    {
        Schema::table('family_groups', function (Blueprint $table) {
            $table->dropColumn(['remind_ram', 'remind_mung_mot']);
        });
    }
};
