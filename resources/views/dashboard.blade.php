<x-app-layout>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Xin chào, {{ Auth::user()->name }} 👋</h1>
        <p class="text-sm text-gray-500 mt-1">Tổng quan lịch giỗ gia đình bạn</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ngày giỗ</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $eventCount }}</p>
            <p class="text-xs text-gray-400 mt-1">
                Không giới hạn (tạo tự động từ gia phả)
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Người nhận</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $recipientCount }}</p>
            <p class="text-xs text-gray-400 mt-1">
                / {{ $recipientLimit === PHP_INT_MAX ? '∞' : $recipientLimit }} tối đa
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ZNS năm này</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $znsUsed }}</p>
            <p class="text-xs text-gray-400 mt-1">
                / {{ $znsLimit === PHP_INT_MAX ? '∞' : $znsLimit }} tin
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Văn khấn</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $prayerCount }}</p>
            <p class="text-xs text-gray-400 mt-1">
                @if ($plan === 'basic')
                    Miễn phí · không giới hạn
                @else
                    Không giới hạn
                @endif
            </p>
        </div>
    </div>

    {{-- Calendar âm lịch tháng này --}}
    @php
        $tl = $calendar['today_lunar'];
        $lunarMonthLabel = $tl['day'] . '/' . $tl['month'] . ' năm ' . $tl['stem_year'] . ' ' . $tl['branch_year'];
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 mb-6" x-data="lunarCalendar()">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <h2 class="font-semibold text-gray-900 text-sm">
                    Lịch tháng {{ $calendar['month'] }} — Âm lịch năm {{ $tl['stem_year'] }} {{ $tl['branch_year'] }}
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    Hôm nay: {{ $lunarMonthLabel }}
                    · <span class="font-medium text-gray-600">Ngày {{ $tl['stem_day'] }} {{ $tl['branch_day'] }}</span>
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1 text-green-700"><span class="px-1 py-0.5 bg-green-100 rounded text-xs font-medium">Tốt</span></span>
                <span class="flex items-center gap-1 text-red-700"><span class="px-1 py-0.5 bg-red-100 rounded text-xs font-medium">Xấu</span></span>
                <span class="flex items-center gap-1 text-amber-700"><span class="px-1 py-0.5 bg-amber-100 rounded text-xs font-medium">LưuÝ</span></span>
            </div>
        </div>
        <div class="p-4 relative">
            {{-- Header: Thứ --}}
            <div class="grid grid-cols-7 mb-1">
                @foreach(['T2','T3','T4','T5','T6','T7','CN'] as $dow)
                <div class="text-center text-xs font-medium text-gray-400 py-1">{{ $dow }}</div>
                @endforeach
            </div>

            {{-- Days grid --}}
            <div class="grid grid-cols-7 gap-0.5">
                @for($i = 0; $i < ($calendar['start_dow']); $i++)
                <div></div>
                @endfor

                @foreach($calendar['days'] as $day)
                @php
                    $q = $day['quality'];
                    $cellBg = match($q) {
                        'good'    => 'bg-green-50 border border-green-200',
                        'bad'     => 'bg-red-50 border border-red-200',
                        'caution' => 'bg-amber-50 border border-amber-200',
                        default   => 'border border-transparent',
                    };
                    $badgeText  = match($q) { 'good' => 'Tốt', 'bad' => 'Xấu', 'caution' => 'LY', default => '' };
                    $badgeClass = match($q) {
                        'good'    => 'bg-green-100 text-green-700',
                        'bad'     => 'bg-red-100 text-red-700',
                        'caution' => 'bg-amber-100 text-amber-700',
                        default   => '',
                    };
                    $todayClass = $day['is_today'] ? 'ring-2 ring-primary-500' : '';
                    $tipData = json_encode([
                        'solar'     => $day['date']->format('d/m/Y'),
                        'dow'       => $day['date']->isoFormat('dddd'),
                        'lunarDay'  => $day['lunar_day'],
                        'lunarMonth'=> $day['lunar_month'],
                        'stemDay'   => $day['stem_day'],
                        'branchDay' => $day['branch_day'],
                        'quality'   => $q,
                        'qualityLabel' => match($q) {
                            'good'    => 'Ngày Sóc/Vọng — tốt để cúng bái, lễ lộc',
                            'bad'     => 'Ngày Tam Nương — nên tránh khởi sự quan trọng',
                            'caution' => 'Ngày Nguyệt Kỵ — hạn chế xuất hành, khởi đầu',
                            default   => '',
                        },
                        'events'    => $day['events']->map(fn($e) => $e->displayName())->values()->toArray(),
                    ]);
                @endphp
                <div class="relative rounded-lg p-1.5 text-center cursor-pointer {{ $cellBg }} {{ $todayClass }} hover:shadow-sm transition-all"
                     @mouseenter="show($event.currentTarget, {{ $tipData }})"
                     @mouseleave="hide()">
                    {{-- Solar --}}
                    <div class="text-sm font-bold {{ $day['is_today'] ? 'text-primary-700' : 'text-gray-800' }}">
                        {{ $day['date']->day }}
                    </div>
                    {{-- Lunar --}}
                    <div class="text-[10px] leading-none {{ $day['is_today'] ? 'text-primary-500 font-medium' : 'text-gray-400' }}">
                        {{ $day['lunar_day'] }}@if($day['lunar_day'] === 1)<span class="opacity-70">/{{ $day['lunar_month'] }}</span>@endif
                    </div>
                    {{-- Quality badge --}}
                    @if($badgeText)
                    <div class="text-[9px] font-bold {{ $badgeClass }} rounded px-0.5 mt-0.5 leading-tight">{{ $badgeText }}</div>
                    @endif
                    {{-- Event dot --}}
                    @if($day['events']->isNotEmpty())
                    <div class="w-1.5 h-1.5 rounded-full bg-primary-500 mx-auto mt-0.5"></div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Hover tooltip (fixed — không phụ thuộc parent positioning) --}}
            <div x-show="tip.visible" x-cloak
                 :style="`position:fixed; top:${tip.y}px; left:${tip.x}px; transform:translateX(-50%)`"
                 class="z-50 bg-white border border-gray-200 text-gray-800 text-xs rounded-xl shadow-xl p-3 w-52 pointer-events-none">
                <div class="font-semibold text-gray-900 mb-1" x-text="tip.dow + ', ' + tip.solar"></div>
                <div class="text-gray-500 mb-2">
                    Âm lịch: <span class="text-gray-800 font-medium" x-text="tip.lunarDay + '/' + tip.lunarMonth"></span>
                    · <span class="text-gray-700" x-text="tip.stemDay + ' ' + tip.branchDay"></span>
                </div>
                <template x-if="tip.qualityLabel">
                    <div :class="{
                        'text-green-700 bg-green-50': tip.quality === 'good',
                        'text-red-700 bg-red-50':     tip.quality === 'bad',
                        'text-amber-700 bg-amber-50': tip.quality === 'caution',
                    }" class="text-xs px-2 py-1 rounded-lg mb-2" x-text="tip.qualityLabel"></div>
                </template>
                <template x-if="tip.events.length > 0">
                    <div class="border-t border-gray-100 pt-2 mt-1">
                        <div class="text-gray-400 text-[10px] mb-1">🕯️ Ngày giỗ</div>
                        <template x-for="ev in tip.events">
                            <div class="text-primary-600 font-medium" x-text="ev"></div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Giỗ trong tháng --}}
            @php $eventsInMonth = collect($calendar['days'])->filter(fn($d) => $d['events']->isNotEmpty()); @endphp
            @if($eventsInMonth->isNotEmpty())
            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1">
                @foreach($eventsInMonth as $d)
                    @foreach($d['events'] as $event)
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></span>
                        <span class="font-semibold text-gray-700">{{ $d['date']->format('d/m') }}</span>
                        <span>{{ $event->displayName() }}</span>
                    </div>
                    @endforeach
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Upcoming events --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 text-sm">Sắp tới</h2>
                <a href="{{ route('events.index') }}" class="text-xs text-primary-600 hover:underline font-medium">Xem tất cả</a>
            </div>
            @if ($upcoming->isEmpty())
                <div class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400">Chưa có ngày giỗ nào.</p>
                    <a href="{{ route('events.create') }}"
                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-700">
                        + Thêm ngày giỗ đầu tiên
                    </a>
                </div>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach ($upcoming as $event)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <a href="{{ route('events.show', $event) }}"
                                class="text-sm font-medium text-gray-800 hover:text-primary-600 hover:underline">
                                {{ $event->displayName() }}
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $event->dateLabel() }}</p>
                        </div>
                        <div class="text-right shrink-0 ml-4">
                            @if ($event->days_until === 0)
                                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Hôm nay</span>
                            @elseif ($event->days_until === 1)
                                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full">Ngày mai</span>
                            @elseif ($event->days_until <= 7)
                                <span class="text-xs font-semibold text-yellow-700 bg-yellow-50 px-2 py-0.5 rounded-full">{{ $event->days_until }} ngày nữa</span>
                            @else
                                <span class="text-xs text-gray-400">{{ $event->days_until }} ngày</span>
                            @endif
                            @if ($event->solar_date_next)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $event->solar_date_next->format('d/m/Y') }}</p>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Quick actions --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 text-sm">Thao tác nhanh</h2>
            </div>
            <div class="p-5 grid grid-cols-2 gap-3">
                <a href="{{ route('events.create') }}"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-primary-200 hover:bg-primary-50 transition-colors group text-center">
                    <div class="w-10 h-10 rounded-lg bg-primary-100 group-hover:bg-primary-200 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Thêm ngày giỗ</span>
                </a>
                <a href="{{ route('recipients.create') }}"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-green-200 hover:bg-green-50 transition-colors group text-center">
                    <div class="w-10 h-10 rounded-lg bg-green-100 group-hover:bg-green-200 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Thêm người nhận</span>
                </a>
                <a href="#"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-blue-200 hover:bg-blue-50 transition-colors group text-center">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Chat AI</span>
                    <span class="text-xs text-gray-400 -mt-1">Sắp ra mắt</span>
                </a>
                <a href="#"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-orange-200 hover:bg-orange-50 transition-colors group text-center">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 group-hover:bg-orange-200 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Soạn văn khấn</span>
                    <span class="text-xs text-gray-400 -mt-1">Sắp ra mắt</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Upgrade banner (basic plan) --}}
    @if ($plan === 'basic')
    <div class="mt-6 bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex-1">
            <p class="font-semibold text-white text-sm">Nâng cấp Đại Gia Đình — 199.000đ/năm</p>
            <p class="text-primary-200 text-xs mt-0.5">ZNS 360 tin/năm · AI không giới hạn · Chia sẻ chatbot · Upload tài liệu gia đình</p>
        </div>
        <a href="{{ route('upgrade') }}" class="shrink-0 bg-white text-primary-700 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-primary-50 transition-colors">
            Nâng cấp ngay
        </a>
    </div>
    @endif

@push('scripts')
<script>
function lunarCalendar() {
    return {
        tip: { visible: false, x: 0, y: 0, solar: '', dow: '', lunarDay: '', lunarMonth: '',
               stemDay: '', branchDay: '', quality: '', qualityLabel: '', events: [] },

        show(el, data) {
            const rect  = el.getBoundingClientRect();
            const tipW  = 208; // w-52
            let x = rect.left + rect.width / 2;   // center of cell (viewport coords)
            let y = rect.bottom + window.scrollY + 6; // below cell + page scroll

            // Clamp: không bị cut ở cạnh phải
            x = Math.min(x, window.innerWidth - tipW / 2 - 8);
            x = Math.max(x, tipW / 2 + 8);

            this.tip = { visible: true, x, y, ...data };
        },

        hide() { this.tip.visible = false; },
    };
}
</script>
@endpush

</x-app-layout>
