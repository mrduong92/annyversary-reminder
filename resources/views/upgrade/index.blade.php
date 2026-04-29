<x-app-layout>

<div class="mb-8 text-center">
    <h1 class="text-3xl font-bold text-gray-900">Nâng cấp tài khoản</h1>
    <p class="text-gray-500 mt-2">Chọn gói phù hợp để không bao giờ quên ngày giỗ</p>
    @if ($currentPlan !== 'free')
    <p class="mt-2 text-sm text-primary-600 font-medium">
        Bạn đang dùng gói <strong>{{ strtoupper($currentPlan) }}</strong>
        @if ($user->subscription_expires_at)
            — hết hạn {{ $user->subscription_expires_at->format('d/m/Y') }}
        @endif
    </p>
    @endif
</div>

<div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">

    {{-- FREE --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col
        {{ $currentPlan === 'free' ? 'ring-2 ring-gray-300' : '' }}">
        <div class="mb-4">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Free</span>
            <p class="text-3xl font-bold text-gray-900 mt-1">0đ</p>
            <p class="text-sm text-gray-400">Mãi mãi</p>
        </div>
        <ul class="space-y-2.5 text-sm text-gray-600 flex-1 mb-6">
            <li class="flex gap-2"><span class="text-green-500">✓</span> Gia phả không giới hạn</li>
            <li class="flex gap-2"><span class="text-green-500">✓</span> Ngày giỗ không giới hạn</li>
            <li class="flex gap-2"><span class="text-green-500">✓</span> AI hỏi ngày giỗ, tra cứu</li>
            <li class="flex gap-2"><span class="text-green-500">✓</span> 1 người nhận ZNS</li>
            <li class="flex gap-2 text-gray-300"><span>✗</span> Nhắc Rằm, Mùng 1</li>
            <li class="flex gap-2 text-gray-300"><span>✗</span> Soạn văn khấn AI</li>
            <li class="flex gap-2 text-gray-300"><span>✗</span> Chia sẻ chatbot gia đình</li>
        </ul>
        @if ($currentPlan === 'free')
        <div class="text-center text-sm text-gray-400 font-medium py-2">Gói hiện tại</div>
        @else
        <div class="text-center text-sm text-gray-400 py-2">Miễn phí</div>
        @endif
    </div>

    {{-- MINI --}}
    <div class="bg-white rounded-2xl border-2 border-primary-400 p-6 flex flex-col relative
        {{ $currentPlan === 'mini' ? 'ring-2 ring-primary-500' : '' }}">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full">Phổ biến nhất</span>
        </div>
        <div class="mb-4">
            <span class="text-xs font-semibold text-primary-600 uppercase tracking-wide">Mini</span>
            <p class="text-3xl font-bold text-gray-900 mt-1">100.000đ</p>
            <p class="text-sm text-gray-400">/ năm</p>
        </div>
        <ul class="space-y-2.5 text-sm text-gray-600 flex-1 mb-6">
            <li class="flex gap-2"><span class="text-green-500">✓</span> Tất cả tính năng Free</li>
            <li class="flex gap-2"><span class="text-green-500">✓</span> 3 người nhận ZNS</li>
            <li class="flex gap-2"><span class="text-green-500">✓</span> AI soạn văn khấn</li>
            <li class="flex gap-2"><span class="text-green-500">✓</span> AI tạo/sửa ngày giỗ qua chat</li>
            <li class="flex gap-2 text-gray-300"><span>✗</span> Nhắc Rằm, Mùng 1</li>
            <li class="flex gap-2 text-gray-300"><span>✗</span> Chia sẻ chatbot gia đình</li>
            <li class="flex gap-2 text-gray-300"><span>✗</span> Upload tài liệu gia đình</li>
        </ul>
        @if ($currentPlan === 'mini')
        <div class="text-center text-sm text-primary-600 font-medium py-2">Gói hiện tại</div>
        @else
        <form method="POST" action="{{ route('payment.create') }}">
            @csrf
            <input type="hidden" name="plan" value="mini">
            <button type="submit"
                class="block w-full text-center py-2.5 px-4 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors">
                Mua ngay — 100.000đ/năm
            </button>
        </form>
        @endif
    </div>

    {{-- PREMIUM --}}
    <div class="bg-gradient-to-b from-gray-900 to-gray-800 rounded-2xl p-6 flex flex-col text-white
        {{ $currentPlan === 'premium' ? 'ring-2 ring-yellow-400' : '' }}">
        <div class="mb-4">
            <span class="text-xs font-semibold text-yellow-400 uppercase tracking-wide">Premium</span>
            <p class="text-3xl font-bold mt-1">200.000đ</p>
            <p class="text-sm text-gray-400">/ năm</p>
        </div>
        <ul class="space-y-2.5 text-sm text-gray-300 flex-1 mb-6">
            <li class="flex gap-2"><span class="text-green-400">✓</span> Tất cả tính năng Mini</li>
            <li class="flex gap-2"><span class="text-green-400">✓</span> Nhắc Rằm & Mùng 1</li>
            <li class="flex gap-2"><span class="text-green-400">✓</span> Chia sẻ chatbot cho cả nhà</li>
            <li class="flex gap-2"><span class="text-green-400">✓</span> Upload tài liệu gia đình (RAG)</li>
            <li class="flex gap-2"><span class="text-green-400">✓</span> Nhập ngày giỗ từ ảnh</li>
            <li class="flex gap-2"><span class="text-green-400">✓</span> AI không giới hạn</li>
        </ul>
        @if ($currentPlan === 'premium')
        <div class="text-center text-sm text-yellow-400 font-medium py-2">Gói hiện tại</div>
        @else
        <form method="POST" action="{{ route('payment.create') }}">
            @csrf
            <input type="hidden" name="plan" value="premium">
            <button type="submit"
                class="block w-full text-center py-2.5 px-4 bg-yellow-400 hover:bg-yellow-300 text-gray-900 text-sm font-semibold rounded-xl transition-colors">
                Mua ngay — 200.000đ/năm
            </button>
        </form>
        @endif
    </div>
</div>

<div class="mt-8 max-w-xl mx-auto text-center text-xs text-gray-400">
    Thanh toán qua chuyển khoản ngân hàng · Kích hoạt tự động trong vài phút · Hỗ trợ: support@giaphong.vn
</div>



</x-app-layout>
