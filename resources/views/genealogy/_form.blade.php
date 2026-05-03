{{--
    Form thành viên gia phả.
    create: $members, $couples
    edit:   $member, $members, $couples, $currentCoupleId, $currentSpouseId
--}}

@php
    if (old('is_alive') !== null) {
        $isDeceased = old('is_alive') == 0;
    } else {
        $isDeceased = $member ? !$member->is_alive : false;
    }
    $initialStatus = $isDeceased ? 'dead' : 'alive';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5"
    x-data="memberForm('{{ $initialStatus }}')"
>
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif
    
    <input type="radio" name="is_alive" value="1" class="hidden" :checked="status === 'alive'">
    <input type="radio" name="is_alive" value="0" class="hidden" :checked="status === 'dead'">

    {{-- Tên + Danh xưng --}}
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700">Danh xưng</label>
            <input type="text" name="pronoun" value="{{ old('pronoun', $member?->pronoun) }}"
                placeholder="Cụ, Ông, Bà..."
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
        </div>
        <div class="sm:col-span-2">
            <label class="block mb-1.5 text-sm font-medium text-gray-700">
                Họ và tên <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name', $member?->name) }}" required
                placeholder="VD: Nguyễn Văn An"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('name') border-red-400 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Quan hệ + Giới tính + Năm sinh --}}
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700">Quan hệ với bạn</label>
            <input type="text" name="relationship" value="{{ old('relationship', $member?->relationship) }}"
                list="rel-list" placeholder="Ông nội, Bà ngoại..."
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
            <datalist id="rel-list">
                <option value="Ông nội"><option value="Bà nội"><option value="Ông ngoại"><option value="Bà ngoại">
                <option value="Bố"><option value="Mẹ"><option value="Chú"><option value="Bác"><option value="Cô"><option value="Dì">
                <option value="Anh"><option value="Chị"><option value="Chồng"><option value="Vợ"><option value="Tổ tiên">
            </datalist>
        </div>
        <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700">Giới tính</label>
            <select name="gender"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                <option value="unknown" {{ old('gender', $member?->gender ?? 'unknown') === 'unknown' ? 'selected' : '' }}>Không rõ</option>
                <option value="male"    {{ old('gender', $member?->gender) === 'male'    ? 'selected' : '' }}>Nam ♂</option>
                <option value="female"  {{ old('gender', $member?->gender) === 'female'  ? 'selected' : '' }}>Nữ ♀</option>
            </select>
        </div>
        <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700">Năm sinh</label>
            <input type="number" name="birth_year" value="{{ old('birth_year', $member?->birth_year) }}"
                min="1000" max="2100" placeholder="VD: 1920"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
        </div>
    </div>

    {{-- Toggle trạng thái: Còn sống / Đã mất --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">Tình trạng</label>
        <div class="flex gap-2">
            <button type="button" @click="status = 'alive'"
                :class="status === 'alive'
                    ? 'bg-green-500 text-white border-green-500'
                    : 'bg-white text-gray-600 border-gray-300 hover:border-green-300'"
                class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors">
                ● Còn sống
            </button>
            <button type="button" @click="status = 'dead'"
                :class="status === 'dead'
                    ? 'bg-gray-700 text-white border-gray-700'
                    : 'bg-white text-gray-600 border-gray-300 hover:border-gray-500'"
                class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors">
                ✝ Đã mất
            </button>
        </div>
    </div>

    {{-- Fields chỉ hiện khi "Đã mất" --}}
    <div x-show="status === 'dead'" x-cloak class="space-y-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block mb-1.5 text-sm font-medium text-gray-700">Năm mất</label>
                <input type="number" name="death_year"
                    value="{{ old('death_year', $member?->death_year) }}"
                    min="1000" max="2100" placeholder="VD: 1995"
                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
            </div>
            <div>
                <label class="block mb-1.5 text-sm font-medium text-gray-700">Ngày giỗ</label>
                <div class="flex gap-1.5 items-center">
                    <input type="number" name="death_day"
                        value="{{ old('death_day', $member?->death_day) }}"
                        min="1" max="30" placeholder="Ngày"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 w-full p-2.5">
                    <span class="text-gray-400 text-xs shrink-0">/</span>
                    <input type="number" name="death_month"
                        value="{{ old('death_month', $member?->death_month) }}"
                        min="1" max="12" placeholder="Tháng"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 w-full p-2.5">
                </div>
            </div>
            <div>
                <label class="block mb-1.5 text-sm font-medium text-gray-700">Loại lịch</label>
                <select name="death_date_type"
                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                    <option value="lunar" {{ old('death_date_type', $member?->death_date_type ?? 'lunar') === 'lunar' ? 'selected' : '' }}>Âm lịch</option>
                    <option value="solar" {{ old('death_date_type', $member?->death_date_type) === 'solar' ? 'selected' : '' }}>Dương lịch</option>
                </select>
            </div>
        </div>

        @if ($member?->derivedEvent)
        <div class="flex items-center gap-2 text-sm text-green-700">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Ngày giỗ đang được theo dõi:
            <a href="{{ route('events.show', $member->derivedEvent) }}" class="font-medium hover:underline">
                {{ $member->derivedEvent->dateLabel() }}
            </a>
            — tự động cập nhật khi sửa ngày bên trên
        </div>
        @else
        <p class="text-xs text-gray-400">Điền ngày/tháng giỗ → hệ thống tự tạo lịch nhắc</p>
        @endif
    </div>

    {{-- Cha/Mẹ: chọn theo cặp --}}
    @if (($couples ?? []))
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">
            Cha/Mẹ
            <span class="font-normal text-xs text-gray-400">(chọn cặp vợ chồng hoặc đơn lẻ)</span>
        </label>
        <select name="couple_id"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
            <option value="">-- Không có / chưa xác định --</option>
            @foreach ($couples as $c)
            <option value="{{ $c['value'] }}"
                {{ old('couple_id', $currentCoupleId ?? '') === $c['value'] ? 'selected' : '' }}>
                {{ $c['label'] }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Vợ/Chồng --}}
    @if (($members ?? collect())->isNotEmpty())
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Vợ/Chồng</label>
        <select name="spouse_id"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
            <option value="">-- Không có --</option>
            @foreach ($members as $m)
            <option value="{{ $m->id }}"
                {{ old('spouse_id', $currentSpouseId ?? '') == $m->id ? 'selected' : '' }}>
                {{ $m->genderIcon() }} {{ $m->name }}{{ $m->lifespan() ? ' (' . $m->lifespan() . ')' : '' }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Ghi chú --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700">Ghi chú</label>
        <textarea name="notes" rows="2" placeholder="Nghề nghiệp, quê quán, giai thoại..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">{{ old('notes', $member?->notes) }}</textarea>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            {{ $method === 'PUT' ? 'Lưu thay đổi' : 'Thêm vào gia phả' }}
        </button>
        <a href="{{ route('genealogy.index') }}"
            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Hủy
        </a>
    </div>
</form>

@push('scripts')
<script>
function memberForm(initialStatus) {
    return {
        status: initialStatus,
    }
}
</script>
@endpush
