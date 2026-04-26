<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_recipient', function (Blueprint $table) {
            $table->foreignId('memorial_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained()->cascadeOnDelete();
            $table->json('notify_days_before')->nullable(); // override per-link; null = dùng default của recipient
            $table->primary(['memorial_event_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_recipient');
    }
};
