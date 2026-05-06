<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Services\FamilyTreeService;
use App\Services\FamilyTreeSvgService;
use App\Services\PrintOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly FamilyTreeService    $tree,
        private readonly FamilyTreeSvgService $svgService,
        private readonly PrintOrderService    $printOrderService,
    ) {}

    public function index(): View
    {
        $group   = active_group();
        $members = $group->familyMembers()->with(['derivedEvent.familyMember'])->orderBy('birth_year')->orderBy('name')->get();
        $roots   = $this->tree->roots($group->id);

        // Build tree cho mỗi root
        $visited = [];
        $trees   = $roots->map(fn ($r) => $this->tree->buildTree($r, 0, 10, $visited))->filter()->values();

        return view('genealogy.index', compact('members', 'trees', 'group'));
    }

    public function create(): View
    {
        $group   = active_group();
        $members = $group->familyMembers()->orderBy('birth_year')->orderBy('name')->get();
        $couples = $this->buildCoupleOptions($group->id, $members);

        return view('genealogy.create', compact('members', 'couples'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'pronoun'         => ['nullable', 'string', 'max:50'],
            'relationship'    => ['nullable', 'string', 'max:80'],
            'gender'          => ['required', 'in:male,female,unknown'],
            'birth_year'      => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'death_year'      => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'death_day'       => ['nullable', 'integer', 'min:1', 'max:30'],
            'death_month'     => ['nullable', 'integer', 'min:1', 'max:12'],
            'death_date_type' => ['nullable', 'in:lunar,solar'],
            'is_alive'        => ['required', 'boolean'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'couple_id'       => ['nullable', 'string'],  // "c:1:2" hoặc "s:5"
            'spouse_id'       => ['nullable', 'integer', 'exists:family_members,id'],
        ]);

        $group  = active_group();
        $member = $group->familyMembers()->create([
            ...collect($data)->except(['couple_id', 'spouse_id'])->toArray(),
            'user_id'         => Auth::id(),
            'death_date_type' => $data['death_date_type'] ?? 'lunar',
        ]);

        $this->syncParents($member->id, $data['couple_id'] ?? null);

        if (! empty($data['spouse_id'])) {
            DB::table('family_relationships')->insertOrIgnore([
                'member_id'         => min($member->id, (int) $data['spouse_id']),
                'related_member_id' => max($member->id, (int) $data['spouse_id']),
                'type'              => 'spouse',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        return redirect()->route('genealogy.index')
            ->with('success', "Đã thêm {$member->name} vào gia phả.");
    }

    public function edit(FamilyMember $genealogy): View
    {
        $member = $genealogy; // alias để code bên dưới không đổi
        $this->authorize($member);
        $group   = active_group();
        $members = $group->familyMembers()->where('id', '!=', $member->id)->orderBy('name')->get();

        $currentSpouseId = DB::table('family_relationships')
            ->where('type', 'spouse')
            ->where(fn ($q) => $q->where('member_id', $member->id)->orWhere('related_member_id', $member->id))
            ->first();
        $currentSpouseId = $currentSpouseId
            ? ($currentSpouseId->member_id == $member->id ? $currentSpouseId->related_member_id : $currentSpouseId->member_id)
            : null;

        $couples        = $this->buildCoupleOptions($group->id, $members, $member->id);
        $currentCoupleId = $this->currentCoupleId($member);

        return view('genealogy.edit', compact('member', 'members', 'couples', 'currentCoupleId', 'currentSpouseId'));
    }

    public function update(Request $request, FamilyMember $genealogy): RedirectResponse
    {
        $member = $genealogy;
        $this->authorize($member);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'pronoun'         => ['nullable', 'string', 'max:50'],
            'relationship'    => ['nullable', 'string', 'max:80'],
            'gender'          => ['required', 'in:male,female,unknown'],
            'birth_year'      => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'death_year'      => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'death_day'       => ['nullable', 'integer', 'min:1', 'max:30'],
            'death_month'     => ['nullable', 'integer', 'min:1', 'max:12'],
            'death_date_type' => ['nullable', 'in:lunar,solar'],
            'is_alive'        => ['required', 'boolean'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'couple_id'       => ['nullable', 'string'],
            'spouse_id'       => ['nullable', 'integer', 'exists:family_members,id'],
        ]);

        $member->update(collect($data)->except(['couple_id', 'spouse_id'])->toArray());

        $this->syncParents($member->id, $data['couple_id'] ?? null);

        // Sync spouse
        DB::table('family_relationships')
            ->where('type', 'spouse')
            ->where(fn ($q) => $q->where('member_id', $member->id)->orWhere('related_member_id', $member->id))
            ->delete();
        if (! empty($data['spouse_id'])) {
            DB::table('family_relationships')->insertOrIgnore([
                'member_id'         => min($member->id, (int) $data['spouse_id']),
                'related_member_id' => max($member->id, (int) $data['spouse_id']),
                'type'              => 'spouse',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        return redirect()->route('genealogy.index')
            ->with('success', "Đã cập nhật {$member->name}.");
    }

    public function destroy(FamilyMember $genealogy): RedirectResponse|JsonResponse
    {
        $member = $genealogy;
        $this->authorize($member);
        $name = $member->name;
        $member->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('genealogy.index')
            ->with('success', "Đã xóa {$name} khỏi gia phả.");
    }

    /**
     * Xuất gia phả dạng SVG vector — sắc nét tuyệt đối, không mất góc.
     * CorelDRAW / Illustrator / Inkscape / trình duyệt đều mở được trực tiếp.
     */
    public function exportSvg(): \Illuminate\Http\Response
    {
        $group    = active_group();
        $members  = $group->familyMembers;
        $template = $this->printOrderService->getTemplate('default');

        $svgContent = $this->svgService->generateContent($members, $template);
        $filename   = 'gia-pha-' . str_replace([' ', '/'], '-', $group->name ?? 'family') . '.svg';

        return response($svgContent, 200, [
            'Content-Type'        => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Xuất gia phả định dạng GEDCOM 5.5.1 — tương thích Ancestry, FamilySearch, MacFamilyTree.
     * Bao gồm: tên, giới tính, năm sinh, năm mất, quan hệ vợ chồng & cha/mẹ con cái.
     */
    public function exportGedcom(): \Illuminate\Http\Response
    {
        $group   = active_group();
        $members = $group->familyMembers()->get();
        $memberIds = $members->pluck('id');

        $parentChild = DB::table('family_relationships')
            ->where('type', 'parent_child')
            ->whereIn('member_id', $memberIds)
            ->whereIn('related_member_id', $memberIds)
            ->get();

        $spouses = DB::table('family_relationships')
            ->where('type', 'spouse')
            ->whereIn('member_id', $memberIds)
            ->whereIn('related_member_id', $memberIds)
            ->get();

        // Build family units (FAM records) — mỗi cặp vợ chồng là 1 FAM
        $famMap  = []; // "husb_id:wife_id" → fam_id
        $famList = [];
        $famIdx  = 1;

        foreach ($spouses as $s) {
            $key = min($s->member_id, $s->related_member_id) . ':' . max($s->member_id, $s->related_member_id);
            if (! isset($famMap[$key])) {
                $famMap[$key] = $famIdx++;
                $famList[$key] = ['husb' => $s->member_id, 'wife' => $s->related_member_id, 'children' => []];
            }
        }

        // Gắn children vào FAM
        foreach ($parentChild as $pc) {
            $parentId = $pc->member_id;
            $childId  = $pc->related_member_id;
            // Tìm FAM mà parent thuộc về (là husb hoặc wife)
            foreach ($famList as $key => &$fam) {
                if ($fam['husb'] == $parentId || $fam['wife'] == $parentId) {
                    if (! in_array($childId, $fam['children'])) {
                        $fam['children'][] = $childId;
                    }
                }
            }
            unset($fam);
        }

        // Generate GEDCOM
        $membersById = $members->keyBy('id');
        $lines = [];

        // HEAD
        $lines[] = "0 HEAD";
        $lines[] = "1 GEDC";
        $lines[] = "2 VERS 5.5.1";
        $lines[] = "2 FORM LINEAGE-LINKED";
        $lines[] = "1 CHAR UTF-8";
        $lines[] = "1 SOUR GiaPhong";
        $lines[] = "2 NAME Gia Phong";
        $lines[] = "1 DATE " . now()->format('d M Y');

        // INDI records
        foreach ($members as $m) {
            $nameParts = explode(' ', $m->name ?? '');
            $lastName  = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
            $gedName   = trim($firstName . ' /' . $lastName . '/');

            $lines[] = "0 @I{$m->id}@ INDI";
            $lines[] = "1 NAME {$gedName}";
            if ($m->pronoun) $lines[] = "2 TITL {$m->pronoun}";
            $lines[] = "1 SEX " . ($m->gender === 'female' ? 'F' : 'M');
            if ($m->birth_year) {
                $lines[] = "1 BIRT";
                $lines[] = "2 DATE {$m->birth_year}";
            }
            if ($m->death_year || ! $m->isAlive()) {
                $lines[] = "1 DEAT" . ($m->death_year ? '' : ' Y');
                if ($m->death_year) $lines[] = "2 DATE {$m->death_year}";
                if ($m->death_day && $m->death_month) {
                    $typeLabel = $m->death_date_type === 'solar' ? '(DL)' : '(AL)';
                    $lines[] = "2 NOTE Ngày giỗ: {$m->death_day}/{$m->death_month} {$typeLabel}";
                }
            }
        }

        // FAM records
        foreach ($famList as $key => $fam) {
            $famId = $famMap[$key];
            $lines[] = "0 @F{$famId}@ FAM";
            $lines[] = "1 HUSB @I{$fam['husb']}@";
            $lines[] = "1 WIFE @I{$fam['wife']}@";
            foreach ($fam['children'] as $cid) {
                $lines[] = "1 CHIL @I{$cid}@";
            }
        }

        // TRLR
        $lines[] = "0 TRLR";

        $content  = implode("\n", $lines);
        $filename = 'gia-pha-' . str_replace([' ', '/'], '-', $group->name ?? 'family') . '.ged';

        return response($content, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** API: trả về JSON cho FamilyTree JS */
    public function treeData(): JsonResponse
    {
        $group     = active_group();
        $members   = $group->familyMembers()->get();
        $memberIds = $members->pluck('id');

        // Lấy tất cả relationships 1 lần (tránh N+1), chỉ lấy quan hệ trong group
        $parentChild = DB::table('family_relationships')
            ->where('type', 'parent_child')
            ->where(fn ($q) => $q
                ->whereIn('member_id', $memberIds)
                ->orWhereIn('related_member_id', $memberIds)
            )->get();

        $spouses = DB::table('family_relationships')
            ->where('type', 'spouse')
            ->where(fn ($q) => $q
                ->whereIn('member_id', $memberIds)
                ->orWhereIn('related_member_id', $memberIds)
            )->get();

        $nodes = $members->map(function (FamilyMember $m) use ($parentChild, $spouses, $memberIds) {
            // Cha/mẹ của member này — chỉ tính parent thuộc cùng group
            $parents = $parentChild
                ->where('related_member_id', $m->id)
                ->filter(fn ($r) => $memberIds->contains($r->member_id));
            $father  = $parents->first(fn ($r) => FamilyMember::find($r->member_id)?->gender === 'male');
            $mother  = $parents->first(fn ($r) => FamilyMember::find($r->member_id)?->gender === 'female');

            // Nếu chỉ có 1 parent và không rõ giới → gán làm father
            if (! $father && ! $mother && $parents->count() === 1) {
                $father = $parents->first();
            }

            // Spouse IDs — chỉ lấy spouse thuộc cùng group
            $pids = $spouses
                ->filter(fn ($r) => $r->member_id == $m->id || $r->related_member_id == $m->id)
                ->map(fn ($r) => $r->member_id == $m->id ? $r->related_member_id : $r->member_id)
                ->filter(fn ($pid) => $memberIds->contains($pid))
                ->values()->toArray();

            return [
                'id'           => $m->id,
                'fid'          => $father?->member_id,
                'mid'          => $mother?->member_id,
                'pids'         => $pids,
                'name'         => $m->name,
                'pronoun'      => $m->pronoun,
                'gender'       => $m->gender === 'male' ? 'male' : ($m->gender === 'female' ? 'female' : 'male'),
                'birth_year'   => $m->birth_year,
                'death_day'    => $m->death_day,
                'death_month'  => $m->death_month,
                'death_year'   => $m->death_year,
                'death_date_type' => $m->death_date_type,
                'is_alive'     => $m->isAlive(),
                'has_event'    => $m->memorial_event_id !== null,
                'event_url'    => $m->memorial_event_id ? route('events.show', $m->memorial_event_id) : null,
                'edit_url'     => route('genealogy.edit', $m),
                'delete_url'   => route('genealogy.destroy', $m),
            ];
        });

        // Root lên đầu để family-chart focus đúng
        $root = $this->tree->detectRootMember($nodes);
        if ($root) {
            $nodes = $nodes->sortBy(fn($n) => $n['id'] === $root['id'] ? 0 : 1);
        }

        return response()->json($nodes->values());
    }

    /** API: thêm thành viên mới kèm quan hệ với một thành viên có sẵn */
    public function addRelative(Request $request, FamilyMember $genealogy): JsonResponse
    {
        abort_unless($genealogy->family_group_id === active_group()->id, 403);

        $data = $request->validate([
            'type'       => ['required', 'in:father,mother,spouse,son,daughter'],
            'name'       => ['required', 'string', 'max:100'],
            'pronoun'    => ['nullable', 'string', 'max:50'],
            'birth_year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'death_year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
        ]);

        $group = active_group();

        $gender = match($data['type']) {
            'father'   => 'male',
            'son'      => 'male',
            'mother'   => 'female',
            'daughter' => 'female',
            'spouse'   => $genealogy->gender === 'male' ? 'female' : 'male',
        };

        $newMember = $group->familyMembers()->create([
            'name'            => $data['name'],
            'pronoun'         => filled($data['pronoun'] ?? null) ? $data['pronoun'] : null,
            'gender'          => $gender,
            'user_id'         => Auth::id(),
            'birth_year'      => $data['birth_year'] ?? null,
            'death_year'      => $data['death_year'] ?? null,
            'is_alive'        => empty($data['death_year']),
            'death_date_type' => 'lunar',
        ]);

        $now = now();
        match($data['type']) {
            'father', 'mother' => DB::table('family_relationships')->insertOrIgnore([
                'member_id' => $newMember->id, 'related_member_id' => $genealogy->id,
                'type' => 'parent_child', 'created_at' => $now, 'updated_at' => $now,
            ]),
            'son', 'daughter' => DB::table('family_relationships')->insertOrIgnore([
                'member_id' => $genealogy->id, 'related_member_id' => $newMember->id,
                'type' => 'parent_child', 'created_at' => $now, 'updated_at' => $now,
            ]),
            'spouse' => DB::table('family_relationships')->insertOrIgnore([
                'member_id' => min($genealogy->id, $newMember->id),
                'related_member_id' => max($genealogy->id, $newMember->id),
                'type' => 'spouse', 'created_at' => $now, 'updated_at' => $now,
            ]),
        };

        // Khi thêm con: nếu $genealogy đã có spouse, tự động gán spouse làm cha/mẹ còn lại
        if (in_array($data['type'], ['son', 'daughter'])) {
            $spouseRel = DB::table('family_relationships')
                ->where('type', 'spouse')
                ->where(fn ($q) => $q
                    ->where('member_id', $genealogy->id)
                    ->orWhere('related_member_id', $genealogy->id)
                )
                ->first();

            if ($spouseRel) {
                $spouseId = $spouseRel->member_id === $genealogy->id
                    ? $spouseRel->related_member_id
                    : $spouseRel->member_id;

                DB::table('family_relationships')->insertOrIgnore([
                    'member_id'         => $spouseId,
                    'related_member_id' => $newMember->id,
                    'type'              => 'parent_child',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }

        return response()->json(['ok' => true, 'id' => $newMember->id, 'name' => $newMember->name]);
    }

    /** @deprecated — tạm giữ route, không dùng nữa */
    public function treeSync(Request $request): JsonResponse
    {
        $group = active_group();
        $nodes = collect($request->input('nodes', []));

        $dbIds = $group->familyMembers()->pluck('id')->map('intval')->toArray();

        // IDs thực (số nguyên) = thành viên đã có trong DB
        $incomingRealIds = $nodes
            ->filter(fn($n) => ctype_digit((string) $n['id']))
            ->pluck('id')->map('intval')->toArray();

        // Xóa thành viên đã bị remove khỏi tree
        $toDelete = array_diff($dbIds, $incomingRealIds);
        if ($toDelete) {
            $group->familyMembers()->whereIn('id', $toDelete)->delete();
        }

        // Build id map: client_id → real_db_id
        $idMap = [];
        foreach ($dbIds as $id) {
            $idMap[(string) $id] = $id;
        }

        foreach ($nodes as $node) {
            $d    = $node['data'] ?? [];
            $name = trim($d['first name'] ?? '');
            if (! $name) continue;

            $fields = [
                'name'       => $name,
                'pronoun'    => filled($d['pronoun']    ?? null) ? trim($d['pronoun'])    : null,
                'gender'     => ($d['gender'] ?? 'M') === 'F' ? 'female' : 'male',
                'birth_year' => filled($d['birthday']   ?? null) ? (int) $d['birthday']   : null,
                'death_year' => filled($d['death_year'] ?? null) ? (int) $d['death_year'] : null,
                'is_alive'   => !filled($d['death_year'] ?? null),
            ];

            $nodeId = (string) $node['id'];

            if (ctype_digit($nodeId) && in_array((int) $nodeId, $dbIds)) {
                $group->familyMembers()->where('id', (int) $nodeId)->update($fields);
                $idMap[$nodeId] = (int) $nodeId;
            } else {
                $m = $group->familyMembers()->create([
                    ...$fields,
                    'user_id'         => Auth::id(),
                    'death_date_type' => 'lunar',
                ]);
                $idMap[$nodeId] = $m->id;
            }
        }

        // Refresh sau khi xóa
        $allMemberIds = $group->familyMembers()->pluck('id')->toArray();

        // Tính expected relationships từ rels của từng node
        $expectedParentChild = [];
        $expectedSpouses     = [];

        foreach ($nodes as $node) {
            $rels       = $node['rels'] ?? [];
            $nodeRealId = $idMap[(string) $node['id']] ?? null;
            if (! $nodeRealId) continue;

            // Hỗ trợ cả legacy (father/mother) lẫn mới (parents[])
            $parents = [];
            if (! empty($rels['father']))  $parents[] = (string) $rels['father'];
            if (! empty($rels['mother']))  $parents[] = (string) $rels['mother'];
            if (! empty($rels['parents'])) {
                foreach ((array) $rels['parents'] as $p) $parents[] = (string) $p;
            }
            foreach (array_unique($parents) as $parentId) {
                $pid = $idMap[$parentId] ?? null;
                if ($pid && in_array($pid, $allMemberIds)) {
                    $expectedParentChild["{$pid}-{$nodeRealId}"] = [(int) $pid, (int) $nodeRealId];
                }
            }

            foreach ((array) ($rels['spouses'] ?? []) as $spouseId) {
                $sid = $idMap[(string) $spouseId] ?? null;
                if ($sid && in_array($sid, $allMemberIds)) {
                    $a = min((int) $nodeRealId, (int) $sid);
                    $b = max((int) $nodeRealId, (int) $sid);
                    $expectedSpouses["{$a}-{$b}"] = [$a, $b];
                }
            }
        }

        // Xóa toàn bộ relationships của group rồi recreate
        DB::table('family_relationships')
            ->where(fn($q) => $q
                ->whereIn('member_id', $allMemberIds)
                ->orWhereIn('related_member_id', $allMemberIds)
            )->delete();

        $now     = now();
        $inserts = [];
        foreach ($expectedParentChild as [$pid, $cid]) {
            $inserts[] = ['member_id' => $pid, 'related_member_id' => $cid, 'type' => 'parent_child', 'created_at' => $now, 'updated_at' => $now];
        }
        foreach ($expectedSpouses as [$a, $b]) {
            $inserts[] = ['member_id' => $a, 'related_member_id' => $b, 'type' => 'spouse', 'created_at' => $now, 'updated_at' => $now];
        }
        if ($inserts) {
            DB::table('family_relationships')->insert($inserts);
        }

        return $this->treeData();
    }

    /** API: lưu thay đổi từ Balkan FamilyTree (add node/relationship) */
    public function treeSave(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:add_member,add_relation,remove_relation,update_member'],
        ]);

        $group = active_group();

        match ($request->action) {
            'add_member' => $this->treeAddMember($request, $group),
            'add_relation' => $this->treeAddRelation($request),
            'remove_relation' => $this->treeRemoveRelation($request),
            'update_member' => $this->treeUpdateMember($request),
        };

        return response()->json(['ok' => true]);
    }

    private function treeAddMember(\Illuminate\Http\Request $request, $group): void
    {
        $member = $group->familyMembers()->create([
            'user_id'   => auth()->id(),
            'name'      => $request->input('name', 'Thành viên mới'),
            'gender'    => $request->input('gender', 'unknown'),
            'birth_year' => $request->input('birth_year'),
            'death_year' => $request->input('death_year'),
            'is_alive'   => empty($request->input('death_year')),
        ]);

        // Gắn quan hệ nếu có
        if ($fid = $request->input('fid')) {
            DB::table('family_relationships')->insertOrIgnore([
                'member_id' => $fid, 'related_member_id' => $member->id,
                'type' => 'parent_child', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if ($mid = $request->input('mid')) {
            DB::table('family_relationships')->insertOrIgnore([
                'member_id' => $mid, 'related_member_id' => $member->id,
                'type' => 'parent_child', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function treeAddRelation(\Illuminate\Http\Request $request): void
    {
        $type     = $request->input('rel_type', 'parent_child');
        $memberId = $request->input('member_id');
        $relId    = $request->input('related_id');

        if (! $memberId || ! $relId) return;

        $insert = $type === 'spouse'
            ? ['member_id' => min($memberId,$relId), 'related_member_id' => max($memberId,$relId), 'type' => 'spouse']
            : ['member_id' => $memberId, 'related_member_id' => $relId, 'type' => 'parent_child'];

        DB::table('family_relationships')->insertOrIgnore([...$insert, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function treeRemoveRelation(\Illuminate\Http\Request $request): void
    {
        DB::table('family_relationships')
            ->where('member_id', $request->input('member_id'))
            ->where('related_member_id', $request->input('related_id'))
            ->delete();
    }

    private function treeUpdateMember(\Illuminate\Http\Request $request): void
    {
        $member = FamilyMember::where('family_group_id', active_group()->id)->find($request->input('id'));
        if ($member) {
            $data = $request->only(['name', 'pronoun', 'gender', 'birth_year', 'death_year']);
            if (isset($data['death_year'])) {
                $data['is_alive'] = empty($data['death_year']);
            }
            $member->update($data);
        }
    }

    private function authorize(FamilyMember $member): void
    {
        abort_if($member->family_group_id !== active_group()->id, 403);
    }

    /**
     * Build danh sách cặp vợ chồng + đơn lẻ để dùng trong dropdown chọn cha/mẹ.
     * Format value: "c:{id1}:{id2}" cho cặp, "s:{id}" cho đơn lẻ.
     */
    private function buildCoupleOptions(int $groupId, $members, ?int $excludeId = null): array
    {
        $allIds = $members->pluck('id');

        $spouseRels = DB::table('family_relationships')
            ->where('type', 'spouse')
            ->whereIn('member_id', $allIds)
            ->whereIn('related_member_id', $allIds)
            ->get();

        $pairedIds = collect();
        $couples   = [];

        foreach ($spouseRels as $rel) {
            if ($pairedIds->contains($rel->member_id) || $pairedIds->contains($rel->related_member_id)) continue;

            $m1 = $members->firstWhere('id', $rel->member_id);
            $m2 = $members->firstWhere('id', $rel->related_member_id);
            if (! $m1 || ! $m2) continue;
            if ($m1->id === $excludeId || $m2->id === $excludeId) continue;

            $couples[] = [
                'value' => "c:{$m1->id}:{$m2->id}",
                'label' => "{$m1->name} & {$m2->name}",
            ];
            $pairedIds->push($m1->id, $m2->id);
        }

        // Thêm các members đơn lẻ (không có spouse trong group)
        foreach ($members as $m) {
            if ($m->id === $excludeId) continue;
            if ($pairedIds->contains($m->id)) continue;
            $couples[] = [
                'value' => "s:{$m->id}",
                'label' => $m->name . ($m->lifespan() ? ' (' . $m->lifespan() . ')' : ''),
            ];
        }

        return $couples;
    }

    /**
     * Sync parent relationships từ couple_id.
     * "c:1:2" → member 1 và 2 là cha/mẹ
     * "s:5"   → chỉ member 5 là cha/mẹ
     * null/"" → xóa hết cha/mẹ
     */
    private function syncParents(int $memberId, ?string $coupleId): void
    {
        DB::table('family_relationships')
            ->where('related_member_id', $memberId)
            ->where('type', 'parent_child')
            ->delete();

        if (! $coupleId) return;

        $parts = explode(':', $coupleId);

        if ($parts[0] === 'c' && isset($parts[1], $parts[2])) {
            foreach ([(int) $parts[1], (int) $parts[2]] as $parentId) {
                DB::table('family_relationships')->insertOrIgnore([
                    'member_id'         => $parentId,
                    'related_member_id' => $memberId,
                    'type'              => 'parent_child',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        } elseif ($parts[0] === 's' && isset($parts[1])) {
            DB::table('family_relationships')->insertOrIgnore([
                'member_id'         => (int) $parts[1],
                'related_member_id' => $memberId,
                'type'              => 'parent_child',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    /** Lấy couple_id hiện tại của member để pre-select dropdown */
    private function currentCoupleId(FamilyMember $member): ?string
    {
        $parentIds = DB::table('family_relationships')
            ->where('related_member_id', $member->id)
            ->where('type', 'parent_child')
            ->pluck('member_id');

        if ($parentIds->count() === 2) {
            $sorted = $parentIds->sort()->values();
            return "c:{$sorted[0]}:{$sorted[1]}";
        }

        if ($parentIds->count() === 1) {
            return "s:{$parentIds->first()}";
        }

        return null;
    }
}
