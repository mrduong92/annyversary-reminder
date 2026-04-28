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
                / {{ $limits[$plan]['events'] === PHP_INT_MAX ? '∞' : $limits[$plan]['events'] }} tối đa
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Người nhận</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $recipientCount }}</p>
            <p class="text-xs text-gray-400 mt-1">
                / {{ $limits[$plan]['recipients'] === PHP_INT_MAX ? '∞' : $limits[$plan]['recipients'] }} tối đa
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ZNS tháng này</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $znsUsed }}</p>
            <p class="text-xs text-gray-400 mt-1">
                / {{ $znsLimit === PHP_INT_MAX ? '∞' : $znsLimit }} tin
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Văn khấn</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $prayerCount }}</p>
            <p class="text-xs text-gray-400 mt-1">đã lưu</p>
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

    {{-- Upgrade banner (free plan) --}}
    @if ($plan === 'free')
    <div class="mt-6 bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex-1">
            <p class="font-semibold text-white text-sm">Nâng cấp để dùng không giới hạn</p>
            <p class="text-primary-200 text-xs mt-0.5">Basic 49k/năm — 10 ngày giỗ, 30 ZNS/tháng, AI văn khấn không giới hạn</p>
        </div>
        <a href="#" class="shrink-0 bg-white text-primary-700 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-primary-50 transition-colors">
            Nâng cấp ngay
        </a>
    </div>
    @endif

</x-app-layout>
