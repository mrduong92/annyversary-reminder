<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FamilyAgent;
use App\Models\FamilyShare;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\RateLimitedException;

class ShareController extends Controller
{
    // ── Owner: toggle share link của group hiện tại ───────────

    /**
     * Bật/tạo share link cho group đang active.
     * Trả JSON để chat UI có thể update inline mà không reload.
     */
    public function enable(Request $request): JsonResponse
    {
        if (Gate::denies('share-chat')) {
            return response()->json([
                'error'   => 'Tính năng chia sẻ chatbot chỉ dành cho gói Premium.',
                'upgrade' => true,
            ], 403);
        }

        $group = active_group();
        $share = $group->getOrCreateShare();

        if (! $share->is_active) {
            $share->update(['is_active' => true]);
        }

        return response()->json([
            'url'     => route('share.public', $share->share_token),
            'enabled' => true,
        ]);
    }

    /** Tắt share link (revoke) nhưng không xóa token */
    public function disable(Request $request): JsonResponse
    {
        $group = active_group();
        $share = $group->activeShare();

        if ($share) {
            $share->update(['is_active' => false]);
        }

        return response()->json(['enabled' => false]);
    }

    // ── Public: trang chat cho người được share ────────────────

    public function show(string $token): \Illuminate\View\View|RedirectResponse
    {
        $share = FamilyShare::where('share_token', $token)->first();

        if (! $share || ! $share->isAccessible()) {
            return redirect('/')->with('error', 'Link chia sẻ không tồn tại hoặc đã hết hạn.');
        }

        $share->increment('access_count');

        return view('share.public', compact('share'));
    }

    // ── Public stream (không cần auth, rate limited) ───────────

    public function stream(Request $request, string $token): Responsable|JsonResponse
    {
        $limitKey = 'share_stream:' . $request->ip();
        if (RateLimiter::tooManyAttempts($limitKey, 20)) {
            return response()->json(['error' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'], 429);
        }
        RateLimiter::hit($limitKey, 60);

        $request->validate([
            'message'         => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $share = FamilyShare::where('share_token', $token)->first();

        if (! $share || ! $share->isAccessible()) {
            return response()->json(['error' => 'Link chia sẻ không hợp lệ.'], 404);
        }

        $owner = $share->user;

        try {
            $agent          = (new FamilyAgent)->forUser($owner, readOnly: true, familyGroupId: $share->family_group_id);
            $conversationId = $request->input('conversation_id');

            if ($conversationId && Str::isUuid($conversationId)) {
                $agent->continue($conversationId, as: $owner);
            }

            return $agent->stream($request->input('message'));

        } catch (RateLimitedException) {
            return response()->json(['error' => 'AI đang bận, vui lòng thử lại sau vài giây.'], 429);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Có lỗi xảy ra. Vui lòng thử lại.'], 500);
        }
    }

    // ── Public history ─────────────────────────────────────────

    public function history(Request $request, string $token): JsonResponse
    {
        $conversationId = $request->input('conversation_id');

        if (! $conversationId || ! Str::isUuid($conversationId)) {
            return response()->json(['messages' => []]);
        }

        $records = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get(['role', 'content', 'created_at']);

        $messages = $records
            ->filter(fn ($r) => in_array($r->role, ['user', 'assistant']))
            ->map(fn ($r) => [
                'role'    => $r->role,
                'content' => $r->content,
                'time'    => \Carbon\Carbon::parse($r->created_at)
                                ->timezone('Asia/Ho_Chi_Minh')
                                ->format('H:i'),
            ])
            ->values();

        return response()->json(['messages' => $messages]);
    }
}
