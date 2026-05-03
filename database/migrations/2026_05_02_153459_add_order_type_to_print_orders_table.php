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
        Schema::table('print_orders', function (Blueprint $table) {
            $table->string('order_type')->default('digital')->after('template_id');
            // Shipping fields are now nullable for digital orders
            $table->string('shipping_name')->nullable()->change();
            $table->string('shipping_phone')->nullable()->change();
            $table->string('shipping_address')->nullable()->change();
            $table->string('shipping_city')->nullable()->change();
            $table->string('shipping_district')->nullable()->change();
            $table->string('shipping_ward')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn('order_type');
            $table->string('shipping_name')->nullable(false)->change();
            $table->string('shipping_phone')->nullable(false)->change();
            $table->string('shipping_address')->nullable(false)->change();
            $table->string('shipping_city')->nullable(false)->change();
            $table->string('shipping_district')->nullable(false)->change();
            $table->string('shipping_ward')->nullable(false)->change();
        });
    }
};
