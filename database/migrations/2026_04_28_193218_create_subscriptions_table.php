<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);           // free | mini | premium
            $table->decimal('amount', 10, 0)->default(0); // VND
            $table->string('note')->nullable();    // ghi chú khi kích hoạt thủ công
            $table->string('activated_by')->nullable(); // email admin hoặc 'system'
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
