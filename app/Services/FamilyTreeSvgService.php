<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Str;

/**
 * Generates family tree SVG by:
 *  1. Loading Gia_Pha_3.svg background (stripping fixed placeholder slots)
 *  2. Computing dynamic positions for each couple-node
 *  3. Injecting boxes + connection lines at those positions
 */
class FamilyTreeSvgService
{
    // ── Base layout cho canvas 3508 × 2480 — scale tỉ lệ theo canvas_w mỗi template ──
    private const BASE_CANVAS_W = 3508.0;

    // Được set bởi initLayout() trước mỗi lần render
    private array $lyt = [];

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Generate low-quality PNG preview (for web display).
     * Renders SVG via Chromium screenshot so fonts & styles load correctly.
     * Result is cached by groupId+templateId+treeUpdatedAt — invalidated on tree edits.
     */
    public function generate(Collection $members, object $template): string
    {
        $groupId      = $members->first()?->family_group_id ?? 0;
        $treeUpdated  = $members->first()?->familyGroup?->tree_updated_at?->timestamp ?? 0;
        $cacheKey     = 'family-trees/preview-' . md5($groupId . ($template->id ?? '') . $treeUpdated) . '.png';

        if (Storage::disk('public')->exists($cacheKey)) {
            return Storage::disk('public')->url($cacheKey);
        }

        $svgContent = $this->buildSvgContent($members, $template);

        $pngRelPath = $this->svgToPreviewPng($svgContent, $cacheKey);

        return Storage::disk('public')->url($pngRelPath);
    }

    /** Return raw SVG string (for PDF pipeline). */
    public function generateContent(Collection $members, object $template): string
    {
        return $this->buildSvgContent($members, $template);
    }

    /**
     * Generate high-res PNG for export/sharing (2× DPI = 3508×2480px native).
     * Returns absolute file path to be streamed as download.
     */
    /**
     * Generate high-quality PNG for export/sharing.
     *
     * Dùng device-scale-factor=3 → output ≈ 3× viewport = ~3500×2480px sharp.
     * Thêm watermark APP_NAME nhỏ ở góc dưới phải.
     */
    public function generateExportPng(Collection $members, object $template): string
    {
        $svgContent = $this->buildSvgContent($members, $template);
        $svgContent = $this->addWatermark($svgContent, $template);

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uid      = \Illuminate\Support\Str::random(10);
        $htmlFile = $tempDir . "/export-{$uid}.html";
        $pngFile  = $tempDir . "/export-{$uid}.png";

        // Dùng viewport ÷ 3 + scale-factor=3 → output ở native canvas resolution (sắc nhất)
        $canvasW  = (int) ($template->canvas_w ?? 3508);
        $canvasH  = (int) ($template->canvas_h ?? 2480);
        $viewW    = (int) round($canvasW / 3);
        $viewH    = (int) round($canvasH / 3);

        $scaledSvg = preg_replace('/(<svg\b[^>]*?)\s+width="[^"]*"/', '$1 width="' . $viewW . '"', $svgContent, 1);
        $scaledSvg = preg_replace('/(<svg\b[^>]*?)\s+height="[^"]*"/', '$1 height="' . $viewH . '"', $scaledSvg, 1);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>html,body{margin:0;padding:0;width:' . $viewW . 'px;height:' . $viewH . 'px;overflow:hidden;background:#fff;}</style>'
            . '</head><body>' . $scaledSvg . '</body></html>';

        file_put_contents($htmlFile, $html);

        $cmd = sprintf(
            'chromium --headless --disable-gpu --no-sandbox --disable-setuid-sandbox --disable-dev-shm-usage'
            . ' --hide-scrollbars --device-scale-factor=3'
            . ' --screenshot=%s --window-size=%d,%d %s 2>&1',
            escapeshellarg($pngFile),
            $viewW, $viewH,
            escapeshellarg('file://' . $htmlFile),
        );
        exec($cmd, $output, $exitCode);
        @unlink($htmlFile);

        if ($exitCode !== 0 || !file_exists($pngFile)) {
            throw new \RuntimeException('Không thể tạo PNG: ' . implode("\n", $output));
        }

        return $pngFile;
    }

    /** Thêm watermark APP_NAME vào góc dưới phải của SVG. */
    private function addWatermark(string $svg, object $template): string
    {
        $appName  = config('app.name', 'Gia Phong');
        $canvasW  = (int) ($template->canvas_w ?? 3508);
        $canvasH  = (int) ($template->canvas_h ?? 2480);
        $fontSize = max(20, (int) round(22 * ($canvasW / 3508)));
        $margin   = max(20, (int) round(30 * ($canvasW / 3508)));

        $watermark = sprintf(
            '<text x="%d" y="%d" text-anchor="end"'
            . ' font-family="Arial,sans-serif" font-size="%d"'
            . ' fill="rgba(80,80,80,0.35)">%s</text>',
            $canvasW - $margin,
            $canvasH - $margin,
            $fontSize,
            htmlspecialchars($appName, ENT_XML1),
        );

        return str_replace('</svg>', $watermark . "\n</svg>", $svg);
    }

    // ── Orchestrator ──────────────────────────────────────────────────────────

    /**
     * Tính layout dựa trên canvas_w của template.
     * Template_2 (7022px) = 2× template_1 (3508px) → mọi kích thước scale ×2.
     */
    private function initLayout(object $template): void
    {
        $s = ((float) ($template->canvas_w ?? self::BASE_CANVAS_W)) / self::BASE_CANVAS_W;

        $bw = (int) round(184 * $s);
        $sg = (int) round(18  * $s);

        $this->lyt = [
            's'          => $s,
            'content_x'  => (int) round(350  * $s),
            'content_y'  => (int) round(560  * $s),
            'content_w'  => (int) round(2808 * $s),
            'content_h'  => (int) round(1770 * $s),
            'box_w'      => $bw,
            'box_h'      => (int) round(85   * $s),
            'spouse_gap' => $sg,
            'node_gap'   => (int) round(60   * $s),
            'gen_gap'    => (int) round(130  * $s),
            'couple_w'   => $bw * 2 + $sg,
        ];
    }

    private function buildSvgContent(Collection $members, object $template): string
    {
        $this->initLayout($template);

        $svgPath = isset($template->svg_path)
            ? base_path($template->svg_path)
            : base_path('vectors/Gia_Pha_3.svg');

        $svg = file_get_contents($svgPath);
        $svg = $this->stripFixedSlots($svg);
        $svg = $this->injectFontImport($svg);

        $groupId = $members->first()?->family_group_id;
        if (!$groupId || $members->isEmpty()) {
            return $svg;
        }

        $membersById = $members->keyBy('id');
        $memberIds   = $members->pluck('id')->toArray();

        // One query — load all relationships
        [$childrenMap, $parentMap, $spouseMap] = $this->loadRelationships($memberIds);

        // Identify the true family root (not a married-in spouse)
        $rootId = $this->findPrimaryRoot($memberIds, $parentMap, $spouseMap, $membersById);
        if (!$rootId) {
            return $svg;
        }

        // Build recursive couple-tree
        $visited   = [];
        $coupleTree = $this->buildCoupleTree($rootId, $visited, $spouseMap, $childrenMap, $membersById);
        if (empty($coupleTree)) {
            return $svg;
        }

        // Bottom-up: compute subtree width for each node
        $this->calcSubtreeWidth($coupleTree);

        // Top-down: assign (x, y) in local coordinates centred at x=0, y=0
        $this->assignPositions($coupleTree, 0, 0);

        // Compute scale so tree fits in content area
        $l      = $this->lyt;
        $treeW  = $coupleTree['width'];
        $depth  = $this->treeDepth($coupleTree);
        $treeH  = $depth * ($l['box_h'] + $l['gen_gap']) - $l['gen_gap'] + $l['box_h'];
        $scale  = min(1.0, $l['content_w'] / max($treeW, 1), $l['content_h'] / max($treeH, 1));

        // Origin: center the scaled tree horizontally inside content area
        $ox = $l['content_x'] + $l['content_w'] / 2;
        $oy = $l['content_y'];

        // Inject family title into scroll area
        $inject  = $this->renderTitle($template, $members);

        // Render tree (connections first so boxes overlay them)
        $inject .= '<g id="tree-connections">';
        $inject .= $this->renderConnections($coupleTree, $scale, $ox, $oy);
        $inject .= '</g>';

        $inject .= '<g id="tree-nodes">';
        $inject .= $this->renderNodes($coupleTree, $scale, $ox, $oy);
        $inject .= '</g>';

        return str_replace('</svg>', $inject . "\n</svg>", $svg);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    private function loadRelationships(array $memberIds): array
    {
        $rels = DB::table('family_relationships')
            ->where(fn ($q) => $q
                ->whereIn('member_id', $memberIds)
                ->orWhereIn('related_member_id', $memberIds)
            )->get();

        $childrenMap = [];
        $parentMap   = [];
        $spouseMap   = [];

        foreach ($rels as $r) {
            if ($r->type === 'parent_child') {
                $childrenMap[$r->member_id][]       = $r->related_member_id;
                $parentMap[$r->related_member_id][] = $r->member_id;
            } elseif ($r->type === 'spouse') {
                $spouseMap[$r->member_id][]       = $r->related_member_id;
                $spouseMap[$r->related_member_id][] = $r->member_id;
            }
        }

        return [$childrenMap, $parentMap, $spouseMap];
    }

    // ── Root detection ────────────────────────────────────────────────────────

    /**
     * True root = member with no parents who is NOT a spouse of someone
     * who has parents (i.e., not a "married-in" in-law).
     * Returns the male with the oldest birth_year among true roots.
     */
    private function findPrimaryRoot(
        array $memberIds,
        array $parentMap,
        array $spouseMap,
        Collection $membersById,
    ): ?int {
        $noParent = array_values(array_filter($memberIds, fn ($id) => empty($parentMap[$id])));
        $hasParent = array_values(array_filter($memberIds, fn ($id) => !empty($parentMap[$id])));

        // Married-in: no parents themselves, but spouse of someone who has parents
        $marriedIn = [];
        foreach ($hasParent as $id) {
            foreach ($spouseMap[$id] ?? [] as $sid) {
                if (in_array($sid, $noParent, true)) {
                    $marriedIn[] = $sid;
                }
            }
        }

        $trueRoots = array_values(array_diff($noParent, $marriedIn));

        // Fallback: if all no-parent members are married-in (unlikely), use all no-parent
        if (empty($trueRoots)) {
            $trueRoots = $noParent;
        }

        // Sort: prefer male, then oldest birth_year, then smallest id
        $sorted = collect($trueRoots)
            ->map(fn ($id) => $membersById->get($id))
            ->filter()
            ->sortBy([
                fn ($m) => $m->gender === 'male' ? 0 : 1,
                fn ($m) => $m->birth_year ?? 9999,
                fn ($m) => $m->id,
            ]);

        return $sorted->first()?->id;
    }

    // ── Tree building ─────────────────────────────────────────────────────────

    /**
     * Recursively build a couple-tree node:
     * ['husband'=>M, 'wife'=>M|null, 'children'=>[...], 'width'=>0, 'x'=>0, 'y'=>0]
     */
    private function buildCoupleTree(
        int $memberId,
        array &$visited,
        array $spouseMap,
        array $childrenMap,
        Collection $membersById,
    ): array {
        if (in_array($memberId, $visited, true) || !$membersById->has($memberId)) {
            return [];
        }
        $visited[] = $memberId;

        $member = $membersById->get($memberId);

        // Find first unvisited spouse
        $spouse = null;
        foreach ($spouseMap[$memberId] ?? [] as $sid) {
            if (!in_array($sid, $visited, true) && $membersById->has($sid)) {
                $spouse    = $membersById->get($sid);
                $visited[] = $sid;
                break;
            }
        }

        // Children of this couple
        $childIds = array_unique(array_merge(
            $childrenMap[$memberId] ?? [],
            $spouse ? ($childrenMap[$spouse->id] ?? []) : [],
        ));

        // Sort children by birth_year, then name
        $childIds = collect($childIds)
            ->map(fn ($cid) => $membersById->get($cid))
            ->filter()
            ->sortBy([
                fn ($m) => $m->birth_year ?? 9999,
                fn ($m) => $m->name ?? '',
            ])
            ->pluck('id')
            ->toArray();

        $children = [];
        foreach ($childIds as $cid) {
            if (!in_array($cid, $visited, true)) {
                $child = $this->buildCoupleTree($cid, $visited, $spouseMap, $childrenMap, $membersById);
                if (!empty($child)) {
                    $children[] = $child;
                }
            }
        }

        return [
            'husband'  => $member,
            'wife'     => $spouse,
            'children' => $children,
            'width'    => 0,
            'x'        => 0,
            'y'        => 0,
        ];
    }

    // ── Layout ────────────────────────────────────────────────────────────────

    /** Bottom-up: calculate subtree width using current $lyt units. */
    private function calcSubtreeWidth(array &$node): float
    {
        $l    = $this->lyt;
        $ownW = $node['wife'] ? $l['couple_w'] : $l['box_w'];

        if (empty($node['children'])) {
            $node['width'] = $ownW;
            return $ownW;
        }

        $childrenW = 0;
        foreach ($node['children'] as &$child) {
            $childrenW += $this->calcSubtreeWidth($child);
        }
        unset($child);
        $childrenW += (count($node['children']) - 1) * $l['node_gap'];

        $node['width'] = max($ownW, $childrenW);
        return $node['width'];
    }

    /** Top-down: assign (x, y) in local coords (root centered at x=0, y=0). */
    private function assignPositions(array &$node, float $cx, float $y): void
    {
        $l = $this->lyt;
        $node['x'] = $cx;
        $node['y'] = $y;

        if (empty($node['children'])) {
            return;
        }

        $totalChildW = array_sum(array_map(fn ($c) => $c['width'], $node['children']))
                     + (count($node['children']) - 1) * $l['node_gap'];

        $curX = $cx - $totalChildW / 2;
        foreach ($node['children'] as &$child) {
            $this->assignPositions($child, $curX + $child['width'] / 2, $y + $l['box_h'] + $l['gen_gap']);
            $curX += $child['width'] + $l['node_gap'];
        }
        unset($child);
    }

    /** Count maximum depth of the tree. */
    private function treeDepth(array $node): int
    {
        if (empty($node['children'])) {
            return 1;
        }
        return 1 + max(array_map([$this, 'treeDepth'], $node['children']));
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Title text uốn theo đường cong của ribbon cuộn.
     *
     * Dùng SVG <textPath> với quadratic bezier arc:
     *   - Hai đầu arc ở y≈318 (góc trái/phải ribbon)
     *   - Đỉnh arc ở y≈278 (giữa ribbon, cao hơn một chút)
     *   - startOffset="50%" + text-anchor="middle" trên <textPath> → tự căn giữa
     *   - SVG feDropShadow filter thay cho text-shadow
     */
    private function renderTitle(object $template, Collection $members): string
    {
        $name = optional($members->first()?->familyGroup)->name ?? '';
        if (!$name) {
            return '';
        }

        // ── Arc geometry — scale theo canvas size ────────────────────────────────
        // Base values cho Gia_Pha_3 (3508 × 2480).
        // Ribbon đỏ nằm giữa y≈230–380; title cần ở giữa ribbon → arcYPeak=315, arcY0=350.
        $s        = $this->lyt['s'];
        $arcX1    = 1100.0 * $s;
        $arcCX    = 1754.0 * $s;
        $arcX2    = 2408.0 * $s;
        $arcY0    = 350.0  * $s;   // y tại hai đầu (fix: hạ từ 318→350 so với version trước)
        $arcYPeak = 315.0  * $s;   // y tại đỉnh center (fix: hạ từ 278→315, căn giữa ribbon)

        // ── Font size tự động theo độ dài tên ────────────────────────────────────
        $maxSize   = (int) ($template->title_size ?? 48);
        $nameUpper = mb_strtoupper($name);
        $arcLen    = $arcX2 - $arcX1;                // ~1308px
        $size      = min($maxSize, (int) floor($arcLen / max(1, mb_strlen($nameUpper) * 0.60)));
        $size      = max(26, $size);

        $text   = htmlspecialchars($nameUpper, ENT_XML1);
        $pathId = 'ttl-curve';
        $filtId = 'ttl-shadow';

        // Quadratic bezier: M x1,y0 Q cx,yPeak x2,y0
        $d = sprintf(
            'M %.1f,%.1f Q %.1f,%.1f %.1f,%.1f',
            $arcX1, $arcY0,
            $arcCX, $arcYPeak,
            $arcX2, $arcY0,
        );

        // Defs: path + drop-shadow filter
        $defs = sprintf(
            '<defs>'
            . '<path id="%s" d="%s" fill="none"/>'
            . '<filter id="%s" x="-5%%" y="-40%%" width="110%%" height="180%%">'
            .   '<feDropShadow dx="2" dy="2" stdDeviation="2"'
            .   ' flood-color="#000000" flood-opacity="0.40"/>'
            . '</filter>'
            . '</defs>',
            $pathId, $d,
            $filtId,
        );

        // Title text theo arc — text-anchor="middle" trên <textPath> là đúng spec
        $titleText = sprintf(
            '<text font-family="\'Lora\',\'Georgia\',serif"'
            . ' font-size="%d" font-weight="700" font-style="italic"'
            . ' fill="#ffffff" letter-spacing="3"'
            . ' filter="url(#%s)">'
            . '<textPath href="#%s" startOffset="50%%" text-anchor="middle">%s</textPath>'
            . '</text>',
            $size, $filtId, $pathId, $text,
        );

        return '<g id="family-title">' . $defs . $titleText . '</g>';
    }

    /** Recursively render connection lines (parent→children T-bars, spouse dashes). */
    private function renderConnections(array $node, float $scale, float $ox, float $oy): string
    {
        $l   = $this->lyt;
        $out = '';

        // SVG positions for this couple
        $svgX = $node['x'] * $scale + $ox;  // center of couple unit
        $svgY = $node['y'] * $scale + $oy;  // top of boxes

        $scaledH = $l['box_h'] * $scale;
        $scaledW = $l['box_w'] * $scale;

        // Spouse connector (dashed horizontal)
        if ($node['wife']) {
            $hx1 = $svgX - ($l['spouse_gap'] / 2) * $scale;
            $hx2 = $svgX + ($l['spouse_gap'] / 2) * $scale;
            $hy  = $svgY + $scaledH / 2;
            $out .= sprintf(
                '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f"'
                . ' stroke="#b07d2a" stroke-width="%.1f" stroke-dasharray="%.0f %.0f"/>',
                $hx1, $hy, $hx2, $hy,
                max(1.5, 2.5 * $scale),
                max(5, 8 * $scale), max(3, 4 * $scale),
            );
        }

        // Parent → children T-bar
        if (!empty($node['children'])) {
            $parentCX = $svgX;
            $parentBY = $svgY + $scaledH;

            $childCXs = array_map(
                fn ($c) => $c['x'] * $scale + $ox,
                $node['children'],
            );
            $childTopY = $node['children'][0]['y'] * $scale + $oy;
            $midY      = ($parentBY + $childTopY) / 2;

            $lw = max(1.5, 2.5 * $scale);

            // Vertical drop from couple center
            $out .= sprintf(
                '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#b07d2a" stroke-width="%.1f"/>',
                $parentCX, $parentBY, $parentCX, $midY, $lw,
            );

            if (count($childCXs) > 1) {
                // Horizontal T-bar
                $out .= sprintf(
                    '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#b07d2a" stroke-width="%.1f"/>',
                    min($childCXs), $midY, max($childCXs), $midY, $lw,
                );
            }

            // Vertical rise to each child
            foreach ($childCXs as $ccx) {
                $out .= sprintf(
                    '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#b07d2a" stroke-width="%.1f"/>',
                    $ccx, $midY, $ccx, $childTopY, $lw,
                );
            }

            // Recurse
            foreach ($node['children'] as $child) {
                $out .= $this->renderConnections($child, $scale, $ox, $oy);
            }
        }

        return $out;
    }

    /** Recursively render member boxes. */
    private function renderNodes(array $node, float $scale, float $ox, float $oy): string
    {
        $l = $this->lyt;
        $out = '';

        $svgX    = $node['x'] * $scale + $ox;
        $svgY    = $node['y'] * $scale + $oy;
        $scaledW = $l['box_w'] * $scale;
        $scaledH = $l['box_h'] * $scale;

        // Husband box (left of center)
        $husbandX = $svgX - ($node['wife'] ? ($l['box_w'] + $l['spouse_gap'] / 2) * $scale : $scaledW / 2);
        $out .= $this->renderBox($node['husband'], $husbandX, $svgY, $scaledW, $scaledH);

        // Wife box (right of center)
        if ($node['wife']) {
            $wifeX = $svgX + ($l['spouse_gap'] / 2) * $scale;
            $out .= $this->renderBox($node['wife'], $wifeX, $svgY, $scaledW, $scaledH);
        }

        foreach ($node['children'] as $child) {
            $out .= $this->renderNodes($child, $scale, $ox, $oy);
        }

        return $out;
    }

    private function renderBox(FamilyMember $member, float $bx, float $by, float $bw, float $bh): string
    {
        // Font sizes adapt to scaled box width
        [$nameSize, $subSize, $lineGap] = $this->fontSizes($bw);

        $pronoun  = htmlspecialchars(trim($member->pronoun ?? $member->relationship ?? ''), ENT_XML1);
        $name     = htmlspecialchars($member->name ?? '', ENT_XML1);
        $lifespan = htmlspecialchars($this->lifespanText($member), ENT_XML1);

        // Shrink name for long strings
        $nameLen = mb_strlen($member->name ?? '');
        if ($nameLen > 16) {
            $nameSize = (int) round($nameSize * 0.78);
        } elseif ($nameLen > 11) {
            $nameSize = (int) round($nameSize * 0.88);
        }

        // Build lines
        $lines = [];
        if ($pronoun !== '') {
            $lines[] = ['t' => $pronoun,  's' => $subSize,  'f' => '#7a5c1e', 'b' => false];
        }
        $lines[] = ['t' => $name, 's' => $nameSize, 'f' => '#1a1a1a', 'b' => true];
        if ($lifespan !== '') {
            $lines[] = ['t' => $lifespan, 's' => $subSize,  'f' => '#593f8b', 'b' => false];
        }

        $cx     = $bx + $bw / 2;
        $cy     = $by + $bh / 2;
        $n      = count($lines);
        $startY = $cy - (($n - 1) * $lineGap) / 2;

        $pad = max(3, 5 * ($bw / ($this->lyt['box_w'] ?: 184)));  // scale padding with box size
        $out = sprintf(
            '<g id="m-%d">'
            . '<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="rgba(255,255,255,0.90)" rx="3"/>'
            // Box frame path (box.svg design), scaled and translated
            . '<g transform="translate(%.1f,%.1f) scale(%.4f)">'
            . '<path fill="none" stroke="#ff7d24" stroke-miterlimit="100" stroke-width="4.2"'
            . ' d="m10.4 4.5h163.9q0 0.1 0 0.3c0 3.9 2.4 7.1 5.5 7.3v60.3'
            . 'c-3.1 0.2-5.5 3.4-5.5 7.3q0 0.3 0 0.5h-163.9q0-0.2 0-0.5'
            . 'c0-4-2.5-7.3-5.7-7.3v-60.3c3.2 0 5.7-3.3 5.7-7.3q0-0.2 0-0.3z"/>'
            . '</g>',
            $member->id,
            $bx + $pad, $by + $pad, $bw - $pad * 2, $bh - $pad * 2,
            $bx, $by, $bw / ($this->lyt['box_w'] ?: 184),
        );

        foreach ($lines as $i => $line) {
            $weight  = $line['b'] ? ' font-weight="bold"' : '';
            $out .= sprintf(
                '<text x="%.1f" y="%.1f" text-anchor="middle" dominant-baseline="middle"'
                . ' font-family="Arial,Tahoma,sans-serif" font-size="%d" fill="%s"%s>%s</text>',
                $cx, $startY + $i * $lineGap,
                $line['s'], $line['f'], $weight, $line['t'],
            );
        }

        $out .= '</g>';
        return $out;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Inject calligraphy font import into SVG so title text can use it.
     *
     * Idempotent: if the import is already present, keep SVG unchanged.
     */
    private function injectFontImport(string $svg): string
    {
        $fontImport = "@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@700&display=swap');";
        if (str_contains($svg, $fontImport)) {
            return $svg;
        }

        $styleBlock = '<style type="text/css"><![CDATA[' . $fontImport . ']]></style>';

        if (preg_match('/<defs\b[^>]*>/i', $svg, $m, PREG_OFFSET_CAPTURE) === 1) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            return substr($svg, 0, $insertAt) . $styleBlock . substr($svg, $insertAt);
        }

        // Fallback for uncommon SVGs without <defs>
        if (preg_match('/<svg\b[^>]*>/i', $svg, $m, PREG_OFFSET_CAPTURE) === 1) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            return substr($svg, 0, $insertAt) . $styleBlock . substr($svg, $insertAt);
        }

        return $svg;
    }

    /**
     * Render SVG → PNG preview via Chromium screenshot.
     *
     * Vấn đề với CSS override: SVG có width="3508" height="2480" là presentation attributes,
     * CSS `svg { width: Xpx }` không luôn override được trong mọi trường hợp.
     * Fix: thay trực tiếp attribute trên thẻ <svg> trước khi render.
     *
     * Output: 1170 × 828 px (A4 landscape ratio 297:210, ≈1/3 scale gốc).
     */
    private function svgToPreviewPng(string $svgContent, string $publicRelPath): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Thay width/height trực tiếp trên thẻ <svg> — giữ nguyên viewBox để scale đúng
        $previewW  = 1170;
        $previewH  = 828;  // 1170 × (2480 / 3508) ≈ 827.8 → làm tròn 828
        $scaledSvg = preg_replace('/(<svg\b[^>]*?)\s+width="[^"]*"/', '$1 width="' . $previewW . '"', $svgContent, 1);
        $scaledSvg = preg_replace('/(<svg\b[^>]*?)\s+height="[^"]*"/', '$1 height="' . $previewH . '"', $scaledSvg, 1);

        $uid      = Str::random(10);
        $htmlFile = $tempDir . "/prev-{$uid}.html";
        $pngFile  = $tempDir . "/prev-{$uid}.png";

        // Inline SVG trong HTML để Chromium load được Google Fonts
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>html,body{margin:0;padding:0;width:' . $previewW . 'px;height:' . $previewH . 'px;overflow:hidden;background:#fff;}</style>'
            . '</head><body>' . $scaledSvg . '</body></html>';

        file_put_contents($htmlFile, $html);

        $cmd = sprintf(
            'chromium --headless --disable-gpu --no-sandbox --disable-setuid-sandbox --disable-dev-shm-usage --hide-scrollbars'
            . ' --screenshot=%s --window-size=%d,%d %s 2>&1',
            escapeshellarg($pngFile),
            $previewW, $previewH,
            escapeshellarg('file://' . $htmlFile),
        );
        exec($cmd, $output, $exitCode);

        @unlink($htmlFile);

        if ($exitCode !== 0 || !file_exists($pngFile)) {
            // Fallback: lưu SVG nếu Chromium thất bại
            $fallback = str_replace('.png', '.svg', $publicRelPath);
            Storage::disk('public')->put($fallback, $svgContent);
            @unlink($pngFile);
            return $fallback;
        }

        Storage::disk('public')->put($publicRelPath, file_get_contents($pngFile));
        @unlink($pngFile);

        return $publicRelPath;
    }

    /** Remove the pre-positioned placeholder frame paths (Objects 2–72). */
    private function stripFixedSlots(string $svg): string
    {
        for ($i = 2; $i <= 72; $i++) {
            // Matches self-closing <path ... id="Object N" .../>
            $svg = preg_replace(
                '/<path[^>]+id="Object ' . $i . '"[^>]*\/>/',
                '',
                $svg,
            );
        }
        return $svg;
    }


    /** [nameSize, subSize, lineGap] based on rendered box width (px). */
    private function fontSizes(float $w): array
    {
        if ($w >= 160) return [16, 11, 14];
        if ($w >= 130) return [14, 10, 13];
        if ($w >= 100) return [12,  9, 11];
        return [10, 8, 10];
    }

    private function lifespanText(FamilyMember $m): string
    {
        $alive = $m->is_alive ?? ($m->death_year === null && $m->death_day === null);

        if ($alive) {
            // Người sống: chỉ hiển thị năm sinh
            return $m->birth_year ? (string) $m->birth_year : '';
        }

        // Người mất: ưu tiên ngày giỗ (day/month)
        if ($m->death_day && $m->death_month) {
            $suffix = ($m->death_date_type === 'lunar') ? ' ÂL' : '';
            return 'Giỗ ' . $m->death_day . '/' . $m->death_month . $suffix;
        }

        // Fallback: khoảng năm sinh–mất
        if ($m->birth_year && $m->death_year) {
            return $m->birth_year . '–' . $m->death_year;
        }
        if ($m->birth_year) {
            return $m->birth_year . ' (†)';
        }

        return '†';
    }
}
