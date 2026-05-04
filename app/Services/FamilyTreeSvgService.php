<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Generates family tree SVG by:
 *  1. Loading Gia_Pha_3.svg background (stripping fixed placeholder slots)
 *  2. Computing dynamic positions for each couple-node
 *  3. Injecting boxes + connection lines at those positions
 */
class FamilyTreeSvgService
{
    // ── Layout constants (SVG coordinate space: 3508 × 2480) ──────────────────
    private const CONTENT_X  = 350;   // left bound of tree area
    private const CONTENT_Y  = 560;   // top bound (below title scroll, ~y=550)
    private const CONTENT_W  = 2808;  // 3508 - 2*350
    private const CONTENT_H  = 1770;  // 2480 - 560 - 150 (bottom margin)

    private const BOX_W      = 184;
    private const BOX_H      = 85;
    private const SPOUSE_GAP = 18;    // gap between husband and wife boxes
    private const NODE_GAP   = 60;    // gap between sibling couple-units
    private const GEN_GAP    = 130;   // vertical gap between generations

    private const COUPLE_W   = self::BOX_W * 2 + self::SPOUSE_GAP; // 386

    // ── Public API ─────────────────────────────────────────────────────────────

    /** Generate SVG, store to public disk, return URL (for web preview). */
    public function generate(Collection $members, object $template): string
    {
        $content  = $this->buildSvgContent($members, $template);
        $groupId  = $members->first()?->family_group_id ?? 0;
        $filename = 'family-trees/preview-' . md5($groupId . ($template->id ?? '')) . '.svg';
        Storage::disk('public')->put($filename, $content);
        return Storage::disk('public')->url($filename);
    }

    /** Return raw SVG string (for PDF pipeline). */
    public function generateContent(Collection $members, object $template): string
    {
        return $this->buildSvgContent($members, $template);
    }

    // ── Orchestrator ──────────────────────────────────────────────────────────

    private function buildSvgContent(Collection $members, object $template): string
    {
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
        $treeW  = $coupleTree['width'];
        $depth  = $this->treeDepth($coupleTree);
        $treeH  = $depth * (self::BOX_H + self::GEN_GAP) - self::GEN_GAP + self::BOX_H;
        $scale  = min(1.0, self::CONTENT_W / max($treeW, 1), self::CONTENT_H / max($treeH, 1));

        // Origin: center the scaled tree horizontally inside content area
        $ox = self::CONTENT_X + self::CONTENT_W / 2;  // horizontal center (local x=0 maps here)
        $oy = self::CONTENT_Y;

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

    /** Bottom-up: calculate subtree width in abstract units. */
    private function calcSubtreeWidth(array &$node): float
    {
        $ownW = $node['wife'] ? self::COUPLE_W : self::BOX_W;

        if (empty($node['children'])) {
            $node['width'] = $ownW;
            return $ownW;
        }

        $childrenW = 0;
        foreach ($node['children'] as &$child) {
            $childrenW += $this->calcSubtreeWidth($child);
        }
        unset($child);
        $childrenW += (count($node['children']) - 1) * self::NODE_GAP;

        $node['width'] = max($ownW, $childrenW);
        return $node['width'];
    }

    /** Top-down: assign (x, y) in local coords (root centered at x=0, y=0). */
    private function assignPositions(array &$node, float $cx, float $y): void
    {
        $node['x'] = $cx;
        $node['y'] = $y;

        if (empty($node['children'])) {
            return;
        }

        $totalChildW = array_sum(array_map(fn ($c) => $c['width'], $node['children']))
                     + (count($node['children']) - 1) * self::NODE_GAP;

        $curX = $cx - $totalChildW / 2;
        foreach ($node['children'] as &$child) {
            $this->assignPositions($child, $curX + $child['width'] / 2, $y + self::BOX_H + self::GEN_GAP);
            $curX += $child['width'] + self::NODE_GAP;
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
     * Title text overlaid on the scroll banner.
     * Uses Lora italic bold (thư pháp-style) from Google Fonts.
     * Auto-sizes so the text never overflows the scroll width (~820px).
     */
    private function renderTitle(object $template, Collection $members): string
    {
        $name = optional($members->first()?->familyGroup)->name ?? '';
        if (!$name) {
            return '';
        }

        // Scroll ribbon center: Object 468 starts at x=1744, ribbon spans ≈820px wide
        $cx      = (float) ($template->title_cx   ?? 1754);
        $cy      = (float) ($template->title_cy   ?? 295);   // center of red ribbon
        $maxSize = (int)   ($template->title_size ?? 48);

        // Each bold italic char ≈ 0.58× font-size wide
        $nameUpper  = mb_strtoupper($name);
        $charCount  = mb_strlen($nameUpper);
        $maxWidth   = 820;
        $size       = min($maxSize, (int) floor($maxWidth / max(1, $charCount * 0.58)));
        $size       = max(24, $size);

        $textNode = htmlspecialchars($nameUpper, ENT_XML1);

        // Drop-shadow để chữ nổi trên nền cuộn
        $shadow = sprintf(
            '<text x="%.1f" y="%.1f" text-anchor="middle" dominant-baseline="central"'
            . ' font-family="\'Lora\',\'IM Fell English\',\'Georgia\',serif"'
            . ' font-size="%d" font-weight="700" font-style="italic"'
            . ' fill="rgba(0,0,0,0.35)" letter-spacing="3"'
            . ' dx="2" dy="2">%s</text>',
            $cx, $cy, $size, $textNode,
        );
        $main = sprintf(
            '<text x="%.1f" y="%.1f" text-anchor="middle" dominant-baseline="central"'
            . ' font-family="\'Lora\',\'IM Fell English\',\'Georgia\',serif"'
            . ' font-size="%d" font-weight="700" font-style="italic"'
            . ' fill="#ffffff" letter-spacing="3">%s</text>',
            $cx, $cy, $size, $textNode,
        );

        return '<g id="family-title">' . $shadow . $main . '</g>';
    }

    /** Recursively render connection lines (parent→children T-bars, spouse dashes). */
    private function renderConnections(array $node, float $scale, float $ox, float $oy): string
    {
        $out = '';

        // SVG positions for this couple
        $svgX = $node['x'] * $scale + $ox;  // center of couple unit
        $svgY = $node['y'] * $scale + $oy;  // top of boxes

        $scaledH = self::BOX_H * $scale;
        $scaledW = self::BOX_W * $scale;

        // Spouse connector (dashed horizontal)
        if ($node['wife']) {
            $hx1 = $svgX - (self::SPOUSE_GAP / 2) * $scale;
            $hx2 = $svgX + (self::SPOUSE_GAP / 2) * $scale;
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
        $out = '';

        $svgX    = $node['x'] * $scale + $ox;
        $svgY    = $node['y'] * $scale + $oy;
        $scaledW = self::BOX_W * $scale;
        $scaledH = self::BOX_H * $scale;

        // Husband box (left of center)
        $husbandX = $svgX - ($node['wife'] ? (self::BOX_W + self::SPOUSE_GAP / 2) * $scale : $scaledW / 2);
        $out .= $this->renderBox($node['husband'], $husbandX, $svgY, $scaledW, $scaledH);

        // Wife box (right of center)
        if ($node['wife']) {
            $wifeX = $svgX + (self::SPOUSE_GAP / 2) * $scale;
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

        $pad = max(3, 5 * ($bw / self::BOX_W));  // scale padding with box size
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
            $bx, $by, $bw / self::BOX_W,
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
