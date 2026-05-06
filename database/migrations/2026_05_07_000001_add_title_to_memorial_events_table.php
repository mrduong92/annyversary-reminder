<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            // Tiêu đề tự do — dùng khi không gắn thành viên gia phả
            $table->string('title')->nullable()->after('family_member_id');

            // family_member_id không còn bắt buộc
            $table->foreignId('family_member_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->foreignId('family_member_id')->nullable(false)->change();
        });
    }
};
