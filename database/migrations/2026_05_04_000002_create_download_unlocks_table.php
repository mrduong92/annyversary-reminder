<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_group_id')->constrained()->cascadeOnDelete();
            // Snapshot tree_updated_at tại thời điểm unlock — dùng để phát hiện cây đã bị sửa sau khi unlock
            $table->timestamp('tree_snapshot_at')->nullable();
            $table->string('template_id')->nullable();
            // Đường dẫn relative trên public disk, ví dụ: download-unlocks/gia-pha-xxx.pdf
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'active'])->default('active');
            $table->integer('amount')->default(49000);
            $table->text('payment_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_unlocks');
    }
};
