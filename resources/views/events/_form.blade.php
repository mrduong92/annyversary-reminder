{{--
    Shared form partial — create và edit.
    create: $availableMembers, $allMembers, $recipients, $attachedRecipientIds
    edit:   $event, $allMembers, $recipients, $attachedRecipientIds
--}}

<form method="POST" action="{{ $action }}" class="space-y-6"
    x-data="eventForm('{{ old('date_type', $event?->date_type ?? 'lunar') }}')"
>
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    {{-- ── Loại sự kiện (Alpine để active state rõ ràng) ─────────── --}}
    @php $currentType = old('event_type', $event?->event_type ?? \App\Models\MemorialEvent::DEFAULT_TYPE); @endphp
    <div x-data="{ selected: '{{ $currentType }}' }">
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Loại sự kiện</label>
        <div class="flex flex-wrap gap-2">
            @foreach (\App\Models\MemorialEvent::TYPE_LABELS as $val => $label)
            <button type="button"
                    @click="selected = '{{ $val }}'"
                    :class="selected === '{{ $val }}'
                        ? 'border-primary-500 bg-primary-50 text-primary-700 font-semibold'
                        : 'border-gray-200 text-gray-600 bg-white hover:border-gray-300 hover:bg-gray-50'"
                    class="inline-flex items-center px-3 py-1.5 text-xs rounded-lg border transition-colors">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <input type="hidden" name="event_type" :value="selected">
    </div>

    {{-- ── Tiêu đề (bắt buộc khi không chọn thành viên) ──────────── --}}
    <div>
        <label for="title" class="block mb-1.5 text-sm font-medium text-gray-700">
            Tiêu đề <span class="text-red-500">*</span>
        </label>
        <input type="text" id="title" name="title"
            value="{{ old('title', $event?->title) }}"
            placeholder="Ví dụ: Ngày giỗ Ông nội, Giỗ Tổ Hùng Vương, Giỗ Bà..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('title') border-red-400 @enderror">
        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-gray-400">Tên hiển thị khi nhắc lịch và trong ZNS.</p>
    </div>

    {{-- ── Gắn thành viên gia phả (tuỳ chọn) ─────────────────────── --}}
    <div x-data="{ open: {{ old('family_member_id', $event?->family_member_id) ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open"
                class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span x-text="open ? 'Ẩn gắn kết gia phả' : 'Gắn với thành viên gia phả (tuỳ chọn)'"></span>
        </button>

        <div x-show="open" x-cloak class="mt-3 p-4 bg-gray-50 border border-gray-200 rounded-xl">
            <p class="text-xs text-gray-500 mb-3">
                Nếu chọn thành viên, hệ thống sẽ tự lấy tên từ gia phả thay vì tiêu đề trên.
            </p>

            @php
                $membersToList = $allMembers ?? collect();
                $currentId     = old('family_member_id', $event?->family_member_id);
            @endphp

            @if (isset($availableMembers) && $availableMembers->isNotEmpty())
            <p class="text-xs text-gray-400 mb-2">Gợi ý (chưa có ngày giỗ):</p>
            <div class="grid sm:grid-cols-2 gap-2 mb-3">
                @foreach ($availableMembers->take(6) as $m)
                <label class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition-colors bg-white
                    {{ $currentId == $m->id ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:border-primary-300' }}">
                    <input type="radio" name="family_member_id" value="{{ $m->id }}"
                        {{ $currentId == $m->id ? 'checked' : '' }}
                        class="w-4 h-4 text-primary-600 border-gray-300 shrink-0">
                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $m->pronoun ? $m->pronoun . ' ' : '' }}{{ $m->name }}
                        </p>
                        @if ($m->relationship || $m->lifespan())
                        <p class="text-xs text-gray-400">{{ collect([$m->relationship, $m->lifespan()])->filter()->implode(' · ') }}</p>
                        @endif
                    </div>
                </label>
                @endforeach
            </div>
            @endif

            <select name="family_member_id"
                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                <option value="">-- Không gắn --</option>
                @foreach ($membersToList as $m)
                <option value="{{ $m->id }}" {{ $currentId == $m->id ? 'selected' : '' }}>
                    {{ $m->pronoun ? $m->pronoun . ' ' : '' }}{{ $m->name }}{{ $m->relationship ? ' (' . $m->relationship . ')' : '' }}{{ $m->lifespan() ? ' · ' . $m->lifespan() : '' }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ── Ngày giỗ ─────────────────────────────────────────────────── --}}
    <div>
        <div class="flex items-center gap-3 mb-3">
            <label class="text-sm font-medium text-gray-700 shrink-0">Loại lịch</label>
            <select name="date_type" x-model="dateType"
                class="text-sm border border-gray-300 rounded-lg px-2.5 py-1.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                <option value="lunar">Âm lịch</option>
                <option value="solar">Dương lịch</option>
            </select>
        </div>

        <label class="block mb-1.5 text-sm font-medium text-gray-700">
            Ngày <span class="text-red-500">*</span>
            <span class="font-normal text-gray-400" x-text="dateType === 'lunar' ? '(âm lịch)' : '(dương lịch)'"></span>
        </label>
        <div class="flex items-center gap-2">
            <input type="number" name="lunar_day" value="{{ old('lunar_day', $event?->lunar_day) }}"
                min="1" :max="dateType === 'solar' ? 31 : 30" placeholder="Ngày" required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 w-24 p-2.5 @error('lunar_day') border-red-400 @enderror">
            <span class="text-gray-500 text-sm shrink-0">tháng</span>
            <input type="number" name="lunar_month" value="{{ old('lunar_month', $event?->lunar_month) }}"
                min="1" max="12" placeholder="Tháng" required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 w-24 p-2.5 @error('lunar_month') border-red-400 @enderror">
            <span class="text-gray-400 text-sm shrink-0" x-text="dateType === 'lunar' ? 'âm lịch' : 'dương lịch'"></span>
        </div>
        @error('lunar_day')   <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('lunar_month') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

        @if ($event?->solar_date_next)
        <p class="mt-1.5 text-xs text-gray-400">
            Năm nay: {{ $event->solar_date_next->format('d/m/Y') }} ({{ $event->solar_date_next->isoFormat('dddd') }})
        </p>
        @endif
    </div>

    {{-- ── Ghi chú ──────────────────────────────────────────────────── --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Ghi chú</label>
        <textarea name="notes" rows="2"
            placeholder="Địa điểm tổ chức, lễ vật đặc biệt, phong tục riêng..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">{{ old('notes', $event?->notes) }}</textarea>
    </div>

    {{-- ── Kích hoạt ZNS ───────────────────────────────────────────── --}}
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            {{ old('is_active', $event?->is_active ?? true) ? 'checked' : '' }}
            class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
        <label for="is_active" class="text-sm text-gray-700">Kích hoạt nhắc lịch ZNS</label>
    </div>

    {{-- ── Người nhận ──────────────────────────────────────────────── --}}
    @if (($recipients ?? collect())->isNotEmpty())
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">Người nhận thông báo ZNS</label>
        <div class="grid sm:grid-cols-2 gap-2 bg-white border border-gray-200 rounded-lg p-3 max-h-48 overflow-y-auto">
            @foreach ($recipients as $r)
            <label class="flex items-start gap-2 cursor-pointer">
                <input type="checkbox" name="recipient_ids[]" value="{{ $r->id }}"
                    {{ in_array($r->id, $attachedRecipientIds ?? []) ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <span class="text-sm text-gray-700">
                    {{ $r->name }}
                    <span class="block text-xs text-gray-400">{{ $r->phone }}</span>
                </span>
            </label>
            @endforeach
        </div>
    </div>
    @endif

    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            {{ $method === 'PUT' ? 'Lưu thay đổi' : 'Tạo sự kiện' }}
        </button>
        <a href="{{ route('events.index') }}"
            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Hủy
        </a>
    </div>
</form>

@push('scripts')
<script>
function eventForm(initialType) {
    return { dateType: initialType };
}
</script>
@endpush
