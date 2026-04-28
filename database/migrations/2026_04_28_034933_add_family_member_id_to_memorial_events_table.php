<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->foreignId('family_member_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete()
                  ->after('user_id');
        });

        // Sync ngược: family_members đã có memorial_event_id
        // giờ memorial_events cũng có family_member_id để query 2 chiều dễ hơn
    }

    public function down(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->dropForeign(['family_member_id']);
            $table->dropColumn('family_member_id');
        });
    }
};
