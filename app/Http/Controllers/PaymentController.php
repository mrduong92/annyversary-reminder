<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const PRICES = [
        'advanced' => 149000,
    ];

    public function __construct(private readonly SubscriptionService $subscription) {}

    /** Tạo order và chuyển đến trang QR */
    public function create(Request $request): RedirectResponse
    {
        $request->validate(['plan' => ['required', 'in:advanced']]);

        $plan   = $request->input('plan');
        $user   = Auth::user();
        $amount = self::PRICES[$plan];

        // Hủy các pending orders cũ của user cho plan này
        PaymentOrder::where('user_id', $user->id)
            ->where('plan', $plan)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $order = PaymentOrder::create([
            'user_id'        => $user->id,
            'plan'           => $plan,
            'amount'         => $amount,
            'reference_code' => PaymentOrder::generateReference(),
            'status'         => 'pending',
            'expires_at'     => now()->addHours(48),
        ]);

        return redirect()->route('payment.show', $order);
    }

    /** Trang QR + countdown */
    public function show(PaymentOrder $order): View|RedirectResponse
    {
        abort_if($order->user_id !== Auth::id(), 403);

        if ($order->isCompleted()) {
            return redirect()->route('upgrade')->with('success', "Đã kích hoạt gói {$order->planLabel()} thành công! 🎉");
        }

        if ($order->isExpired()) {
            return redirect()->route('upgrade')->with('error', 'Đơn hàng đã hết hạn. Vui lòng tạo đơn mới.');
        }

        return view('upgrade.payment', compact('order'));
    }

    /** API polling — chat UI gọi mỗi 5s để check */
    public function status(PaymentOrder $order): JsonResponse
    {
        abort_if($order->user_id !== Auth::id(), 403);

        $order->refresh();

        return response()->json([
            'status'    => $order->status,
            'completed' => $order->isCompleted(),
            'expired'   => $order->isExpired(),
            'redirect'  => $order->isCompleted() ? route('upgrade') : null,
        ]);
    }

    /** Xử lý khi order hết hạn (cron hàng giờ) */
    public static function expireOldOrders(): void
    {
        PaymentOrder::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
