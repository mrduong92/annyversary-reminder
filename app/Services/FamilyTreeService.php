<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FamilyTreeService
{
    /**
     * Trả về các node gốc (không có cha/mẹ trong group).
     */
    public function roots(int $groupId): Collection
    {
        $allIds = FamilyMember::where('family_group_id', $groupId)->pluck('id');

        // Những ID có ít nhất 1 parent trong group
        $hasParent = DB::table('family_relationships')
            ->where('type', 'parent_child')
            ->whereIn('related_member_id', $allIds)
            ->whereIn('member_id', $allIds)
            ->pluck('related_member_id')
            ->unique();

        return FamilyMember::where('family_group_id', $groupId)
            ->whereNotIn('id', $hasParent)
            ->orderBy('birth_year')
            ->orderBy('name')
            ->get();
    }

    /**
     * Build tree recursively, giới hạn depth để tránh infinite loop.
     * Trả về array node, mỗi node có: member, spouses, children[]
     */
    public function buildTree(FamilyMember $member, int $depth = 0, int $maxDepth = 10, array &$visited = []): ?array
    {
        if ($depth > $maxDepth || in_array($member->id, $visited)) {
            return null;
        }
        $visited[] = $member->id;

        $spouses  = $member->spouses()->filter(fn ($s) => ! in_array($s->id, $visited));
        $children = $member->children()->filter(fn ($c) => ! in_array($c->id, $visited));

        $childNodes = $children->map(fn ($c) => $this->buildTree($c, $depth + 1, $maxDepth, $visited))
            ->filter()
            ->values();

        return [
            'member'   => $member,
            'spouses'  => $spouses,
            'children' => $childNodes,
        ];
    }

    /**
     * Text summary cho AI tool.
     */
    public function toText(int $groupId): string
    {
        $members = FamilyMember::where('family_group_id', $groupId)
            ->orderBy('birth_year')
            ->get();

        if ($members->isEmpty()) return 'Chưa có thành viên nào trong gia phả.';

        $lines = $members->map(function (FamilyMember $m) {
            $lifespan = $m->lifespan();
            $parents  = $m->parents()->pluck('name')->implode(', ');
            $spouses  = $m->spouses()->pluck('name')->implode(', ');
            $children = $m->children()->pluck('name')->implode(', ');

            $parts = ["• {$m->name}"];
            if ($lifespan) $parts[] = "({$lifespan})";
            if ($parents)  $parts[] = "con của: {$parents}";
            if ($spouses)  $parts[] = "vợ/chồng: {$spouses}";
            if ($children) $parts[] = "con cái: {$children}";

            return implode(', ', $parts);
        });

        return $lines->implode("\n");
    }

    /**
     * Detech root member.
     */
    public function detectRootMember(Collection $nodes)
    {
        $nodesById = $nodes->keyBy('id');

        $rootCandidates = $nodes->filter(function ($n) use ($nodesById) {
            $noParent = empty($n['fid']) && empty($n['mid']);
            if (! $noParent) return false;

            $spouseIds = $n['pids'] ?? [];

            // Case A: không có spouse
            if (empty($spouseIds)) {
                return true;
            }

            // Case B: có spouse nhưng spouse cũng không có cha mẹ
            $allSpouseNoParent = collect($spouseIds)->every(function ($sid) use ($nodesById) {
                $sp = $nodesById->get($sid);
                if (! $sp) return false;

                return empty($sp['fid']) && empty($sp['mid']);
            });

            return $allSpouseNoParent;
        });

        // Ưu tiên nam
        $root = $rootCandidates
            ->sortByDesc(fn($n) => $n['gender'] === 'male')
            ->first();

        // Fallback nếu không tìm được
        if (! $root) {
            $root = $nodes->sortBy(fn($n) => $n['birth_year'] ?? PHP_INT_MAX)->first();
        }

        return $root;
    }
}
