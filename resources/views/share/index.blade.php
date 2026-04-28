<x-app-layout>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Chia sẻ chatbot</h1>
            <p class="text-sm text-gray-500 mt-0.5">Tạo link cho gia đình chat với AI biết lịch giỗ nhà bạn</p>
        </div>
    </div>

    {{-- Tạo link mới --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <h2 class="font-semibold text-gray-900 text-sm mb-3">Tạo link chia sẻ mới</h2>
        <form method="POST" action="{{ route('share.store') }}" class="flex gap-3">
            @csrf
            <input type="text" name="name" placeholder="VD: Chatbot Gia Đình Nguyễn" required
                class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-2.5">
            <button type="submit"
                class="px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors shrink-0">
                + Tạo link
            </button>
        </form>
    </div>

    {{-- Danh sách links --}}
    @if ($shares->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-12 text-center">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            <p class="text-sm text-gray-400">Chưa có link chia sẻ nào.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($shares as $share)
            <div class="bg-white rounded-xl border border-gray-200 p-5" x-data="{ copied: false }">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-medium text-gray-900 text-sm">{{ $share->name }}</h3>
                            @if ($share->is_active)
                                <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-medium">Đang bật</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">Đã tắt</span>
                            @endif
                        </div>

                        {{-- URL --}}
                        @php $url = route('share.public', $share->share_token); @endphp
                        <div class="flex items-center gap-2 mt-2">
                            <input type="text" value="{{ $url }}" readonly
                                class="flex-1 bg-gray-50 border border-gray-200 text-gray-600 text-xs rounded-lg px-3 py-1.5 font-mono min-w-0">
                            <button @click="navigator.clipboard.writeText('{{ $url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors"
                                :class="copied ? 'border-green-300 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'">
                                <span x-show="!copied">Copy</span>
                                <span x-show="copied">✓ Copied!</span>
                            </button>
                            <a href="{{ $url }}" target="_blank"
                                class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">
                                Xem thử
                            </a>
                        </div>

                        <p class="text-xs text-gray-400 mt-2">
                            Đã truy cập {{ $share->access_count }} lần
                            · Tạo {{ $share->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <div class="flex gap-2 shrink-0">
                        @if ($share->is_active)
                        <form method="POST" action="{{ route('share.revoke', $share) }}">
                            @csrf @method('PATCH')
                            <button class="text-xs font-medium text-orange-500 hover:underline">Tắt</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('share.destroy', $share) }}"
                            onsubmit="return confirm('Xóa link chia sẻ này?')">
                            @csrf @method('DELETE')
                            <button class="text-xs font-medium text-red-400 hover:underline">Xóa</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
