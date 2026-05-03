<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Facades\Storage;

class FamilyTreeSvgService
{
    /**
     * Generate SVG từ data gia phả
     */
    public function generate($members, $template): string
    {
        if (isset($template->view_name) && !empty($template->view_name)) {
            // Dùng thuật toán tính toán vị trí hiện tại
            $roots = $this->findRoots($members);
            $width = $template->width ?? 1200;
            $padding = $template->padding ?? 50;
            $treeData = [];
            
            if (!$roots->isEmpty()) {
                $treeData = $this->calculateTreePositions($roots, $width, $padding);
            }

            $svg = view($template->view_name, compact('members', 'treeData', 'template'))->render();
        } else {
            $svg = $this->buildSvg($members, $template);
        }

        $filename = 'family-tree-' . time() . '.svg';

        // Lưu SVG vào storage
        Storage::disk('public')->put('family-trees/' . $filename, $svg);

        return Storage::disk('public')->url('family-trees/' . $filename);
    }

    /**
     * Xây build SVG từ data gia phả
     */
    private function buildSvg($members, $template): string
    {
        $width = $template->width ?? 1200;
        $height = $template->height ?? 800;
        $padding = $template->padding ?? 50;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';

        // Background pattern
        if ($template->background_pattern) {
            $svg .= $this->addBackgroundPattern($template->background_pattern);
        }

        // Title
        if ($template->show_title) {
            $svg .= $this->addTitle($template->title_text ?? 'Gia Phả', $width, $padding);
        }

        // Build tree
        $svg .= $this->buildTree($members, $width, $padding);

        // Footer
        if ($template->show_footer) {
            $svg .= $this->addFooter($template->footer_text ?? '', $width, $height, $padding);
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Build cây gia phả từ data
     */
    private function buildTree($members, int $width, int $padding): string
    {
        // Tìm root node (không có cha mẹ)
        $roots = $this->findRoots($members);

        if ($roots->isEmpty()) {
            return '';
        }

        // Tính toán vị trí
        $treeData = $this->calculateTreePositions($roots, $width, $padding);

        return $this->renderTreeNodes($treeData);
    }

    /**
     * Tìm các root node (không có cha mẹ)
     */
    private function findRoots($members): \Illuminate\Support\Collection
    {
        $memberIds = collect($members)->pluck('id')->toArray();
        $parentIds = collect($members)->pluck('father_id', 'mother_id')
            ->filter()
            ->flatten()
            ->unique()
            ->toArray();

        return collect($members)
            ->whereIn('id', $memberIds)
            ->whereNotIn('id', $parentIds)
            ->values();
    }

    /**
     * Tính toán vị trí các node trong cây
     */
    private function calculateTreePositions(\Illuminate\Support\Collection $roots, int $width, int $padding): array
    {
        $treeData = [];

        foreach ($roots as $root) {
            $this->calculateNodePositions($root, $width, $padding, 0, 0, $treeData);
        }

        return $treeData;
    }

    /**
     * Tính toán vị trí node và các con
     */
    private function calculateNodePositions(
        $node,
        int $width,
        int $padding,
        int $level,
        int $index,
        array &$treeData
    ): void {
        $nodeData = [
            'member' => $node,
            'x' => 0,
            'y' => 0,
            'width' => 0,
            'height' => 0,
            'children' => [],
        ];

        // Tính toán vị trí
        $nodeWidth = 120;
        $nodeHeight = 80;
        $horizontalGap = 40;
        $verticalGap = 60;

        // Vị trí đơn giản: level -> y, index -> x
        // (Thực tế thuật toán xịn sẽ cần đệ quy post-order, nhưng ta làm đơn giản trước)
        $nodeX = $padding + ($index * ($nodeWidth + $horizontalGap));
        $nodeY = $padding + ($level * ($nodeHeight + $verticalGap));

        $nodeData['x'] = $nodeX;
        $nodeData['y'] = $nodeY;
        $nodeData['width'] = $nodeWidth;
        $nodeData['height'] = $nodeHeight;

        // Xử lý các con
        $children = $node->children();
        foreach ($children as $i => $child) {
            $this->calculateNodePositions($child, $width, $padding, $level + 1, $i, $nodeData['children']);
        }

        $treeData[] = $nodeData;
    }

    /**
     * Render các node thành SVG
     */
    private function renderTreeNodes(array $treeData): string
    {
        $svg = '';

        foreach ($treeData as $data) {
            $svg .= $this->renderNode($data['member'], $data['x'], $data['y']);
            $svg .= $this->renderConnections($data);
        }

        return $svg;
    }

    /**
     * Render một node thành SVG
     */
    private function renderNode(FamilyMember $member, int $x, int $y): string
    {
        $width = 120;
        $height = 80;
        $radius = 10;

        $svg = '<g transform="translate(' . $x . ', ' . $y . ')">';

        // Card background
        $svg .= '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" rx="' . $radius . '" fill="#ffffff" stroke="#e5e7eb" stroke-width="1"/>';

        // Avatar placeholder
        $svg .= '<circle cx="' . ($width / 2) . '" cy="' . ($height / 2) . '" r="25" fill="#f3f4f6"/>';

        // Name
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($height / 2 - 5) . '" text-anchor="middle" font-size="12" font-weight="600" fill="#1f2937">' . e($member->name) . '</text>';

        // Lifespan
        if ($member->lifespan()) {
            $svg .= '<text x="' . ($width / 2) . '" y="' . ($height / 2 + 10) . '" text-anchor="middle" font-size="10" fill="#6b7280">' . e($member->lifespan()) . '</text>';
        }

        // Relationship
        if ($member->relationship) {
            $svg .= '<text x="' . ($width / 2) . '" y="' . ($height / 2 + 25) . '" text-anchor="middle" font-size="10" fill="#9ca3af">' . e($member->relationship) . '</text>';
        }

        // Death year
        if ($member->death_year) {
            $svg .= '<text x="' . ($width / 2) . '" y="' . ($height / 2 + 40) . '" text-anchor="middle" font-size="10" fill="#ef4444">' . e($member->death_year) . '</text>';
        }

        $svg .= '</g>';

        return $svg;
    }

    /**
     * Render các đường kết nối
     */
    private function renderConnections(array $data): string
    {
        $svg = '';

        foreach ($data['children'] as $child) {
            $parentX = $data['x'] + ($data['width'] / 2);
            $parentY = $data['y'] + ($data['height'] / 2);
            $childX = $child['x'] + ($child['width'] / 2);
            $childY = $child['y'] + ($child['height'] / 2);

            $svg .= '<line x1="' . $parentX . '" y1="' . $parentY . '" x2="' . $childX . '" y2="' . $childY . '" stroke="#9ca3af" stroke-width="2" stroke-linecap="round"/>';

            $svg .= $this->renderConnections($child);
        }

        return $svg;
    }

    /**
     * Thêm background pattern
     */
    private function addBackgroundPattern(string $pattern): string
    {
        return '<pattern id="bg-pattern" width="100" height="100" patternUnits="userSpaceOnUse" x="0" y="0">
            <image href="' . $pattern . '" width="100" height="100"/>
        </pattern>';
    }

    /**
     * Thêm tiêu đề
     */
    private function addTitle(string $text, int $width, int $padding): string
    {
        return '<text x="' . ($width / 2) . '" y="' . ($padding / 2 + 10) . '" text-anchor="middle" font-size="24" font-weight="bold" fill="#1f2937">' . htmlspecialchars($text) . '</text>';
    }

    /**
     * Thêm footer
     */
    private function addFooter(string $text, int $width, int $height, int $padding): string
    {
        return '<text x="' . ($width / 2) . '" y="' . ($height - $padding / 2) . '" text-anchor="middle" font-size="14" fill="#6b7280">' . htmlspecialchars($text) . '</text>';
    }
}
