<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->boolean('is_alive')->default(true)->after('death_year');
        });

        // Cập nhật dữ liệu cũ: nếu có death_year hoặc death_day, nghĩa là đã mất
        DB::table('family_members')
            ->whereNotNull('death_year')
            ->orWhereNotNull('death_day')
            ->update(['is_alive' => false]);
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('is_alive');
        });
    }
};
