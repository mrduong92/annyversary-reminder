<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipients', function (Blueprint $table) {
            $table->dropForeign(['memorial_event_id']);
            $table->dropColumn('memorial_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('recipients', function (Blueprint $table) {
            $table->foreignId('memorial_event_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
