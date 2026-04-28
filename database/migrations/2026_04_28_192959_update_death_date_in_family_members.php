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
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('birth_date_lunar');
            $table->tinyInteger('death_day')->nullable()->after('death_year');
            $table->tinyInteger('death_month')->nullable()->after('death_day');
            $table->enum('death_date_type', ['lunar', 'solar'])->nullable()->after('death_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('birth_date_lunar', 10)->nullable();
            $table->dropColumn(['death_day', 'death_month', 'death_date_type']);
        });
    }
};
