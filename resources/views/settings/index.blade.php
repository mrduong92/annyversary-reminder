<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cài đặt</h1>
            <p class="text-sm text-gray-500 mt-0.5">Tùy chỉnh thông báo và thiết lập hệ thống cho nhóm "{{ $activeGroup->name }}"</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-base font-medium text-gray-900">Thông báo tự động (Zalo ZNS)</h2>
                <p class="text-sm text-gray-500 mt-0.5">Hệ thống sẽ gửi thông báo đến bạn và các thành viên vào các ngày lễ âm lịch.</p>
            </div>
            
            <div class="p-5 space-y-4">
                @foreach (['remind_ram' => 'Nhắc ngày Rằm (15 âm lịch)', 'remind_mung_mot' => 'Nhắc Mùng 1 âm lịch'] as $field => $label)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $label }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Thông báo sẽ được gửi vào sáng ngày hôm đó.</p>
                    </div>
                    <form method="POST" action="{{ route('family-groups.toggle-reminder') }}">
                        @csrf
                        <input type="hidden" name="field" value="{{ $field }}">
                        <button type="submit"
                            class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors {{ $activeGroup->$field ? 'bg-primary-500' : 'bg-gray-200' }}">
                            <span class="inline-block h-5 w-5 mt-0.5 rounded-full bg-white shadow transition-transform {{ $activeGroup->$field ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                        </button>
                    </form>
                </div>
                @if (!$loop->last)
                    <hr class="border-gray-100">
                @endif
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
