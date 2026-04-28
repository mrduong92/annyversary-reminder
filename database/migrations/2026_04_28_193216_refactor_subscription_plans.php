<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Đổi plan names: basic→mini, unlimited→premium
        DB::table('users')->where('subscription_plan', 'basic')->update(['subscription_plan' => 'mini']);
        DB::table('users')->where('subscription_plan', 'unlimited')->update(['subscription_plan' => 'premium']);

        // 2. Đổi ZNS counter: monthly → yearly
        Schema::table('users', function (Blueprint $table) {
            $table->integer('zns_count_this_year')->default(0)->after('zns_count_this_month');
            $table->year('zns_count_reset_year')->nullable()->after('zns_count_this_year');
        });

        // Migrate existing monthly count → yearly (approximate)
        DB::table('users')->update(['zns_count_this_year' => DB::raw('zns_count_this_month')]);

        // 3. Drop obsolete monthly counters (ai_prayer dùng lại, zns đổi sang yearly)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['zns_count_this_month', 'zns_count_reset_month']);
        });
    }

    public function down(): void
    {
        DB::table('users')->where('subscription_plan', 'mini')->update(['subscription_plan' => 'basic']);
        DB::table('users')->where('subscription_plan', 'premium')->update(['subscription_plan' => 'unlimited']);

        Schema::table('users', function (Blueprint $table) {
            $table->integer('zns_count_this_month')->default(0)->after('subscription_plan');
            $table->date('zns_count_reset_month')->nullable();
            $table->dropColumn(['zns_count_this_year', 'zns_count_reset_year']);
        });
    }
};
