<?php

namespace App\Ai\Tools;

use App\Models\FamilyDocument;
use App\Services\AI\RagService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FamilyMemoryTool implements Tool
{
    public function __construct(
        private readonly int $userId,
        private readonly ?int $familyGroupId = null,
    ) {}

    public function description(): string
    {
        return 'Tìm kiếm thông tin trong tài liệu và nhật ký gia đình đã upload. Dùng khi người dùng hỏi về kỷ niệm, lịch sử gia đình, sở thích của người thân.';
    }

    public function handle(Request $request): string
    {
        $query = (string) $request->string('query');

        if (! $query) {
            return 'Vui lòng cung cấp câu hỏi cụ thể.';
        }

        if (! $this->familyGroupId) {
            return 'Không xác định được nhóm gia đình.';
        }

        $hasDocuments = FamilyDocument::where('family_group_id', $this->familyGroupId)
            ->where('status', 'ready')
            ->exists();

        if (! $hasDocuments) {
            return 'Gia đình chưa upload tài liệu nào. Bạn có thể thêm tài liệu trong mục "Tài liệu gia đình" trên trang Chat AI.';
        }

        $context = app(RagService::class)->search($query, $this->familyGroupId);

        if (empty($context)) {
            return "Không tìm thấy thông tin liên quan đến \"{$query}\" trong tài liệu gia đình.";
        }

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
