<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_groups', function (Blueprint $table) {
            $table->timestamp('tree_updated_at')->nullable()->after('remind_mung_mot');
        });
    }

    public function down(): void
    {
        Schema::table('family_groups', function (Blueprint $table) {
            $table->dropColumn('tree_updated_at');
        });
    }
};
