<x-app-layout>

<div class="max-w-md mx-auto" x-data="paymentPage('{{ route('payment.status', $order) }}')" x-init="startPolling()">

    {{-- Header --}}
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Thanh toán gói {{ $order->planLabel() }}</h1>
        <p class="text-gray-500 mt-1">Chuyển khoản ngân hàng để kích hoạt ngay</p>
    </div>

    {{-- Status banner --}}
    <div x-show="completed" x-cloak
        class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-center text-green-800 font-semibold">
        ✅ Thanh toán thành công! Đang chuyển hướng...
    </div>

    {{-- QR Card --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- QR Code --}}
        <div class="p-6 text-center bg-gray-50 border-b border-gray-100">
            <img src="{{ $order->qrUrl() }}"
                alt="QR thanh toán"
                class="w-56 h-56 mx-auto rounded-xl shadow-sm object-cover"
                onerror="this.src='/images/qr-placeholder.png'">
            <p class="text-xs text-gray-400 mt-2">Quét bằng app ngân hàng bất kỳ</p>
        </div>

        {{-- Payment details --}}
        <div class="p-5 space-y-3">
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Số tiền</span>
                <span class="font-bold text-lg text-gray-900">{{ $order->formattedAmount() }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Nội dung CK</span>
                <div class="flex items-center gap-2">
                    <span class="font-mono font-bold text-primary-700 text-base tracking-wider">
                        {{ $order->reference_code }}
                    </span>
                    <button @click="copyRef()" title="Copy"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Ngân hàng</span>
                <span class="text-sm font-medium text-gray-700">{{ strtoupper(config('services.sepay.bank_code')) }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-500">Số TK</span>
                <span class="text-sm font-mono font-medium text-gray-700">{{ config('services.sepay.bank_account') }}</span>
            </div>
        </div>

        {{-- Warning --}}
        <div class="px-5 pb-4">
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 text-xs text-amber-800">
                ⚠️ Ghi đúng nội dung <strong>{{ $order->reference_code }}</strong> để hệ thống tự động xác nhận.
                Sai nội dung sẽ không được kích hoạt tự động.
            </div>
        </div>

        {{-- Countdown --}}
        <div class="px-5 pb-5">
            <div class="flex items-center justify-between text-xs text-gray-400 mb-1.5">
                <span>Đơn hàng hết hạn sau</span>
                <span x-text="countdown" class="font-mono font-medium text-gray-600"></span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-primary-500 h-1.5 rounded-full transition-all duration-1000"
                    :style="'width: ' + progressPct + '%'"></div>
            </div>
        </div>

        {{-- Polling indicator --}}
        <div class="px-5 pb-5 flex items-center justify-center gap-2 text-xs text-gray-400">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            Đang tự động kiểm tra thanh toán...
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('upgrade') }}" class="text-sm text-gray-400 hover:text-gray-600 hover:underline">
            ← Quay lại chọn gói khác
        </a>
    </div>
</div>

@push('scripts')
<script>
function paymentPage(statusUrl) {
    const expiresAt   = new Date('{{ $order->expires_at->toIso8601String() }}');
    const createdAt   = new Date('{{ $order->created_at->toIso8601String() }}');
    const totalMs     = expiresAt - createdAt;

    return {
        completed:   false,
        copied:      false,
        countdown:   '',
        progressPct: 100,
        timer:       null,
        pollTimer:   null,

        startPolling() {
            this.updateCountdown();
            this.timer = setInterval(() => this.updateCountdown(), 1000);
            // Poll SePay status mỗi 5 giây
            this.pollTimer = setInterval(() => this.checkStatus(), 5000);
        },

        updateCountdown() {
            const now      = new Date();
            const diffMs   = expiresAt - now;
            if (diffMs <= 0) {
                this.countdown   = 'Hết hạn';
                this.progressPct = 0;
                clearInterval(this.timer);
                clearInterval(this.pollTimer);
                return;
            }
            const h = Math.floor(diffMs / 3600000);
            const m = Math.floor((diffMs % 3600000) / 60000);
            const s = Math.floor((diffMs % 60000) / 1000);
            this.countdown   = `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            this.progressPct = Math.max(0, (diffMs / totalMs) * 100);
        },

        async checkStatus() {
            try {
                const res  = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.completed) {
                    this.completed = true;
                    clearInterval(this.pollTimer);
                    setTimeout(() => { window.location.href = data.redirect; }, 2000);
                }
                if (data.expired) {
                    clearInterval(this.pollTimer);
                }
            } catch (e) { /* ignore network error, retry next tick */ }
        },

        copyRef() {
            navigator.clipboard.writeText('{{ $order->reference_code }}');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
    };
}
</script>
@endpush

</x-app-layout>
