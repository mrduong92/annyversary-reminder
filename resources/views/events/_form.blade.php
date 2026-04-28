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

    {{-- Chọn thành viên gia đình --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">
            Thành viên <span class="text-red-500">*</span>
        </label>

        @php
            $membersToList = $allMembers ?? collect();
            $currentId     = old('family_member_id', $event?->family_member_id);
        @endphp

        @if (isset($availableMembers) && $availableMembers->isNotEmpty())
        {{-- Gợi ý card grid --}}
        <p class="text-xs text-gray-400 mb-2">Gợi ý (chưa có ngày giỗ):</p>
        <div class="grid sm:grid-cols-2 gap-2 mb-3">
            @foreach ($availableMembers->take(6) as $m)
            <label class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition-colors
                {{ $currentId == $m->id ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50' }}">
                <input type="radio" name="family_member_id" value="{{ $m->id }}"
                    {{ $currentId == $m->id ? 'checked' : '' }}
                    class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500 shrink-0">
                <div>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $m->pronoun ? $m->pronoun . ' ' : '' }}{{ $m->name }}
                        <span class="{{ $m->gender === 'male' ? 'text-blue-400' : ($m->gender === 'female' ? 'text-pink-400' : 'text-gray-300') }}">{{ $m->genderIcon() }}</span>
                    </p>
                    @if ($m->relationship || $m->lifespan())
                    <p class="text-xs text-gray-400">
                        {{ collect([$m->relationship, $m->lifespan()])->filter()->implode(' · ') }}
                    </p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
        @endif

        {{-- Dropdown tất cả members (edit hoặc "Chọn khác") --}}
        <div class="{{ isset($availableMembers) && $availableMembers->isNotEmpty() ? '' : '' }}">
            @if (isset($availableMembers) && $availableMembers->isNotEmpty())
                <details class="mt-1">
                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 select-none">Chọn thành viên khác...</summary>
                    <select name="family_member_id" class="mt-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                        <option value="">-- Chọn --</option>
                        @foreach ($membersToList as $m)
                        <option value="{{ $m->id }}" {{ $currentId == $m->id ? 'selected' : '' }}>
                            {{ $m->pronoun ? $m->pronoun . ' ' : '' }}{{ $m->name }}{{ $m->relationship ? ' (' . $m->relationship . ')' : '' }}{{ $m->lifespan() ? ' · ' . $m->lifespan() : '' }}
                        </option>
                        @endforeach
                    </select>
                </details>
            @else
                <select name="family_member_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('family_member_id') border-red-400 @enderror">
                    <option value="">-- Chọn thành viên --</option>
                    @foreach ($membersToList as $m)
                    <option value="{{ $m->id }}" {{ $currentId == $m->id ? 'selected' : '' }}>
                        {{ $m->pronoun ? $m->pronoun . ' ' : '' }}{{ $m->name }}{{ $m->relationship ? ' (' . $m->relationship . ')' : '' }}{{ $m->lifespan() ? ' · ' . $m->lifespan() : '' }}
                    </option>
                    @endforeach
                </select>
            @endif
        </div>

        @error('family_member_id') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror

        <p class="mt-2 text-xs text-gray-400">
            Chưa có?
            <a href="{{ route('genealogy.create') }}" class="text-primary-600 hover:underline">Thêm vào gia phả trước</a>
        </p>
    </div>

    {{-- Ngày giỗ --}}
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
            Ngày giỗ <span class="text-red-500">*</span>
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

    {{-- Ghi chú --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Ghi chú</label>
        <textarea name="notes" rows="2"
            placeholder="Địa điểm tổ chức, lễ vật đặc biệt, phong tục riêng..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">{{ old('notes', $event?->notes) }}</textarea>
    </div>

    {{-- Kích hoạt --}}
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            {{ old('is_active', $event?->is_active ?? true) ? 'checked' : '' }}
            class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
        <label for="is_active" class="text-sm text-gray-700">Kích hoạt nhắc lịch ZNS</label>
    </div>

    {{-- Người nhận --}}
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
            {{ $method === 'PUT' ? 'Lưu thay đổi' : 'Tạo ngày giỗ' }}
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
    return { dateType: initialType }
}
</script>
@endpush
