<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('prayers.index') }}" class="hover:text-gray-700">Văn khấn</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 truncate max-w-xs">{{ $prayer->title }}</span>
        </div>
        <div class="flex items-start justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $prayer->title }}</h1>
            @if (!$prayer->is_system)
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('prayers.edit', $prayer) }}"
                    class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Sửa</a>
                <form method="POST" action="{{ route('prayers.destroy', $prayer) }}"
                    onsubmit="return confirm('Xóa văn khấn này?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50">Xóa</button>
                </form>
            </div>
            @endif
        </div>
        <div class="flex items-center gap-3 mt-1">
            @if ($prayer->is_system)
                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Văn khấn mẫu</span>
            @elseif ($prayer->ai_generated)
                <span class="text-xs bg-primary-50 text-primary-600 px-2 py-0.5 rounded-full font-medium">AI soạn</span>
            @endif
            @if ($prayer->memorialEvent)
                <span class="text-xs text-gray-400">Giỗ: {{ $prayer->memorialEvent->name }}</span>
            @endif
            @if (!$prayer->is_system)
                <span class="text-xs text-gray-400">{{ $prayer->created_at->format('d/m/Y H:i') }}</span>
            @endif
        </div>
    </div>

    <div class="max-w-2xl bg-white rounded-xl border border-gray-200 p-6 sm:p-8">
        <div class="prose prose-sm max-w-none text-gray-800 leading-relaxed whitespace-pre-wrap font-serif text-base">{{ $prayer->content }}</div>
    </div>
</x-app-layout>
