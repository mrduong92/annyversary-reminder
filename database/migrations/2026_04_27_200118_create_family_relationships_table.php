<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_relationships', function (Blueprint $table) {
            $table->id();
            // parent_child: member_id = cha/mẹ, related_member_id = con
            // spouse: bidirectional (chỉ lưu 1 chiều, query 2 chiều)
            $table->foreignId('member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('related_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->enum('type', ['parent_child', 'spouse']);
            $table->timestamps();

            $table->unique(['member_id', 'related_member_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_relationships');
    }
};
