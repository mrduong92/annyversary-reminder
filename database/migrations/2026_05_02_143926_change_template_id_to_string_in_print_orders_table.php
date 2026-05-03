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
            $table->dropForeign(['template_id']);
            $table->string('template_id')->change();
        });

        Schema::dropIfExists('print_templates');
    }

    public function down(): void
    {
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('print_orders', function (Blueprint $table) {
            // Need a cast to integer, usually difficult to reverse string -> FK
            // In dev environment it's fine
            $table->integer('template_id')->change();
            $table->foreign('template_id')->references('id')->on('print_templates');
        });
    }
};
