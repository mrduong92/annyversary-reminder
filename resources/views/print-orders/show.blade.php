<x-app-layout>
    <div class="max-w-4xl mx-auto py-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Chi tiết đơn đặt in #{{ $order->id }}</h1>
                <p class="mt-1 text-sm text-gray-500">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="{{ route('print-orders.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                &larr; Tạo đơn mới
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Trạng thái đơn hàng</h3>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium 
                    @if($order->status == 'pending') bg-yellow-100 text-yellow-800 
                    @elseif($order->status == 'processing') bg-blue-100 text-blue-800 
                    @elseif($order->status == 'shipped') bg-purple-100 text-purple-800 
                    @elseif($order->status == 'delivered') bg-green-100 text-green-800 
                    @else bg-gray-100 text-gray-800 @endif">
                    @if($order->status == 'pending') Chờ xử lý
                    @elseif($order->status == 'processing') Đang in ấn
                    @elseif($order->status == 'shipped') Đang giao hàng
                    @elseif($order->status == 'delivered') Đã giao
                    @else Đã huỷ @endif
                </span>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Mẫu gia phả</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $order->template->name }}</dd>
                    </div>
                    
                    @php
                        $printOptions = config('print_options');
                        $orderTypeName = '';
                        $orderTypeDesc = '';
                        if ($order->order_type === 'digital') {
                            $orderTypeName = $printOptions['digital']['name'];
                            $orderTypeDesc = $printOptions['digital']['description'];
                        } else {
                            foreach ($printOptions['physical_options'] as $opt) {
                                if ($opt['id'] === $order->order_type) {
                                    $orderTypeName = $opt['name'];
                                    $orderTypeDesc = $opt['description'];
                                    break;
                                }
                            }
                        }
                    @endphp
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Phương thức In Ấn</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="font-medium text-primary-600">{{ $orderTypeName }}</span>
                            @if($orderTypeDesc)
                            <p class="mt-1 text-xs text-gray-500">{{ $orderTypeDesc }}</p>
                            @endif
                        </dd>
                    </div>

                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tổng thanh toán</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 sm:mt-0 sm:col-span-2">{{ $order->total_price == 0 ? 'Miễn phí' : number_format($order->total_price) . 'đ' }}</dd>
                    </div>
                    @if($order->tracking_number)
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Mã vận đơn</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $order->tracking_number }} ({{ $order->shipping_company }})
                        </dd>
                    </div>
                    @endif
                    
                    @if($order->order_type !== 'digital')
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Thông tin nhận hàng</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <p class="font-medium">{{ $order->shipping_name }} - {{ $order->shipping_phone }}</p>
                            <p class="mt-1 text-gray-500">{{ $order->shipping_address }}</p>
                        </dd>
                    </div>
                    @endif
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">File gia phả (PDF)</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($order->pdf_path)
                            <a href="{{ $order->pdf_path }}" target="_blank" class="inline-flex items-center gap-1.5 font-medium text-primary-600 hover:text-primary-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Tải xuống PDF
                            </a>
                            @else
                            <span class="text-gray-500">Đang tạo PDF...</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        
        @if($order->status == 'pending' && $order->total_price > 0)
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Thanh toán trực tuyến</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Vui lòng quét mã QR dưới đây để thanh toán cho đơn hàng.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6 text-center">
                @php
                    $acc = '0123456789'; // Thay bằng tài khoản thật
                    $bank = 'MBBank'; // Thay bằng ngân hàng thật
                    $amount = $order->total_price;
                    $des = 'GP' . $order->id;
                    $qrUrl = "https://qr.sepay.vn/img?acc={$acc}&bank={$bank}&amount={$amount}&des={$des}";
                @endphp
                <img src="{{ $qrUrl }}" alt="Mã QR Thanh Toán" class="mx-auto w-64 h-64 border p-2 rounded-lg shadow-sm">
                
                <div class="mt-6 text-sm text-gray-700 bg-gray-50 p-4 rounded-lg inline-block text-left">
                    <p><strong>Ngân hàng:</strong> {{ $bank }}</p>
                    <p><strong>Số tài khoản:</strong> {{ $acc }}</p>
                    <p><strong>Số tiền:</strong> {{ number_format($amount) }}đ</p>
                    <p><strong>Nội dung CK:</strong> <span class="font-mono bg-white px-2 py-1 border rounded">{{ $des }}</span></p>
                </div>
                <p class="mt-4 text-xs text-gray-500 italic">*Hệ thống sẽ tự động cập nhật trạng thái đơn hàng sau khi nhận được thanh toán (1-3 phút).</p>
            </div>
        </div>
        @endif
        
        <p class="text-sm text-center text-gray-500">
            Nếu bạn có thắc mắc về đơn hàng, vui lòng liên hệ bộ phận hỗ trợ hoặc nhắn tin trực tiếp trên Zalo.
        </p>
    </div>
</x-app-layout>
