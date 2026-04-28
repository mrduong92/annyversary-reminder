<x-app-layout>

    {{-- Breadcrumb + header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('events.index') }}" class="hover:text-gray-700">Ngày giỗ</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700">{{ trim($event->pronoun . ' ' . $event->name) }}</span>
        </div>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ trim($event->pronoun . ' ' . $event->name) }}</h1>
                @if ($event->relationship)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $event->relationship }}</p>
                @endif
            </div>
            <a href="{{ route('events.edit', $event) }}"
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Sửa
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Thông tin ngày giỗ --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                <h2 class="font-semibold text-gray-900 text-sm">Thông tin</h2>

                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Ngày giỗ</span>
                    <span class="font-medium text-gray-900">{{ $event->dateLabel() }}</span>
                </div>

                @if ($event->solar_date_next)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Năm nay</span>
                    <span class="font-medium text-gray-900">
                        {{ $event->solar_date_next->format('d/m/Y') }}
                        <span class="text-gray-400 font-normal">({{ $event->solar_date_next->isoFormat('ddd') }})</span>
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Còn lại</span>
                    @if ($event->days_until === 0)
                        <span class="text-red-600 font-semibold">Hôm nay</span>
                    @elseif ($event->days_until === 1)
                        <span class="text-orange-600 font-semibold">Ngày mai</span>
                    @else
                        <span class="text-gray-900 font-medium">{{ $event->days_until }} ngày</span>
                    @endif
                </div>
                @endif

                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Nhắc lịch</span>
                    @if ($event->is_active)
                        <span class="text-green-600 font-medium">Đang bật</span>
                    @else
                        <span class="text-gray-400">Đã tắt</span>
                    @endif
                </div>

                @if ($event->notes)
                <div class="pt-2 border-t border-gray-100">
                    <p class="text-xs text-gray-500">{{ $event->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Quản lý người nhận --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Đang nhận thông báo --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900 text-sm">
                        Người nhận thông báo
                        @if ($attached->isNotEmpty())
                            <span class="ml-1.5 text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full">{{ $attached->count() }}</span>
                        @endif
                    </h2>
                    <a href="{{ route('recipients.create') }}"
                        class="text-xs text-primary-600 hover:underline font-medium">+ Tạo mới</a>
                </div>

                @if ($attached->isEmpty())
                    <p class="px-5 py-6 text-sm text-gray-400 text-center">Chưa có ai nhận thông báo cho ngày giỗ này.</p>
                @else
                    <ul class="divide-y divide-gray-50">
                        @foreach ($attached as $r)
                        <li class="flex items-center justify-between px-5 py-3 {{ $r->is_active ? '' : 'opacity-50' }}">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-xs font-semibold shrink-0">
                                    {{ mb_substr($r->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $r->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $r->phone }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @php
                                    $days = $r->pivot->notify_days_before
                                        ? json_decode($r->pivot->notify_days_before, true)
                                        : $r->notify_days_before;
                                @endphp
                                <span class="text-xs text-gray-400 hidden sm:block">
                                    Nhắc: {{ collect($days)->map(fn($d) => $d.'ng')->join(', ') }}
                                </span>
                                <form method="POST" action="{{ route('events.recipients.detach', [$event, $r]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 hover:underline">Gỡ</button>
                                </form>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Thêm từ danh bạ --}}
            @if ($available->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900 text-sm">Thêm từ danh bạ</h2>
                </div>
                <ul class="divide-y divide-gray-50" x-data="{ open: false }">
                    {{-- Hiện tối đa 5, expand nếu có nhiều hơn --}}
                    @foreach ($available as $i => $r)
                    <li class="{{ $i >= 5 ? 'hidden' : '' }}"
                        x-show="{{ $i >= 5 ? 'open' : 'true' }}">
                        <form method="POST" action="{{ route('events.recipients.attach', [$event, $r]) }}"
                            class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                            @csrf
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs font-semibold shrink-0">
                                    {{ mb_substr($r->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">{{ $r->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $r->phone }}</p>
                                </div>
                            </div>
                            <button type="submit"
                                class="text-xs font-medium text-primary-600 hover:text-primary-700 border border-primary-200 hover:border-primary-400 px-2.5 py-1 rounded-lg transition-colors">
                                + Thêm
                            </button>
                        </form>
                    </li>
                    @endforeach
                    @if ($available->count() > 5)
                    <li class="px-5 py-3">
                        <button @click="open = !open" class="text-xs text-gray-400 hover:text-gray-600">
                            <span x-show="!open">Xem thêm {{ $available->count() - 5 }} người...</span>
                            <span x-show="open">Thu gọn</span>
                        </button>
                    </li>
                    @endif
                </ul>
            </div>
            @endif

        </div>
    </div>

</x-app-layout>
