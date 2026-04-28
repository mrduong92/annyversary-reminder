<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FamilyAgent;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Ai\Exceptions\RateLimitedException;

class AgentController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscription) {}

    public function index(): View
    {
        return view('agent.chat');
    }

    /**
     * Trả về lịch sử messages của một conversation.
     * Client gọi khi init để khôi phục chat sau reload.
     */
    public function history(Request $request): JsonResponse
    {
        $conversationId = $request->input('conversation_id');

        if (! $conversationId || ! Str::isUuid($conversationId)) {
            return response()->json(['messages' => []]);
        }

        $records = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('user_id', Auth::id())
            ->orderBy('created_at')
            ->get(['role', 'content', 'tool_calls', 'created_at']);

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

    /**
     * Tạo hoặc xác nhận conversation_id.
     */
    public function conversation(Request $request): JsonResponse
    {
        $conversationId = $request->input('conversation_id');

        if ($conversationId && ! Str::isUuid($conversationId)) {
            $conversationId = null;
        }

        if (! $conversationId) {
            $conversationId = (string) Str::uuid();
        }

        return response()->json(['conversation_id' => $conversationId]);
    }

    public function stream(Request $request): Responsable|JsonResponse
    {
        $request->validate([
            'message'         => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        if (! $this->subscription->canSendAgentMessage($user)) {
            return response()->json([
                'error' => 'Bạn đã đạt giới hạn tin nhắn hôm nay. Nâng cấp để chat không giới hạn.',
            ], 429);
        }

        $this->subscription->incrementAgentMessageCount($user);

        try {
            $agent          = (new FamilyAgent)->forUser($user, familyGroupId: active_group()->id);
            $conversationId = $request->input('conversation_id');
            $message        = $request->input('message');

            if ($conversationId && Str::isUuid($conversationId)) {
                $agent->continue($conversationId, as: $user);
            }

            return $agent->stream($message);

        } catch (RateLimitedException) {
            return response()->json([
                'error' => 'AI đang bận, vui lòng thử lại sau vài giây.',
            ], 429);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'Có lỗi xảy ra khi kết nối AI. Vui lòng thử lại.',
            ], 500);
        }
    }
}
