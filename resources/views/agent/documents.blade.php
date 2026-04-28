<x-app-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('agent.index') }}" class="hover:text-gray-700">Chat AI</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-700">Tài liệu gia đình</span>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Tài liệu gia đình</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Upload để AI trả lời câu hỏi từ tài liệu, nhật ký, gia phả của {{ $activeFamilyGroup->name }}
            </p>
        </div>
    </div>

    {{-- Upload form --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="font-semibold text-gray-900 text-sm mb-4">Upload tài liệu mới</h2>

        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
            x-data="{ filename: '', dragging: false }"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="dragging = false; filename = $event.dataTransfer.files[0]?.name; $refs.fileInput.files = $event.dataTransfer.files">
            @csrf

            <div @click="$refs.fileInput.click()"
                :class="dragging ? 'border-primary-400 bg-primary-50' : 'border-gray-300 hover:border-primary-300 hover:bg-gray-50'"
                class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-600 mb-1">
                    <span x-text="filename || 'Kéo thả file vào đây hoặc click để chọn'"></span>
                </p>
                <p class="text-xs text-gray-400">PDF, Word (.docx), TXT · Tối đa 10MB</p>
                <input type="file" name="document" x-ref="fileInput" class="hidden" accept=".pdf,.doc,.docx,.txt"
                    @change="filename = $event.target.files[0]?.name">
            </div>

            @error('document')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex justify-end">
                <button type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
                    x-bind:disabled="!filename">
                    Upload & Xử lý
                </button>
            </div>
        </form>
    </div>

    {{-- Document list --}}
    @if ($documents->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-12 text-center">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-gray-400">Chưa có tài liệu nào. Upload để AI học từ gia phả, nhật ký, tài liệu dòng họ.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3">Tên file</th>
                        <th class="px-5 py-3 hidden sm:table-cell">Số chunks</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3 hidden sm:table-cell">Upload</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($documents as $doc)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                @php
                                    $ext = strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION));
                                    $icon = match($ext) {
                                        'pdf'  => ['bg-red-100 text-red-600', 'PDF'],
                                        'docx', 'doc' => ['bg-blue-100 text-blue-600', 'DOC'],
                                        default => ['bg-gray-100 text-gray-600', 'TXT'],
                                    };
                                @endphp
                                <span class="text-xs font-bold px-1.5 py-0.5 rounded {{ $icon[0] }}">{{ $icon[1] }}</span>
                                <span class="font-medium text-gray-800 truncate max-w-xs">{{ $doc->filename }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell text-gray-500">
                            {{ $doc->chunks()->count() }} chunks
                        </td>
                        <td class="px-5 py-3.5">
                            @if ($doc->status === 'ready')
                                <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Sẵn sàng
                                </span>
                            @elseif ($doc->status === 'processing')
                                <span class="inline-flex items-center gap-1 text-xs text-yellow-700 bg-yellow-50 px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span> Đang xử lý
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-red-700 bg-red-50 px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Lỗi
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell text-gray-400 text-xs">
                            {{ $doc->created_at->diffForHumans() }}
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($doc->status === 'failed')
                                <form method="POST" action="{{ route('documents.reprocess', $doc) }}">
                                    @csrf
                                    <button class="text-xs font-medium text-primary-600 hover:underline">Xử lý lại</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                    onsubmit="return confirm('Xóa tài liệu này? AI sẽ không còn truy cập được.')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-medium text-red-400 hover:underline">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Info box --}}
    <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700">
        <strong>Cách hoạt động:</strong> File được tách thành các đoạn nhỏ và chuyển thành vector embedding (Gemini text-embedding-004), lưu vào PostgreSQL với pgvector.
        Khi bạn hỏi AI về tài liệu, hệ thống tìm các đoạn liên quan nhất bằng cosine similarity rồi đưa vào context của AI.
    </div>

</x-app-layout>
