<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FamilyAgent;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Tạo hoặc trả về conversation_id cho session hiện tại.
     * Client gọi trước khi stream để lấy ID.
     */
    public function conversation(Request $request): JsonResponse
    {
        $conversationId = $request->input('conversation_id');

        // Validate UUID nếu có
        if ($conversationId && ! Str::isUuid($conversationId)) {
            $conversationId = null;
        }

        // Tạo mới nếu chưa có
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

        // if (! $this->subscription->canSendAgentMessage($user)) {
        //     return response()->json([
        //         'error' => 'Bạn đã đạt giới hạn tin nhắn hôm nay. Nâng cấp để chat không giới hạn.',
        //     ], 429);
        // }

        // $this->subscription->incrementAgentMessageCount($user);

        try {
            $agent          = (new FamilyAgent)->forUser($user);
            $conversationId = $request->input('conversation_id');
            $message        = $request->input('message');

            if ($conversationId && Str::isUuid($conversationId)) {
                $agent->continue($conversationId, as: $user);
            }

            // Trả thẳng StreamableAgentResponse — SDK tự handle SSE
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
