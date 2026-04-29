<x-app-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Người nhận ZNS</h1>
            <p class="text-sm text-gray-500 mt-0.5">Danh bạ nhận thông báo nhắc lịch giỗ</p>
        </div>
        <a href="{{ route('recipients.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors focus:ring-4 focus:ring-primary-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm người nhận
        </a>
    </div>

    @if ($recipients->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-sm text-gray-400">Chưa có người nhận nào.</p>
            <a href="{{ route('recipients.create') }}"
                class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-700">
                + Thêm người nhận đầu tiên
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3">Họ và tên</th>
                        <th class="px-5 py-3">Số điện thoại</th>
                        <th class="px-5 py-3 hidden sm:table-cell">Nhắc trước</th>
                        <th class="px-5 py-3 hidden md:table-cell">Gắn với giỗ</th>
                        <th class="px-5 py-3 text-center">Trạng thái</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recipients as $r)
                    <tr class="hover:bg-gray-50 transition-colors {{ $r->is_active ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3.5 font-medium text-gray-900">{{ $r->name }}</td>
                        <td class="px-5 py-3.5 font-mono text-gray-600">{{ $r->phone }}</td>
                        <td class="px-5 py-3.5 hidden sm:table-cell text-gray-500">
                            {{ collect($r->notify_days_before)->map(fn($d) => $d . ' ngày')->join(', ') }}
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            @if ($r->memorial_events_count > 0)
                                <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-medium">
                                    {{ $r->memorial_events_count }} ngày giỗ
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Chưa gắn</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if ($r->is_active)
                                <span class="inline-block w-2 h-2 rounded-full bg-green-400"></span>
                            @else
                                <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('recipients.edit', $r) }}"
                                    class="text-xs font-medium text-primary-600 hover:underline">Sửa</a>
                                <form method="POST" action="{{ route('recipients.destroy', $r) }}"
                                    onsubmit="return confirm('Xóa {{ $r->name }}? Người này sẽ bị gỡ khỏi tất cả ngày giỗ đang gắn.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-500 hover:underline">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            $plan   = Auth::user()->fresh()->subscription_plan ?? 'free';
            $limit  = app(SubscriptionService::class)->recipientLimit(Auth::user());
            $count  = app(SubscriptionService::class)->recipientCount(Auth::user());
        @endphp
        @if ($plan !== 'premium')
        <p class="mt-3 text-xs text-gray-400 text-right">
            Đã dùng {{ $count }}/{{ $limit }} người nhận (gói {{ strtoupper($plan) }})
            @if ($count >= $limit)
                · <a href="#" class="text-primary-600 hover:underline">Nâng cấp</a>
            @endif
        </p>
        @endif
    @endif

</x-app-layout>
