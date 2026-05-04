@php
/**
 * Template "Mẫu Truyền Thống" — A0 landscape (3508 × 2480)
 * Nhận: $members (Collection<FamilyMember>), $treeData (unused), $template (array/object)
 */

$groupId = $members->first()?->family_group_id;

// ── 1. Build tree dùng FamilyTreeService (proven, tránh raw-DB trong blade) ──
/** @var \App\Services\FamilyTreeService $treeService */
$treeService = app(\App\Services\FamilyTreeService::class);

$roots   = $groupId ? $treeService->roots($groupId) : collect();
$visited = [];
$trees   = $roots->map(fn ($r) => $treeService->buildTree($r, 0, 10, $visited))
                 ->filter()->values();

// ── 2. BFS → generations[gen_num] = [[member, spouse|null], ...] ────────────
$generations  = [];  // gen => [[member, spouse|null], ...]
$processedIds = [];
$bfsQueue     = [];  // [[node, gen], ...]

foreach ($trees as $tree) {
    $bfsQueue[] = [$tree, 0];
}

while (!empty($bfsQueue)) {
    [$node, $gen] = array_shift($bfsQueue);
    $mid = $node['member']->id;

    if (in_array($mid, $processedIds, true)) {
        continue;
    }
    $processedIds[] = $mid;

    // Tìm spouse chưa xử lý
    $spouse = $node['spouses']->first(fn ($s) => !in_array($s->id, $processedIds, true));
    if ($spouse) {
        $processedIds[] = $spouse->id;
    }

    $generations[$gen][] = ['member' => $node['member'], 'spouse' => $spouse];

    foreach ($node['children'] as $child) {
        if (!in_array($child['member']->id, $processedIds, true)) {
            $bfsQueue[] = [$child, $gen + 1];
        }
    }
}

// ── 3. Layout constants ──────────────────────────────────────────────────────
$BOX_W      = 184;
$BOX_H      = 85;
$SPOUSE_GAP = 20;   // khoảng giữa 2 vợ chồng
$GEN_V_GAP  = 140;  // khoảng dọc giữa các thế hệ

$CONTENT_X  = 380;
$CONTENT_Y  = 420;  // bắt đầu dưới title scroll
$CONTENT_W  = 2748; // 3508 - 2*380

// Tính NODE_GAP adaptive — dựa trên hàng có nhiều cặp nhất
$maxCouplesInRow = max(1, ...array_map('count', $generations ?: [[null]]));
$COUPLE_W  = $BOX_W * 2 + $SPOUSE_GAP; // 388px khi có spouse
$nodeGap   = max(20, (int) floor(($CONTENT_W - $maxCouplesInRow * $COUPLE_W) / max(1, $maxCouplesInRow)));
$nodeGap   = min($nodeGap, 80); // không quá thưa

// ── 4. Tính toán positions ───────────────────────────────────────────────────
$positions     = [];  // member_id => [x, y]
$coupleCenters = [];  // "pid_sid" => [cx, bottom_y]

foreach ($generations as $genNum => $couples) {
    // Chiều rộng thực của hàng này (mix couple/solo)
    $rowW = 0;
    foreach ($couples as ['member' => $m, 'spouse' => $s]) {
        $rowW += ($s ? $COUPLE_W : $BOX_W);
    }
    $rowW += max(0, (count($couples) - 1) * $nodeGap);

    $startX = $CONTENT_X + ($CONTENT_W - $rowW) / 2;
    $y      = $CONTENT_Y + $genNum * ($BOX_H + $GEN_V_GAP);
    $curX   = $startX;

    foreach ($couples as ['member' => $m, 'spouse' => $s]) {
        $positions[$m->id] = ['x' => (int) round($curX), 'y' => (int) round($y)];
        $unitW = $BOX_W;

        if ($s) {
            $positions[$s->id] = ['x' => (int) round($curX + $BOX_W + $SPOUSE_GAP), 'y' => (int) round($y)];
            $unitW = $COUPLE_W;
        }

        $key = $m->id . '_' . ($s?->id ?? 'solo');
        $coupleCenters[$key] = [
            'cx' => (int) round($curX + $unitW / 2),
            'by' => (int) round($y + $BOX_H),
        ];
        $curX += $unitW + $nodeGap;
    }
}

// ── 5. Đường kết nối ────────────────────────────────────────────────────────

// Helper: tìm node trong tree theo member ID (recursive DFS)
$treesArr  = $trees->toArray();
$findNode  = function (array $nodes, int $id) use (&$findNode): ?array {
    foreach ($nodes as $t) {
        if ($t['member']->id === $id) {
            return $t;
        }
        $found = $findNode($t['children']->toArray(), $id);
        if ($found) {
            return $found;
        }
    }
    return null;
};

$connLines = [];

foreach ($generations as $genNum => $couples) {
    foreach ($couples as ['member' => $m, 'spouse' => $s]) {
        // Đường nối vợ chồng (nét đứt)
        if ($s && isset($positions[$m->id], $positions[$s->id])) {
            $connLines[] = [
                't'  => 's',
                'x1' => $positions[$m->id]['x'] + $BOX_W,
                'x2' => $positions[$s->id]['x'],
                'y'  => $positions[$m->id]['y'] + (int) round($BOX_H / 2),
            ];
        }

        // Tìm node → lấy children đã positioned
        $node = $findNode($treesArr, $m->id);
        if (!$node) {
            continue;
        }

        $childIds = $node['children']->map(fn ($c) => $c['member']->id)
            ->filter(fn ($cid) => isset($positions[$cid]))
            ->values()->toArray();

        // Gộp thêm con của spouse
        if ($s) {
            $spouseNode = $findNode($treesArr, $s->id);
            if ($spouseNode) {
                foreach ($spouseNode['children'] as $sc) {
                    $scid = $sc['member']->id;
                    if (isset($positions[$scid]) && !in_array($scid, $childIds, true)) {
                        $childIds[] = $scid;
                    }
                }
            }
        }

        if (empty($childIds)) {
            continue;
        }

        $key   = $m->id . '_' . ($s?->id ?? 'solo');
        $cInfo = $coupleCenters[$key] ?? null;
        if (!$cInfo) {
            continue;
        }

        $childCXs = array_map(fn ($cid) => $positions[$cid]['x'] + (int) round($BOX_W / 2), $childIds);
        $topY     = min(array_map(fn ($cid) => $positions[$cid]['y'], $childIds));
        $midY     = (int) round(($cInfo['by'] + $topY) / 2);

        $connLines[] = ['t' => 'v', 'x' => $cInfo['cx'], 'y1' => $cInfo['by'], 'y2' => $midY];
        $connLines[] = ['t' => 'h', 'x1' => min($childCXs), 'x2' => max($childCXs), 'y' => $midY];
        foreach ($childIds as $cid) {
            $cx          = $positions[$cid]['x'] + (int) round($BOX_W / 2);
            $connLines[] = ['t' => 'v', 'x' => $cx, 'y1' => $midY, 'y2' => $positions[$cid]['y']];
        }
    }
}

// ── 6. Inline background SVG ─────────────────────────────────────────────────
$bgRaw   = file_get_contents(resource_path('views/print-templates/default/background.svg'));
$bgInner = preg_replace('/^<svg[^>]*>/s', '', $bgRaw, 1);
$bgInner = preg_replace('/<\/svg>\s*$/', '', $bgInner);

// ── 7. Meta ───────────────────────────────────────────────────────────────────
$membersById = $members->keyBy('id');
$familyName  = optional($members->first()?->familyGroup)->name ?? '';
$year        = date('Y');
@endphp
<svg version="1.2" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
     viewBox="0 0 3508 2480" width="3508" height="2480">

{!! $bgInner !!}

{{-- ── Connection lines ──────────────────────────────────────────────── --}}
<g id="connections" fill="none" stroke-linecap="round" stroke-linejoin="round">
@foreach($connLines as $ln)
    @if($ln['t'] === 'v')
    <line stroke="#ff7d24" stroke-width="3"
          x1="{{ $ln['x'] }}" y1="{{ $ln['y1'] }}" x2="{{ $ln['x'] }}" y2="{{ $ln['y2'] }}"/>
    @elseif($ln['t'] === 'h')
    <line stroke="#ff7d24" stroke-width="3"
          x1="{{ $ln['x1'] }}" y1="{{ $ln['y'] }}" x2="{{ $ln['x2'] }}" y2="{{ $ln['y'] }}"/>
    @elseif($ln['t'] === 's')
    <line stroke="#593f8b" stroke-width="2.5" stroke-dasharray="10 5"
          x1="{{ $ln['x1'] }}" y1="{{ $ln['y'] }}" x2="{{ $ln['x2'] }}" y2="{{ $ln['y'] }}"/>
    @endif
@endforeach
</g>

{{-- ── Member boxes ───────────────────────────────────────────────────── --}}
@foreach($positions as $memberId => $pos)
@php
    /** @var \App\Models\FamilyMember|null $m */
    $m = $membersById->get($memberId);
@endphp
@continue(!$m)
@php
    $pronoun  = trim($m->pronoun ?? $m->relationship ?? '');
    $nameStr  = $m->name ?? '';
    $nameLen  = mb_strlen($nameStr);
    $fontSize = $nameLen > 16 ? 14 : ($nameLen > 12 ? 16 : ($nameLen > 8 ? 18 : 20));

    $lifespan = '';
    if ($m->birth_year && $m->death_year) {
        $lifespan = $m->birth_year . '–' . $m->death_year;
    } elseif ($m->birth_year) {
        $lifespan = 'sinh ' . $m->birth_year;
    } elseif (isset($m->is_alive) && !$m->is_alive) {
        $lifespan = '†';
    }

    $lineCount = ($pronoun ? 1 : 0) + 1 + ($lifespan ? 1 : 0);
    $nameY = match ($lineCount) {
        3 => 44,
        2 => ($pronoun ? 50 : 48),
        default => 50,
    };
@endphp
<g transform="translate({{ $pos['x'] }},{{ $pos['y'] }})">
    {{-- Nền trắng mờ bên trong --}}
    <rect x="13" y="13" width="158" height="59" fill="rgba(255,255,255,0.85)" rx="2"/>
    {{-- Khung từ box.svg --}}
    <path fill="none" stroke="#ff7d24" stroke-miterlimit="100" stroke-width="4.2"
          d="m10.4 4.5h163.9q0 0.1 0 0.3c0 3.9 2.4 7.1 5.5 7.3v60.3c-3.1 0.2-5.5 3.4-5.5 7.3q0 0.3 0 0.5h-163.9q0-0.2 0-0.5c0-4-2.5-7.3-5.7-7.3v-60.3c3.2 0 5.7-3.3 5.7-7.3q0-0.2 0-0.3z"/>
    {{-- Pronoun / chức danh --}}
    @if($pronoun)
    <text x="92" y="27" text-anchor="middle" dominant-baseline="middle"
          font-family="Arial,Tahoma,sans-serif" font-size="13" fill="#593f8b">{{ $pronoun }}</text>
    @endif
    {{-- Tên --}}
    <text x="92" y="{{ $nameY }}" text-anchor="middle" dominant-baseline="middle"
          font-family="Arial,Tahoma,sans-serif" font-size="{{ $fontSize }}" font-weight="bold" fill="#2c2e35">{{ $nameStr }}</text>
    {{-- Năm sinh–mất --}}
    @if($lifespan)
    <text x="92" y="65" text-anchor="middle" dominant-baseline="middle"
          font-family="Arial,Tahoma,sans-serif" font-size="13" fill="#593f8b">{{ $lifespan }}</text>
    @endif
</g>
@endforeach

{{-- ── Tiêu đề gia phả (overlay lên scroll của background) ────────────── --}}
@if($familyName)
<text x="1754" y="192" text-anchor="middle" dominant-baseline="middle"
      font-family="Arial,Tahoma,sans-serif" font-size="52" font-weight="bold"
      fill="#ffffff" letter-spacing="4">{{ mb_strtoupper($familyName) }}</text>
@endif
<text x="1754" y="252" text-anchor="middle" dominant-baseline="middle"
      font-family="Arial,Tahoma,sans-serif" font-size="28" fill="#f5d376"
      letter-spacing="2">Năm {{ $year }}</text>

</svg>
