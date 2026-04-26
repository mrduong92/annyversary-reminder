<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('subscription_plan', ['free', 'basic', 'unlimited'])->default('free')->after('remember_token');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
            $table->unsignedInteger('zns_count_this_month')->default(0)->after('subscription_expires_at');
            $table->unsignedInteger('ai_prayer_count_this_month')->default(0)->after('zns_count_this_month');
            $table->unsignedInteger('ai_message_count_today')->default(0)->after('ai_prayer_count_this_month');
            $table->date('ai_message_reset_date')->nullable()->after('ai_message_count_today');
            $table->date('zns_count_reset_month')->nullable()->after('ai_message_reset_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_expires_at',
                'zns_count_this_month',
                'ai_prayer_count_this_month',
                'ai_message_count_today',
                'ai_message_reset_date',
                'zns_count_reset_month',
            ]);
        });
    }
};
