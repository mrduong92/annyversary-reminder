<x-app-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Văn khấn</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Văn khấn đã lưu ·
            <a href="{{ route('agent.index') }}" class="text-primary-600 hover:underline">Chat với AI để soạn mới</a>
        </p>
    </div>

    {{-- Văn khấn của tôi --}}
    @if ($myPrayers->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Văn khấn của tôi</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($myPrayers as $prayer)
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3 hover:border-primary-200 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-medium text-gray-900 text-sm leading-snug">{{ $prayer->title }}</h2>
                    @if ($prayer->ai_generated)
                        <span class="shrink-0 text-xs bg-primary-50 text-primary-600 px-1.5 py-0.5 rounded font-medium">AI</span>
                    @endif
                </div>
                @if ($prayer->memorialEvent)
                    <p class="text-xs text-gray-400">
                        <svg class="w-3.5 h-3.5 inline mr-0.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $prayer->memorialEvent->name }}
                    </p>
                @endif
                <p class="text-xs text-gray-500 line-clamp-3 flex-1">{{ Str::limit($prayer->content, 120) }}</p>
                <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                    <span class="text-xs text-gray-400">{{ $prayer->created_at->diffForHumans() }}</span>
                    <div class="flex gap-2">
                        <a href="{{ route('prayers.show', $prayer) }}" class="text-xs font-medium text-primary-600 hover:underline">Xem</a>
                        <a href="{{ route('prayers.edit', $prayer) }}" class="text-xs font-medium text-gray-500 hover:underline">Sửa</a>
                        <form method="POST" action="{{ route('prayers.destroy', $prayer) }}" onsubmit="return confirm('Xóa văn khấn này?')">
                            @csrf @method('DELETE')
                            <button class="text-xs font-medium text-red-400 hover:underline">Xóa</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Văn khấn mẫu (hệ thống) --}}
    @if ($systemPrayers->isNotEmpty())
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Văn khấn mẫu các dịp lễ
        </h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($systemPrayers as $prayer)
            <a href="{{ route('prayers.show', $prayer) }}"
               class="bg-amber-50 border border-amber-100 rounded-xl p-5 flex flex-col gap-3 hover:border-amber-300 hover:bg-amber-50/80 transition-colors group">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-medium text-gray-800 text-sm leading-snug group-hover:text-amber-800">
                        {{ $prayer->title }}
                    </h3>
                    <span class="shrink-0 text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Mẫu</span>
                </div>
                <p class="text-xs text-gray-500 line-clamp-3 flex-1">{{ Str::limit($prayer->content, 120) }}</p>
                <span class="text-xs font-medium text-amber-700 group-hover:underline">Xem văn khấn →</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Trống --}}
    @if ($myPrayers->isEmpty() && $systemPrayers->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
        <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm text-gray-400 mb-4">Chưa có văn khấn nào.</p>
        <a href="{{ route('agent.index') }}" class="text-sm font-medium text-primary-600 hover:underline">
            Chat với AI để soạn văn khấn đầu tiên →
        </a>
    </div>
    @endif
</x-app-layout>
