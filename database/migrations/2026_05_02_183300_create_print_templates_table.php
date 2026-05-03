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
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('preview_image')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Design settings
            $table->integer('width')->default(1200);
            $table->integer('height')->default(800);
            $table->integer('padding')->default(50);
            $table->boolean('show_title')->default(true);
            $table->string('title_text')->nullable();
            $table->boolean('show_footer')->default(true);
            $table->string('footer_text')->nullable();
            $table->string('background_pattern')->nullable();
            $table->string('view_name')->nullable(); // Thêm trường này để mapping với Blade view
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
