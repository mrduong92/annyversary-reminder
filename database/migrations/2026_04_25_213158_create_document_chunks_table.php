<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enable pgvector extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('family_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('chunk_index');
            $table->text('content');
            $table->timestamps();
        });

        // 768 dims = gemini-embedding-001 với outputDimensionality=768
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(768)');

        // IVFFlat index (max 2000 dims) cho cosine similarity search
        DB::statement('CREATE INDEX document_chunks_embedding_idx ON document_chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 10)');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
