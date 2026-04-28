<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('pronoun', 50)->nullable()->after('name');       // VD: Cụ, Ông, Bà
            $table->string('relationship', 80)->nullable()->after('pronoun'); // VD: Ông nội, Bà ngoại
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn(['pronoun', 'relationship']);
        });
    }
};
