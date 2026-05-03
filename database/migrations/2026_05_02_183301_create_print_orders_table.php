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
        Schema::create('print_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('print_templates')->cascadeOnDelete();
            $table->json('member_ids')->nullable();
            
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->string('shipping_address');
            $table->string('shipping_city')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_ward')->nullable();
            
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('pending'); // pending, processing, shipped, delivered, cancelled
            
            $table->string('tracking_number')->nullable();
            $table->string('shipping_company')->nullable();
            $table->decimal('shipping_fee', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_price', 10, 2)->default(0);
            
            $table->timestamps();
        });
        
        // Bảng trung gian cho relationships (nếu muốn queries nhiều-nhiều theo member)
        Schema::create('family_member_print_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_member_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_member_print_order');
        Schema::dropIfExists('print_orders');
    }
};
