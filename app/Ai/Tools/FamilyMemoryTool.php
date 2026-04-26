<?php

namespace App\Ai\Tools;

use App\Models\DocumentChunk;
use App\Models\FamilyDocument;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FamilyMemoryTool implements Tool
{
    public function __construct(private readonly int $userId) {}

    public function description(): string
    {
        return 'Tìm kiếm thông tin trong tài liệu và nhật ký gia đình đã upload. Dùng khi người dùng hỏi về kỷ niệm, lịch sử gia đình, sở thích của người thân.';
    }

    public function handle(Request $request): string
    {
        $query = $request->string('query');

        if (! $query) return 'Vui lòng cung cấp câu hỏi cụ thể.';

        // Lấy document IDs của user
        $docIds = FamilyDocument::where('user_id', $this->userId)
            ->where('status', 'ready')
            ->pluck('id');

        if ($docIds->isEmpty()) {
            return 'Gia đình chưa upload tài liệu nào. Bạn có thể thêm tài liệu trong mục "Tài liệu gia đình".';
        }

        // MySQL FULLTEXT search trên document_chunks (MVP — sẽ nâng lên vector search sau)
        $chunks = DocumentChunk::whereIn('document_id', $docIds)
            ->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$query])
            ->select('content', DB::raw('MATCH(content) AGAINST(? IN BOOLEAN MODE) AS relevance'), 'document_id')
            ->addBinding($query, 'select')
            ->orderByDesc('relevance')
            ->limit(5)
            ->get();

        // Fallback: LIKE search nếu FULLTEXT không có kết quả
        if ($chunks->isEmpty()) {
            $chunks = DocumentChunk::whereIn('document_id', $docIds)
                ->where('content', 'like', '%' . $query . '%')
                ->limit(5)
                ->get();
        }

        if ($chunks->isEmpty()) {
            return "Không tìm thấy thông tin liên quan đến \"{$query}\" trong tài liệu gia đình.";
        }

        $context = $chunks->pluck('content')->implode("\n\n---\n\n");

        return "Tìm thấy các đoạn liên quan từ tài liệu gia đình:\n\n{$context}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Câu hỏi hoặc từ khóa tìm kiếm trong tài liệu gia đình (VD: "ông nội thích ăn gì", "lịch sử dòng họ")')
                ->required(),
        ];
    }
}
