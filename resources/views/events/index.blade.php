<x-app-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Ngày giỗ</h1>
            <p class="text-sm text-gray-500 mt-0.5">Quản lý lịch giỗ gia đình</p>
        </div>
        <a href="{{ route('events.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors focus:ring-4 focus:ring-primary-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm ngày giỗ
        </a>
    </div>

    @if ($events->isEmpty())
        {{-- Empty state --}}
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500 text-sm">Chưa có ngày giỗ nào.</p>
            <a href="{{ route('events.create') }}"
                class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-700">
                + Thêm ngày giỗ đầu tiên
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3">Tên người mất</th>
                        <th class="px-5 py-3 hidden sm:table-cell">Quan hệ</th>
                        <th class="px-5 py-3">Ngày âm lịch</th>
                        <th class="px-5 py-3 hidden md:table-cell">Ngày dương (năm nay)</th>
                        <th class="px-5 py-3">Còn lại</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($events as $event)
                    <tr class="hover:bg-gray-50 transition-colors {{ $event->is_active ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3.5">
                            <span class="font-medium text-gray-900">
                                {{ $event->name }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell text-gray-500">
                            {{ $event->relationship ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-gray-700">
                            <span class="font-mono">{{ $event->lunar_day }}/{{ $event->lunar_month }}</span>
                            <span @class([
                                'ml-1 text-xs px-1.5 py-0.5 rounded font-medium',
                                'bg-amber-50 text-amber-600' => $event->isLunar(),
                                'bg-blue-50 text-blue-600'   => $event->isSolar(),
                            ])>{{ $event->isLunar() ? 'âm' : 'dương' }}</span>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-gray-500">
                            @if ($event->solar_date_next)
                                {{ $event->solar_date_next->format('d/m/Y') }}
                                <span class="text-gray-400">({{ $event->solar_date_next->isoFormat('dddd') }})</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if ($event->days_until !== null)
                                @if ($event->days_until === 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Hôm nay</span>
                                @elseif ($event->days_until === 1)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">Ngày mai</span>
                                @elseif ($event->days_until <= 7)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">{{ $event->days_until }} ngày nữa</span>
                                @else
                                    <span class="text-gray-500 text-sm">{{ $event->days_until }} ngày</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('events.show', $event) }}"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-700 hover:underline">
                                    Quản lý người nhận
                                </a>
                                <a href="{{ route('events.edit', $event) }}"
                                    class="text-xs font-medium text-gray-600 hover:text-gray-900 hover:underline">
                                    Sửa
                                </a>
                                <form method="POST" action="{{ route('events.destroy', $event) }}"
                                    onsubmit="return confirm('Xóa ngày giỗ {{ $event->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Plan usage --}}
        @php
            $plan = Auth::user()->fresh()->subscription_plan ?? 'free';
            $limits = ['free' => 3, 'basic' => 10, 'unlimited' => PHP_INT_MAX];
            $limit = $limits[$plan];
            $count = $events->count();
        @endphp
        @if ($plan !== 'unlimited')
        <p class="mt-3 text-xs text-gray-400 text-right">
            Đã dùng {{ $count }}/{{ $limit }} ngày giỗ (gói {{ strtoupper($plan) }})
            @if ($count >= $limit)
                · <a href="#" class="text-primary-600 hover:underline">Nâng cấp</a>
            @endif
        </p>
        @endif
    @endif

</x-app-layout>
