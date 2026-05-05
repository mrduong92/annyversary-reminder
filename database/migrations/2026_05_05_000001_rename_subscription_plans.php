<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrate data trước khi đổi column
        DB::table('users')
            ->whereIn('subscription_plan', ['free', 'mini'])
            ->update(['subscription_plan' => 'basic']);

        DB::table('users')
            ->where('subscription_plan', 'premium')
            ->update(['subscription_plan' => 'advanced']);

        // 2. Đổi column thành string (enum cũ) — dùng Schema builder chuẩn
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan')->default('basic')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan')->default('free')->change();
        });

        DB::table('users')
            ->where('subscription_plan', 'basic')
            ->update(['subscription_plan' => 'free']);

        DB::table('users')
            ->where('subscription_plan', 'advanced')
            ->update(['subscription_plan' => 'premium']);
    }
};
