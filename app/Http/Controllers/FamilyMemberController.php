<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Services\FamilyTreeService;
use App\Services\LunarCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly FamilyTreeService $tree,
        private readonly LunarCalendarService $lunar,
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
        $events  = $group->memorialEvents()->with('familyMember')->orderBy('solar_date_next')->get();

        $currentParentIds = DB::table('family_relationships')
            ->where('related_member_id', $member->id)->where('type', 'parent_child')
            ->pluck('member_id')->toArray();

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

    public function destroy(FamilyMember $genealogy): RedirectResponse
    {
        $member = $genealogy;
        $this->authorize($member);
        $name = $member->name;
        $member->delete();

        return redirect()->route('genealogy.index')
            ->with('success', "Đã xóa {$name} khỏi gia phả.");
    }

    /** API: trả về JSON cho Balkan FamilyTree JS */
    public function treeData(): \Illuminate\Http\JsonResponse
    {
        $group   = active_group();
        $members = $group->familyMembers()->get();

        // Lấy tất cả relationships 1 lần (tránh N+1)
        $parentChild = DB::table('family_relationships')
            ->where('type', 'parent_child')
            ->whereIn('member_id', $members->pluck('id'))
            ->orWhere(fn ($q) => $q->where('type', 'parent_child')->whereIn('related_member_id', $members->pluck('id')))
            ->get();

        $spouses = DB::table('family_relationships')
            ->where('type', 'spouse')
            ->where(fn ($q) => $q
                ->whereIn('member_id', $members->pluck('id'))
                ->orWhereIn('related_member_id', $members->pluck('id'))
            )->get();

        $nodes = $members->map(function (FamilyMember $m) use ($parentChild, $spouses) {
            // Cha/mẹ của member này
            $parents = $parentChild->where('related_member_id', $m->id);
            $father  = $parents->first(fn ($r) => FamilyMember::find($r->member_id)?->gender === 'male');
            $mother  = $parents->first(fn ($r) => FamilyMember::find($r->member_id)?->gender === 'female');

            // Nếu chỉ có 1 parent và không rõ giới → gán làm father
            if (! $father && ! $mother && $parents->count() === 1) {
                $father = $parents->first();
            }

            // Spouse IDs
            $pids = $spouses
                ->filter(fn ($r) => $r->member_id == $m->id || $r->related_member_id == $m->id)
                ->map(fn ($r) => $r->member_id == $m->id ? $r->related_member_id : $r->member_id)
                ->values()->toArray();

            return [
                'id'           => $m->id,
                'fid'          => $father?->member_id,
                'mid'          => $mother?->member_id,
                'pids'         => $pids,
                'name'         => $m->name,
                'gender'       => $m->gender === 'male' ? 'male' : ($m->gender === 'female' ? 'female' : 'male'),
                'birth_year'   => $m->birth_year,
                'death_year'   => $m->death_year,
                'is_alive'     => $m->isAlive(),
                'has_event'    => $m->memorial_event_id !== null,
                'event_url'    => $m->memorial_event_id ? route('events.show', $m->memorial_event_id) : null,
                'edit_url'     => route('genealogy.edit', $m),
            ];
        });

        return response()->json($nodes->values());
    }

    /** API: lưu thay đổi từ Balkan FamilyTree (add node/relationship) */
    public function treeSave(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
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
            $member->update($request->only(['name', 'gender', 'birth_year', 'death_year']));
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
