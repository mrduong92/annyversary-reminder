<?php

namespace App\Http\Controllers;

use App\Models\FamilyGroup;
use App\Models\FamilyMember;
use App\Models\FamilyShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PublicTreeController extends Controller
{
    /** Bật chia sẻ cây gia phả — tạo hoặc kích hoạt lại share link */
    public function enable(Request $request): JsonResponse
    {
        $group = FamilyGroup::where('user_id', Auth::id())->firstOrFail();
        $share = $group->getOrCreateTreeShare();
        $share->update(['is_active' => true]);

        return response()->json([
            'url'   => route('public-tree.show', $share->share_token),
            'token' => $share->share_token,
        ]);
    }

    /** Tắt chia sẻ cây gia phả */
    public function disable(Request $request): JsonResponse
    {
        $group = FamilyGroup::where('user_id', Auth::id())->firstOrFail();
        $group->familyShares()->where('type', 'tree')->update(['is_active' => false]);

        return response()->json(['ok' => true]);
    }

    /** Trang xem cây gia phả công khai — không cần đăng nhập */
    public function show(string $token): View|RedirectResponse
    {
        $share = FamilyShare::where('share_token', $token)->first();

        if (! $share || ! $share->isAccessible()) {
            return redirect('/')->with('error', 'Link chia sẻ không tồn tại hoặc đã hết hạn.');
        }

        $share->increment('access_count');

        // Lookup group — ưu tiên family_group_id, fallback về group của user
        $group = $share->family_group_id
            ? FamilyGroup::find($share->family_group_id)
            : null;

        if (! $group) {
            $group = FamilyGroup::where('user_id', $share->user_id)
                ->where('is_default', true)->first()
                ?? FamilyGroup::where('user_id', $share->user_id)->first();
        }

        if (! $group) {
            return redirect('/')->with('error', 'Không tìm thấy dữ liệu gia phả.');
        }

        return view('public-tree.show', compact('share', 'group'));
    }

    /** JSON tree data cho f3 chart — no auth, rate limited */
    public function data(Request $request, string $token): JsonResponse
    {
        $limitKey = 'public_tree:' . $request->ip();
        if (RateLimiter::tooManyAttempts($limitKey, 30)) {
            return response()->json(['error' => 'Quá nhiều yêu cầu.'], 429);
        }
        RateLimiter::hit($limitKey, 60);

        $share = FamilyShare::where('share_token', $token)->first();

        if (! $share || ! $share->isAccessible()) {
            return response()->json(['error' => 'Link không hợp lệ.'], 403);
        }

        // Lookup group — ưu tiên family_group_id, fallback về group mặc định của user
        $group = null;

        if ($share->family_group_id) {
            $group = FamilyGroup::find($share->family_group_id);
        }

        if (! $group) {
            $group = FamilyGroup::where('user_id', $share->user_id)
                ->where('is_default', true)->first()
                ?? FamilyGroup::where('user_id', $share->user_id)->first();
        }

        if (! $group) {
            return response()->json(['error' => 'Không tìm thấy dữ liệu gia phả.'], 404);
        }

        $members   = $group->familyMembers()->get();
        $memberIds = $members->pluck('id');

        // Preload gender map để tránh N+1 trong loop
        $genderMap = $members->pluck('gender', 'id');

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

        $nodes = $members->map(function (FamilyMember $m) use ($parentChild, $spouses, $memberIds, $genderMap) {
            $parents = $parentChild
                ->where('related_member_id', $m->id)
                ->filter(fn ($r) => $memberIds->contains($r->member_id));

            $father = $parents->first(fn ($r) => ($genderMap[$r->member_id] ?? '') === 'male');
            $mother = $parents->first(fn ($r) => ($genderMap[$r->member_id] ?? '') === 'female');

            if (! $father && ! $mother && $parents->count() === 1) {
                $father = $parents->first();
            }

            $pids = $spouses
                ->filter(fn ($r) => $r->member_id == $m->id || $r->related_member_id == $m->id)
                ->map(fn ($r) => $r->member_id == $m->id ? $r->related_member_id : $r->member_id)
                ->filter(fn ($pid) => $memberIds->contains($pid))
                ->values()->toArray();

            return [
                'id'         => $m->id,
                'fid'        => $father?->member_id ?? null,
                'mid'        => $mother?->member_id ?? null,
                'pids'       => $pids,
                'name'       => $m->name,
                'pronoun'    => $m->pronoun,
                'gender'     => $m->gender === 'female' ? 'female' : 'male',
                'birth_year' => $m->birth_year,
                'death_year' => $m->death_year,
                'is_alive'   => $m->isAlive(),
            ];
        });

        return response()->json($nodes->values());
    }
}
