<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_groups', function (Blueprint $table) {
            $table->renameColumn('remind_ram',     'remind_full_moon');
            $table->renameColumn('remind_mung_mot', 'remind_first_day');
        });
    }

    public function down(): void
    {
        Schema::table('family_groups', function (Blueprint $table) {
            $table->renameColumn('remind_full_moon', 'remind_ram');
            $table->renameColumn('remind_first_day',  'remind_mung_mot');
        });
    }
};
