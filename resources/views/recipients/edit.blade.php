<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('recipients.index') }}" class="hover:text-gray-700">Người nhận</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700">Sửa</span>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900">Sửa người nhận</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $recipient->name }}</p>
    </div>

    <div class="max-w-xl space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            @include('recipients._form', [
                'recipient' => $recipient,
                'action'    => route('recipients.update', $recipient),
                'method'    => 'PUT',
            ])
        </div>

        {{-- Ngày giỗ đang gắn --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 text-sm">Đang nhận thông báo cho</h2>
            </div>
            @if ($allEvents->isEmpty() && $linkedEventIds->isEmpty())
                <p class="px-5 py-4 text-sm text-gray-400">Chưa có ngày giỗ nào.</p>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach ($allEvents as $event)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $event->name }}</p>
                            <p class="text-xs text-gray-400">{{ $event->dateLabel() }}</p>
                        </div>
                        @if ($linkedEventIds->contains($event->id))
                            <form method="POST" action="{{ route('events.recipients.detach', [$event, $recipient]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline font-medium">Gỡ</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('events.recipients.attach', [$event, $recipient]) }}">
                                @csrf
                                <button type="submit" class="text-xs text-primary-600 hover:underline font-medium">Thêm vào</button>
                            </form>
                        @endif
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
