<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SepayController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscription) {}

    /**
     * SePay POST webhook khi có giao dịch mới.
     *
     * Payload mẫu:
     * {
     *   "id": 12345,
     *   "gateway": "MBBank",
     *   "transactionDate": "2026-04-29 10:00:00",
     *   "accountNumber": "1234567890",
     *   "subAccount": null,
     *   "code": "GP123456",           ← reference từ nội dung chuyển khoản
     *   "content": "GPMINI GP123456", ← full content
     *   "transferType": "in",
     *   "transferAmount": 100000,
     *   "accumulated": 0,
     *   "referenceCode": "FT26...",
     *   "description": ""
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        // Xác thực webhook token
        $token = $request->header('Authorization');
        $expected = 'Apikey ' . config('services.sepay.webhook_token');

        if ($token !== $expected) {
            Log::warning('SePay webhook: invalid token', ['all' => $request->all()]);
            return response()->json(['success' => false], 401);
        }

        $payload = $request->all();
        Log::info('SePay webhook received', $payload);

        // Chỉ xử lý giao dịch chuyển tiền vào
        if (($payload['transferType'] ?? '') !== 'in') {
            return response()->json(['success' => true]);
        }

        $transactionId = $payload['id'] ?? null;
        $amount        = (int) ($payload['transferAmount'] ?? 0);
        $content       = strtoupper($payload['content'] ?? '');

        if (! $transactionId || ! $amount) {
            return response()->json(['success' => true]);
        }

        // Idempotency: đã xử lý transaction này chưa?
        if (PaymentOrder::where('sepay_transaction_id', $transactionId)->exists()) {
            return response()->json(['success' => true]);
        }

        // Tìm reference code trong nội dung chuyển khoản
        // Pattern: GP + 6 alphanum chars (VD: GP1A2B3C)
        preg_match('/GP[A-Z0-9]{6}/', $content, $matches);
        $referenceCode = $matches[0] ?? null;

        if (! $referenceCode) {
            Log::info('SePay: no reference code found in content', ['content' => $content]);
            return response()->json(['success' => true]);
        }

        // Tìm order tương ứng
        $order = PaymentOrder::where('reference_code', $referenceCode)
            ->where('status', 'pending')
            ->where('amount', $amount)     // kiểm tra đúng số tiền
            ->where('expires_at', '>', now())
            ->first();

        if (! $order) {
            Log::warning('SePay: no matching order', [
                'reference' => $referenceCode,
                'amount'    => $amount,
            ]);
            return response()->json(['success' => true]);
        }

        // Kích hoạt subscription
        $this->activateSubscription($order, $transactionId, $payload);

        return response()->json(['success' => true]);
    }

    private function activateSubscription(PaymentOrder $order, mixed $transactionId, array $payload): void
    {
        // Cập nhật order
        $order->update([
            'status'               => 'completed',
            'sepay_transaction_id' => $transactionId,
            'sepay_payload'        => json_encode($payload),
            'paid_at'              => now(),
        ]);

        // Kích hoạt / gia hạn subscription cho user
        $user      = $order->user;
        $expiresAt = ($user->subscription_expires_at?->isFuture()
            ? $user->subscription_expires_at
            : now()
        )->addYear();

        $user->update([
            'subscription_plan'       => $order->plan,
            'subscription_expires_at' => $expiresAt,
        ]);

        // Lưu lịch sử
        Subscription::create([
            'user_id'      => $user->id,
            'plan'         => $order->plan,
            'amount'       => $order->amount,
            'note'         => "SePay #{$transactionId}",
            'activated_by' => 'sepay_webhook',
            'starts_at'    => now(),
            'expires_at'   => $expiresAt,
        ]);

        Log::info('Subscription activated', [
            'user_id'    => $user->id,
            'plan'       => $order->plan,
            'expires_at' => $expiresAt,
        ]);
    }
}
