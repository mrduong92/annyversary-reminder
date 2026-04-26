{{-- $recipient: Recipient|null, $action, $method --}}
<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <label for="name" class="block mb-1.5 text-sm font-medium text-gray-700">
                Họ và tên <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name"
                value="{{ old('name', $recipient?->name) }}"
                placeholder="VD: Nguyễn Thị B"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('name') border-red-400 bg-red-50 @enderror">
            @error('name') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="block mb-1.5 text-sm font-medium text-gray-700">
                Số điện thoại Zalo <span class="text-red-500">*</span>
            </label>
            <input type="tel" id="phone" name="phone"
                value="{{ old('phone', $recipient?->phone) }}"
                placeholder="0912345678"
                inputmode="numeric"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('phone') border-red-400 bg-red-50 @enderror">
            @error('phone') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-400">Số này phải có tài khoản Zalo để nhận thông báo ZNS.</p>
        </div>
    </div>

    {{-- Thời điểm nhắc mặc định --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Nhắc trước mặc định <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-400 mb-2">Có thể ghi đè khi gắn vào từng ngày giỗ cụ thể.</p>
        @php $selected = old('notify_days_before', $recipient?->notify_days_before ?? [1]); @endphp
        <div class="flex flex-wrap gap-3">
            @foreach ([1 => '1 ngày', 3 => '3 ngày', 7 => '1 tuần', 14 => '2 tuần'] as $val => $label)
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="notify_days_before[]" value="{{ $val }}"
                    {{ in_array($val, (array) $selected) ? 'checked' : '' }}
                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <span class="text-sm text-gray-700">{{ $label }}</span>
            </label>
            @endforeach
        </div>
        @error('notify_days_before') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Trạng thái --}}
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            {{ old('is_active', $recipient?->is_active ?? true) ? 'checked' : '' }}
            class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
        <label for="is_active" class="text-sm text-gray-700">Kích hoạt nhận thông báo</label>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
            {{ $method === 'PUT' ? 'Lưu thay đổi' : 'Thêm người nhận' }}
        </button>
        <a href="{{ route('recipients.index') }}"
            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            Hủy
        </a>
    </div>
</form>
