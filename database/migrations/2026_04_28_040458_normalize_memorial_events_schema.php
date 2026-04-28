<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // family_member_id đã được fill ở migration trước
        // Giờ make NOT NULL và drop các cột duplicate

        Schema::table('memorial_events', function (Blueprint $table) {
            // Make family_member_id NOT NULL
            $table->foreignId('family_member_id')->nullable(false)->change();

            // Xóa các cột đã move sang family_members
            $table->dropColumn(['name', 'pronoun', 'relationship']);
        });

        // Đảm bảo family_group_id cũng NOT NULL
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->foreignId('family_group_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('pronoun', 50)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->foreignId('family_member_id')->nullable()->change();
        });
    }
};
