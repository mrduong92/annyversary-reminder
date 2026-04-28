<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_documents', function (Blueprint $table) {
            $table->foreignId('family_group_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('family_documents', function (Blueprint $table) {
            $table->dropForeign(['family_group_id']);
            $table->dropColumn('family_group_id');
        });
    }
};
