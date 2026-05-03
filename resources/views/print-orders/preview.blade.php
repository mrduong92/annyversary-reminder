<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Xem trước bản in</h1>
                <p class="mt-1 text-sm text-gray-500">Mẫu: {{ $template->name }}</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button onclick="history.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    &larr; Quay lại
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cột trái: Form thông tin giao hàng -->
            <div class="lg:col-span-1">
                <form action="{{ route('print-orders.order') }}" method="POST" class="bg-white shadow sm:rounded-lg">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                    @foreach($members as $member)
                        <input type="hidden" name="member_ids[]" value="{{ $member->id }}">
                    @endforeach

                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Phương thức Đặt In</h3>
                        
                        @php
                            $printOptions = config('print_options');
                        @endphp
                        
                        <div class="space-y-4">
                            <!-- Digital Option -->
                            <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:bg-gray-50">
                                <input type="radio" name="order_type" value="digital" class="sr-only peer" checked onchange="updateOrderForm()">
                                <div class="flex flex-1">
                                    <div class="flex flex-col">
                                        <span class="block text-sm font-medium text-gray-900">{{ $printOptions['digital']['name'] }}</span>
                                        <span class="mt-1 flex items-center text-sm text-gray-500">{{ $printOptions['digital']['description'] }}</span>
                                        <span class="mt-2 text-sm font-semibold text-primary-600" id="price-digital" data-price="{{ $printOptions['digital']['price'] }}">{{ number_format($printOptions['digital']['price']) }}đ</span>
                                    </div>
                                </div>
                                <span class="pointer-events-none absolute -inset-px rounded-lg border-2 border-transparent peer-checked:border-primary-600" aria-hidden="true"></span>
                            </label>

                            <!-- Physical Options -->
                            @foreach($printOptions['physical_options'] as $option)
                            <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:bg-gray-50">
                                <input type="radio" name="order_type" value="{{ $option['id'] }}" class="sr-only peer" onchange="updateOrderForm()">
                                <div class="flex flex-1">
                                    <div class="flex flex-col">
                                        <span class="block text-sm font-medium text-gray-900">{{ $option['name'] }}</span>
                                        <span class="mt-1 flex items-center text-sm text-gray-500">{{ $option['description'] }}</span>
                                        <span class="mt-2 text-sm font-semibold text-primary-600" id="price-{{ $option['id'] }}" data-price="{{ $option['price'] }}">{{ number_format($option['price']) }}đ</span>
                                    </div>
                                </div>
                                <span class="pointer-events-none absolute -inset-px rounded-lg border-2 border-transparent peer-checked:border-primary-600" aria-hidden="true"></span>
                            </label>
                            @endforeach
                        </div>

                        <div id="shipping-form" class="mt-6 border-t border-gray-200 pt-6 hidden">
                            <h4 class="text-base font-medium text-gray-900 mb-4">Thông tin nhận hàng</h4>
                            <div class="space-y-4">
                                <div>
                                    <label for="shipping_name" class="block text-sm font-medium text-gray-700">Tên người nhận</label>
                                    <input type="text" name="shipping_name" id="shipping_name" value="{{ Auth::user()->name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="shipping_phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                                    <input type="text" name="shipping_phone" id="shipping_phone" value="{{ Auth::user()->phone }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="shipping_address" class="block text-sm font-medium text-gray-700">Địa chỉ chi tiết (nhập tự do)</label>
                                    <textarea name="shipping_address" id="shipping_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Ví dụ: Số 123 Đường ABC, Phường XYZ, Quận 1, TP. HCM"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between text-gray-900 font-medium text-base mt-2 pt-2 border-t border-gray-200">
                                    <dt>Tổng thanh toán</dt>
                                    <dd id="total-price-display">{{ number_format($printOptions['digital']['price']) }}đ</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Xác nhận đặt hàng
                            </button>
                        </div>
                    </div>
                    
                    <script>
                        function updateOrderForm() {
                            const selectedType = document.querySelector('input[name="order_type"]:checked').value;
                            const shippingForm = document.getElementById('shipping-form');
                            const shippingInputs = shippingForm.querySelectorAll('input, textarea');
                            
                            // Hide/Show shipping form
                            if (selectedType === 'digital') {
                                shippingForm.classList.add('hidden');
                                shippingInputs.forEach(input => input.removeAttribute('required'));
                            } else {
                                shippingForm.classList.remove('hidden');
                                shippingInputs.forEach(input => input.setAttribute('required', 'required'));
                            }
                            
                            // Update total price
                            let price = 0;
                            if (selectedType === 'digital') {
                                price = document.getElementById('price-digital').getAttribute('data-price');
                            } else {
                                price = document.getElementById('price-' + selectedType).getAttribute('data-price');
                            }
                            
                            document.getElementById('total-price-display').innerText = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
                        }
                    </script>
                </form>
            </div>

            <!-- Cột phải: Preview SVG -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow sm:rounded-lg p-4 overflow-auto" style="max-height: 800px;">
                    <div class="border border-gray-200" style="min-width: 100%;">
                        <object data="{{ $svg }}" type="image/svg+xml" width="100%" height="800">
                            Trình duyệt của bạn không hỗ trợ hiển thị SVG.
                        </object>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2 text-center">Bản in thực tế có độ phân giải cao và kích thước chuẩn xác hơn.</p>
            </div>
        </div>
    </div>
</x-app-layout>
