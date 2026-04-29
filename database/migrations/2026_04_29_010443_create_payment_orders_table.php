<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);                    // mini | premium
            $table->unsignedInteger('amount');             // VND
            $table->string('reference_code', 20)->unique(); // "GP-XXXXXX" — user điền vào nội dung CK
            $table->enum('status', ['pending', 'completed', 'expired', 'failed'])->default('pending');
            $table->string('sepay_transaction_id')->nullable()->unique(); // idempotency
            $table->text('sepay_payload')->nullable();     // raw webhook JSON
            $table->timestamp('expires_at');               // 48h sau khi tạo
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['reference_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
