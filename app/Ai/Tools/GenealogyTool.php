<?php

namespace App\Ai\Tools;

use App\Services\FamilyTreeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GenealogyTool implements Tool
{
    public function __construct(
        private readonly int $familyGroupId,
        private readonly FamilyTreeService $treeService,
    ) {}

    public function description(): string
    {
        return 'Truy vấn thông tin gia phả: danh sách thành viên, quan hệ huyết thống, con cháu, cha mẹ, vợ chồng.';
    }

    public function handle(Request $request): string
    {
        $summary = $this->treeService->toText($this->familyGroupId);

        if (empty($summary)) {
            return 'Gia phả chưa có thành viên nào. Hãy thêm thành viên tại trang Gia Phả.';
        }

        return "DANH SÁCH THÀNH VIÊN GIA PHẢ:\n{$summary}";
    }

    public function schema(JsonSchema $schema): array
    {
        // Tool này không cần tham số — luôn trả về toàn bộ gia phả
        return [];
    }
}
