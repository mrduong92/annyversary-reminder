<x-app-layout>
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="printOrderPage()">

    {{-- Header --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Xuất & In gia phả</h1>
            <p class="mt-1 text-sm text-gray-500">Chọn mẫu thiết kế, tải về hoặc đặt in.</p>
        </div>
        <a href="{{ route('genealogy.index') }}"
           class="mt-4 sm:mt-0 inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            &larr; Quay lại gia phả
        </a>
    </div>

    {{-- Flash success --}}
    @if(session('success'))
    <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->has('unlock'))
    <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ $errors->first('unlock') }}
    </div>
    @endif

    {{-- Bước 1: Chọn mẫu --}}
    <div class="bg-white shadow sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">1. Chọn mẫu thiết kế</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($templates as $template)
                <label class="relative flex cursor-pointer rounded-xl border-2 bg-white p-5 shadow-sm transition-all hover:shadow-md
                              {{ $selectedTemplate && $selectedTemplate->id == $template['id'] ? 'border-primary-600 bg-primary-50' : 'border-gray-200' }}">
                    <input type="radio" name="template_selector" value="{{ $template['id'] }}" class="sr-only"
                           onchange="window.location.href='?template_id={{ $template['id'] }}'"
                           {{ $selectedTemplate && $selectedTemplate->id == $template['id'] ? 'checked' : '' }}>
                    <div class="flex flex-1 flex-col gap-3">
                        <span class="text-sm font-semibold text-gray-900
                                     {{ $selectedTemplate && $selectedTemplate->id == $template['id'] ? 'text-primary-700' : '' }}">
                            {{ $template['name'] }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $template['description'] }}</span>
                        <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold transition-all
                                     {{ $selectedTemplate && $selectedTemplate->id == $template['id'] ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                            {{ $selectedTemplate && $selectedTemplate->id == $template['id'] ? 'Đang chọn ✓' : 'Chọn mẫu' }}
                        </span>
                    </div>
                    @if($selectedTemplate && $selectedTemplate->id == $template['id'])
                    <svg class="h-5 w-5 text-primary-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                </label>
                @endforeach
            </div>
        </div>
    </div>

    @if($selectedTemplate && $svg)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cột trái: Actions --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- ===== DOWNLOAD SECTION ===== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">2. Tải về</h3>
                    <p class="text-xs text-gray-500 mb-4">File chất lượng cao, in được tại bất kỳ tiệm in nào.</p>

                    @if($isUnlocked)
                    {{-- ĐÃ MỞ KHÓA --}}
                    <div class="mb-4 flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        <span>Đã mở khóa</span>
                    </div>
                    <form action="{{ route('print-orders.download') }}" method="GET">
                        <input type="hidden" name="template_id" value="{{ $selectedTemplate->id }}">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-sm transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Tải về ngay
                        </button>
                    </form>
                    <p class="mt-2 text-xs text-gray-400 text-center">
                        Nếu chỉnh sửa gia phả sau này, bạn sẽ cần mở khóa lại.
                    </p>

                    @else
                    {{-- CHƯA MỞ KHÓA --}}
                    @if($familyGroup->downloadUnlocks()->where('user_id', Auth::id())->exists())
                    <div class="mb-4 flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span>Gia phả đã được chỉnh sửa — cần mở khóa lại</span>
                    </div>
                    @endif

                    <div class="mb-4 flex items-center gap-2 text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z M9 11V7a3 3 0 016 0v4"/>
                        </svg>
                        <span>Chưa mở khóa</span>
                    </div>
                    <button type="button" @click="openUnlockModal()"
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        Mở khóa tải về &mdash; 49.000đ
                    </button>
                    <ul class="mt-3 space-y-1 text-xs text-gray-500">
                        <li class="flex items-center gap-1.5"><span class="text-primary-500">✓</span> File PDF độ phân giải cao</li>
                        <li class="flex items-center gap-1.5"><span class="text-primary-500">✓</span> In được tại bất kỳ tiệm in</li>
                        <li class="flex items-center gap-1.5"><span class="text-primary-500">✓</span> Tải lại thoải mái</li>
                    </ul>
                    @endif
                </div>
            </div>

            {{-- ===== PRINT REQUEST SECTION ===== --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Muốn đặt in?</h3>
                    <p class="text-xs text-gray-500 mb-4">Chúng tôi tư vấn và báo giá theo kích thước, chất liệu, địa chỉ giao hàng của bạn.</p>
                    <button type="button" @click="openZaloModal()"
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold shadow-sm transition-colors">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.477 2 2 6.145 2 11.243c0 2.908 1.436 5.507 3.686 7.237l-.47 3.52 3.843-1.962c.934.258 1.928.397 2.941.397 5.523 0 10-4.144 10-9.243C22 6.145 17.523 2 12 2z"/>
                        </svg>
                        Liên hệ nhận báo giá
                    </button>
                </div>
            </div>

        </div>

        {{-- Cột phải: Preview SVG --}}
        <div class="lg:col-span-2">
            {{-- Zoom controls (vanilla JS — không dùng Alpine scope để tránh conflict) --}}
            <div class="flex items-center justify-between mb-2 px-1">
                <p class="text-xs text-gray-400">Dùng nút để zoom, kéo ngang để xem full.</p>
                <div class="flex items-center gap-1">
                    <button onclick="svgZoom(-0.25)"
                            class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-base leading-none select-none">−</button>
                    <span id="svg-zoom-label" class="text-xs text-gray-500 w-12 text-center tabular-nums">100%</span>
                    <button onclick="svgZoom(+0.25)"
                            class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-base leading-none select-none">+</button>
                    <button onclick="svgZoomReset()"
                            class="ml-1 px-2 h-7 text-xs rounded bg-gray-100 hover:bg-gray-200 text-gray-600 select-none">Fit</button>
                </div>
            </div>
            {{-- Scrollable preview --}}
            <div class="bg-white shadow sm:rounded-lg overflow-auto" style="height: 720px;">
                <img id="svg-preview-img" src="{{ $svg }}"
                     alt="Bản xem trước gia phả" style="width:100%; display:block; min-width:100%;">
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">Bản xem trước — file tải về có độ phân giải cao hơn.</p>
        </div>
    </div>
    @endif

    {{-- ===== MODAL: MỞ KHÓA DOWNLOAD ===== --}}
    <div x-show="showUnlockModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="closeUnlockModal()"></div>
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl" @click.stop>
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Mở khóa tải về</h3>
                <button @click="closeUnlockModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-gray-600">Chuyển khoản <span class="font-bold text-gray-900">49.000đ</span> với nội dung bên dưới, sau đó nhấn xác nhận.</p>

                {{-- Thông tin tài khoản --}}
                <div class="rounded-xl bg-gray-50 border border-gray-200 divide-y divide-gray-200 text-sm">
                    <div class="flex justify-between px-4 py-2.5">
                        <span class="text-gray-500">Ngân hàng</span>
                        <span class="font-medium text-gray-900">{{ config('payment.bank_name', 'MB Bank') }}</span>
                    </div>
                    <div class="flex justify-between px-4 py-2.5">
                        <span class="text-gray-500">Số tài khoản</span>
                        <span class="font-medium text-gray-900 font-mono select-all">{{ config('payment.bank_account', '0123456789') }}</span>
                    </div>
                    <div class="flex justify-between px-4 py-2.5">
                        <span class="text-gray-500">Chủ tài khoản</span>
                        <span class="font-medium text-gray-900">{{ config('payment.bank_owner', 'DUONG TIEN DAT') }}</span>
                    </div>
                    <div class="flex justify-between px-4 py-2.5">
                        <span class="text-gray-500">Số tiền</span>
                        <span class="font-bold text-primary-600">49.000đ</span>
                    </div>
                    <div class="flex justify-between px-4 py-2.5">
                        <span class="text-gray-500">Nội dung CK</span>
                        <span class="font-medium text-gray-900 font-mono select-all">GIPHA {{ strtoupper(substr(md5(Auth::id() . now()->format('Y-m-d')), 0, 6)) }}</span>
                    </div>
                </div>

                <form action="{{ route('print-orders.confirm-unlock') }}" method="POST">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $selectedTemplate?->id }}">
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú (tuỳ chọn)</label>
                        <input type="text" name="payment_note" placeholder="VD: Đã CK lúc 14:30"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold transition-colors">
                        Tôi đã chuyển khoản — Mở khóa
                    </button>
                </form>
                <p class="text-xs text-gray-400 text-center">
                    Chúng tôi kiểm tra thủ công trong vòng vài phút. Nếu chưa nhận được file, liên hệ Zalo để được hỗ trợ.
                </p>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: ĐẶT IN / LIÊN HỆ ZALO ===== --}}
    <div x-show="showZaloModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="closeZaloModal()"></div>
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl text-center" @click.stop>
            <div class="px-6 pt-8 pb-6">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-8 w-8 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.477 2 2 6.145 2 11.243c0 2.908 1.436 5.507 3.686 7.237l-.47 3.52 3.843-1.962c.934.258 1.928.397 2.941.397 5.523 0 10-4.144 10-9.243C22 6.145 17.523 2 12 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Đặt in gia phả</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Báo giá phụ thuộc kích thước, chất liệu và địa chỉ giao hàng của bạn.
                    Nhắn Zalo để chúng tôi tư vấn và báo giá chính xác nhất.
                </p>
                <a href="{{ config('contact.zalo_url', 'https://zalo.me/') }}" target="_blank" rel="noopener"
                   class="w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold transition-colors shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.477 2 2 6.145 2 11.243c0 2.908 1.436 5.507 3.686 7.237l-.47 3.52 3.843-1.962c.934.258 1.928.397 2.941.397 5.523 0 10-4.144 10-9.243C22 6.145 17.523 2 12 2z"/>
                    </svg>
                    Chat Zalo ngay
                </a>
                <button @click="closeZaloModal()" class="mt-3 w-full py-2 text-sm text-gray-500 hover:text-gray-700">
                    Để sau
                </button>
            </div>
        </div>
    </div>

</div>

<script>
// ── SVG zoom — vanilla JS (không dùng Alpine scope để tránh conflict) ──────
let _svgZoom = 1;
function svgZoom(delta) {
    _svgZoom = Math.min(4, Math.max(0.25, _svgZoom + delta));
    const img   = document.getElementById('svg-preview-img');
    const label = document.getElementById('svg-zoom-label');
    if (img)   img.style.width = (_svgZoom * 100) + '%';
    if (label) label.textContent = Math.round(_svgZoom * 100) + '%';
}
function svgZoomReset() {
    _svgZoom = 1;
    const img   = document.getElementById('svg-preview-img');
    const label = document.getElementById('svg-zoom-label');
    if (img)   img.style.width = '100%';
    if (label) label.textContent = '100%';
}

function printOrderPage() {
    return {
        showUnlockModal: false,
        showZaloModal: false,
        openUnlockModal()  { this.showUnlockModal = true; },
        closeUnlockModal() { this.showUnlockModal = false; },
        openZaloModal()    { this.showZaloModal = true; },
        closeZaloModal()   { this.showZaloModal = false; },
    };
}
</script>
</x-app-layout>
