<x-app-layout>

<div class="mb-8 text-center">
    <h1 class="text-3xl font-bold text-gray-900">Nâng cấp tài khoản</h1>
    <p class="text-gray-500 mt-2">Chọn gói phù hợp với gia đình bạn</p>
    @if ($currentPlan === 'advanced')
    <p class="mt-2 text-sm text-primary-600 font-medium">
        Bạn đang dùng gói <strong>Đại Gia Đình</strong>
        @if ($user->subscription_expires_at)
            — hết hạn {{ $user->subscription_expires_at->format('d/m/Y') }}
        @endif
    </p>
    @endif
</div>

<div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">

    {{-- BASIC — Gia Đình --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col
        {{ $currentPlan === 'basic' ? 'ring-2 ring-gray-300' : '' }}">
        <div class="mb-5">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Gia Đình</span>
            <p class="text-4xl font-bold text-gray-900 mt-1">0đ</p>
            <p class="text-sm text-gray-400 mt-0.5">Miễn phí mãi mãi</p>
        </div>

        <ul class="space-y-2.5 text-sm text-gray-600 flex-1 mb-6">
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>Gia phả <strong>không giới hạn</strong> — thành viên thoải mái</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>Ngày giỗ <strong>không giới hạn</strong></span></li>
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>Xuất PNG & SVG miễn phí</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>ZNS tự động qua Zalo — <strong>60 tin/năm</strong>, 2 số nhận</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>AI hỏi ngày giỗ, soạn văn khấn — <strong>10 tin/ngày</strong></span></li>
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>Chia sẻ chatbot gia đình (1 link)</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-500 mt-0.5 shrink-0">✓</span>
                <span>In treo tường — liên hệ Zalo báo giá</span></li>
        </ul>

        @if ($currentPlan === 'basic')
        <div class="text-center text-sm text-gray-400 font-medium py-2.5 border border-gray-200 rounded-xl">
            Gói hiện tại
        </div>
        @else
        <div class="text-center text-sm text-gray-400 py-2">Miễn phí, không cần làm gì</div>
        @endif
    </div>

    {{-- ADVANCED — Đại Gia Đình --}}
    <div class="bg-gradient-to-b from-gray-900 to-gray-800 rounded-2xl p-6 flex flex-col text-white relative
        {{ $currentPlan === 'advanced' ? 'ring-2 ring-yellow-400' : '' }}">

        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-yellow-400 text-gray-900 text-xs font-bold px-3 py-1 rounded-full">Đầy đủ nhất</span>
        </div>

        <div class="mb-5">
            <span class="text-xs font-semibold text-yellow-400 uppercase tracking-widest">Đại Gia Đình</span>
            <div class="flex items-end gap-2 mt-1">
                <p class="text-4xl font-bold">149.000đ</p>
                <p class="text-gray-400 mb-1">/ năm</p>
            </div>
            <p class="text-sm text-gray-400 mt-0.5">≈ 12.400đ/tháng — ít hơn 1 ly cà phê</p>
        </div>

        <ul class="space-y-2.5 text-sm text-gray-300 flex-1 mb-6">
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>Tất cả tính năng Gia Đình</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>ZNS — <strong class="text-white">360 tin/năm</strong>, không giới hạn số nhận</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>AI <strong class="text-white">không giới hạn</strong> — chat, văn khấn, tra cứu</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>Chia sẻ chatbot <strong class="text-white">không giới hạn</strong> link</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>Upload tài liệu gia đình (AI tự trả lời)</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>Nhập ngày giỗ từ ảnh / scan</span></li>
            <li class="flex gap-2 items-start"><span class="text-green-400 mt-0.5 shrink-0">✓</span>
                <span>Nhắc Rằm & Mùng 1 hàng tháng</span></li>
        </ul>

        @if ($currentPlan === 'advanced')
        <div class="text-center text-sm text-yellow-400 font-medium py-2.5 border border-yellow-400/30 rounded-xl">
            Gói hiện tại
        </div>
        @else
        <form method="POST" action="{{ route('payment.create') }}">
            @csrf
            <input type="hidden" name="plan" value="advanced">
            <button type="submit"
                    class="block w-full text-center py-3 px-4 bg-yellow-400 hover:bg-yellow-300 text-gray-900 text-sm font-bold rounded-xl transition-colors shadow-lg shadow-yellow-400/20">
                Nâng cấp — 149.000đ/năm
            </button>
        </form>
        @endif
    </div>

</div>

{{-- Footer note --}}
<div class="mt-8 max-w-2xl mx-auto">
    <div class="text-center text-xs text-gray-400 space-y-1">
        <p>Thanh toán qua chuyển khoản ngân hàng · Kích hoạt trong vài phút</p>
        <p>Câu hỏi? Nhắn
            <a href="{{ env('ZALO_CONTACT_URL', '#') }}" target="_blank"
               class="text-blue-500 hover:underline">Zalo hỗ trợ</a>
        </p>
    </div>
</div>

</x-app-layout>
