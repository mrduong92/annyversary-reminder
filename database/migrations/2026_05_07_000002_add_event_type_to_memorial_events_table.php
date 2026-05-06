<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->string('event_type', 30)->default('anniversary_of_death')->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('memorial_events', function (Blueprint $table) {
            $table->dropColumn('event_type');
        });
    }
};
