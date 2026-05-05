<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('prayers.index') }}" class="hover:text-gray-700">Văn khấn</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700">Sửa</span>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900">Sửa văn khấn</h1>
    </div>

    <div class="max-w-2xl bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('prayers.update', $prayer) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="title" class="block mb-1.5 text-sm font-medium text-gray-700">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title"
                    value="{{ old('title', $prayer->title) }}"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 @error('title') border-red-400 @enderror">
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="content" class="block mb-1.5 text-sm font-medium text-gray-700">Nội dung <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="16" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 font-mono @error('content') border-red-400 @enderror">{{ old('content', $prayer->content) }}</textarea>
                @error('content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                    Lưu thay đổi
                </button>
                <a href="{{ route('prayers.show', $prayer) }}"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Hủy
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
