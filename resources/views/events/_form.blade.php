{{--
    Shared form partial — dùng cho create và edit.
    $event: MemorialEvent|null
    $action: route string
    $method: 'POST' | 'PUT'
--}}

<form method="POST" action="{{ $action }}" class="space-y-5"
    x-data="eventForm('{{ old('date_type', $event?->date_type ?? 'lunar') }}')"
>
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-5">

        {{-- Tên --}}
        <div class="sm:col-span-2">
            <label for="name" class="block mb-1.5 text-sm font-medium text-gray-700">
                Tên <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name"
                value="{{ old('name', $event?->name) }}"
                placeholder="VD: Ông nội Nguyễn Văn A"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('name') border-red-400 bg-red-50 @enderror">
            @error('name') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Quan hệ — combobox: chọn hoặc gõ tự do --}}
        <div>
            <label for="relationship" class="block mb-1.5 text-sm font-medium text-gray-700">
                Quan hệ với bạn
                <span class="font-normal text-gray-400 text-xs">(chọn hoặc tự nhập)</span>
            </label>
            <input type="text" id="relationship" name="relationship"
                value="{{ old('relationship', $event?->relationship) }}"
                list="relationship-options"
                placeholder="VD: Ông nội, Mẹ vợ..."
                autocomplete="off"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
            <datalist id="relationship-options">
                <option value="Ông nội">
                <option value="Bà nội">
                <option value="Ông ngoại">
                <option value="Bà ngoại">
                <option value="Ông cố nội">
                <option value="Bà cố nội">
                <option value="Bố">
                <option value="Mẹ">
                <option value="Bố vợ">
                <option value="Mẹ vợ">
                <option value="Bố chồng">
                <option value="Mẹ chồng">
                <option value="Bác (anh trai bố)">
                <option value="Bác (vợ anh trai bố)">
                <option value="Chú (em trai bố)">
                <option value="Thím (vợ chú)">
                <option value="Cô (chị/em gái bố)">
                <option value="Dượng (chồng cô)">
                <option value="Cậu (anh/em trai mẹ)">
                <option value="Mợ (vợ cậu)">
                <option value="Dì (chị/em gái mẹ)">
                <option value="Chú dượng (chồng dì)">
                <option value="Anh trai">
                <option value="Chị gái">
                <option value="Em trai">
                <option value="Em gái">
                <option value="Chồng">
                <option value="Vợ">
                <option value="Tổ tiên">
            </datalist>
        </div>

        {{-- Loại lịch --}}
        <div>
            <label for="date_type" class="block mb-1.5 text-sm font-medium text-gray-700">Loại lịch</label>
            <select id="date_type" name="date_type"
                x-model="dateType"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                <option value="lunar">Âm lịch</option>
                <option value="solar">Dương lịch</option>
            </select>
        </div>

        {{-- Ngày / tháng --}}
        <div class="sm:col-span-2">
            <label class="block mb-1.5 text-sm font-medium text-gray-700">
                Ngày giỗ <span class="text-red-500">*</span>
                <span class="font-normal text-gray-400" x-text="dateType === 'lunar' ? '(âm lịch)' : '(dương lịch)'"></span>
            </label>
            <div class="flex items-center gap-2">
                <input type="number" id="lunar_day" name="lunar_day"
                    value="{{ old('lunar_day', $event?->lunar_day) }}"
                    min="1" :max="dateType === 'solar' ? 31 : 30"
                    placeholder="Ngày"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-24 p-2.5 @error('lunar_day') border-red-400 @enderror">
                <span class="text-gray-500 text-sm shrink-0">tháng</span>
                <input type="number" id="lunar_month" name="lunar_month"
                    value="{{ old('lunar_month', $event?->lunar_month) }}"
                    min="1" max="12"
                    placeholder="Tháng"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-24 p-2.5 @error('lunar_month') border-red-400 @enderror">
                <span class="text-gray-400 text-sm shrink-0" x-text="dateType === 'lunar' ? 'âm lịch' : 'dương lịch'"></span>
            </div>
            @error('lunar_day') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('lunar_month') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror

            @if ($event?->solar_date_next)
            <p class="mt-1.5 text-xs text-gray-400">
                Năm nay: {{ $event->solar_date_next->format('d/m/Y') }}
                ({{ $event->solar_date_next->isoFormat('dddd') }})
            </p>
            @endif
        </div>
    </div>

    {{-- Ghi chú --}}
    <div>
        <label for="notes" class="block mb-1.5 text-sm font-medium text-gray-700">Ghi chú</label>
        <textarea id="notes" name="notes" rows="3"
            placeholder="Địa điểm tổ chức, phong tục riêng của gia đình..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">{{ old('notes', $event?->notes) }}</textarea>
    </div>

    {{-- Chọn người nhận --}}
    @if ($recipients->isNotEmpty())
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Người nhận thông báo</label>
        <div class="grid sm:grid-cols-2 gap-3 bg-white p-4 border border-gray-200 rounded-lg max-h-60 overflow-y-auto">
            @foreach ($recipients as $recipient)
            <div class="flex items-start gap-2">
                <input type="checkbox" id="recipient_{{ $recipient->id }}" name="recipient_ids[]" value="{{ $recipient->id }}"
                    {{ in_array($recipient->id, old('recipient_ids', old('_token') ? [] : $attachedRecipientIds)) ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <label for="recipient_{{ $recipient->id }}" class="text-sm text-gray-700 cursor-pointer">
                    {{ $recipient->name }}
                    <span class="text-gray-400 block text-xs">{{ $recipient->phone }}</span>
                </label>
            </div>
            @endforeach
        </div>
        <p class="mt-1.5 text-xs text-gray-500">Chọn những người sẽ nhận được thông báo Zalo khi sắp đến ngày giỗ.</p>
    </div>
    @else
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Người nhận thông báo</label>
        <div class="bg-gray-50 p-4 border border-gray-200 border-dashed rounded-lg text-sm text-gray-500 text-center">
            Bạn chưa có người nhận nào. <a href="{{ route('recipients.create') }}" class="text-primary-600 hover:underline" target="_blank">Thêm người nhận ngay</a>
        </div>
    </div>
    @endif

    {{-- Kích hoạt nhắc lịch (cả create lẫn edit) --}}
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            {{ old('is_active', $event?->is_active ?? true) ? 'checked' : '' }}
            class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
        <label for="is_active" class="text-sm text-gray-700">Kích hoạt nhắc lịch</label>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
            {{ $method === 'PUT' ? 'Lưu thay đổi' : 'Thêm ngày giỗ' }}
        </button>
        <a href="{{ route('events.index') }}"
            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            Hủy
        </a>
    </div>
</form>

@push('scripts')
<script>
function eventForm(initialType) {
    return { dateType: initialType }
}
</script>
@endpush
