<x-app-layout>

<div class="flex flex-col h-[calc(100vh-4rem)] sm:h-[calc(100vh-2rem)] -m-4 sm:-m-6 lg:-m-8"
    x-data="chatAgent()"
    x-init="init()">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 sm:px-6 py-3 bg-white border-b border-gray-200 shrink-0">
        <div class="flex-1 min-w-0">
            <h1 class="font-semibold text-gray-900 text-sm">{{ $activeFamilyGroup->name }}</h1>
            <p class="text-xs text-gray-400">Trợ lý AI · Hỏi về lịch giỗ, soạn văn khấn</p>
        </div>

        {{-- Share button --}}
        <div x-data="sharePanel()" class="relative">
            <button @click="toggle()"
                class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 px-2.5 py-1.5 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                Chia sẻ
            </button>

            {{-- Share panel --}}
            <div x-show="open" x-cloak @click.outside="open = false"
                class="absolute right-0 top-10 w-80 bg-white border border-gray-200 rounded-xl shadow-lg p-4 z-50">
                <p class="text-sm font-semibold text-gray-800 mb-1">Chia sẻ chatbot gia đình</p>
                <p class="text-xs text-gray-400 mb-3">Người thân có thể chat với AI biết lịch giỗ nhà bạn — chỉ đọc, không thể sửa.</p>

                <template x-if="!shareUrl && !enabled">
                    <button @click="enableShare()" :disabled="loading"
                        class="w-full py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors disabled:opacity-60">
                        <span x-show="!loading">Tạo link chia sẻ</span>
                        <span x-show="loading">Đang tạo...</span>
                    </button>
                </template>

                <template x-if="shareUrl || enabled">
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <input type="text" :value="shareUrl" readonly
                                class="flex-1 text-xs bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-2 font-mono min-w-0">
                            <button @click="copyUrl()" x-text="copied ? '✓' : 'Copy'"
                                :class="copied ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                class="shrink-0 text-xs font-medium px-3 py-2 rounded-lg transition-colors">
                            </button>
                        </div>
                        <button @click="disableShare()"
                            class="w-full py-1.5 text-xs text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            Tắt link chia sẻ
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <a href="{{ route('documents.index') }}"
            class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors"
            title="Quản lý tài liệu gia đình">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </a>

        <button @click="clearConversation()"
            class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
            Mới
        </button>
    </div>

    {{-- Messages --}}
    <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 space-y-4 bg-gray-50"
        id="messages-container"
        x-ref="messages">

        {{-- Welcome message --}}
        <template x-if="messages.length === 0">
            <div class="flex justify-center py-8">
                <div class="max-w-sm text-center">
                    <div class="w-16 h-16 rounded-2xl bg-primary-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <h2 class="font-semibold text-gray-800 mb-2">Xin chào! Tôi có thể giúp gì?</h2>
                    <div class="grid grid-cols-1 gap-2 mt-4">
                        <template x-for="hint in hints">
                            <button @click="sendMessage(hint)"
                                class="text-left text-xs text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2 hover:border-primary-300 hover:bg-primary-50 transition-colors">
                                <span x-text="hint"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Message list --}}
        <template x-for="(msg, i) in messages" :key="i">
            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                <div :class="[
                    'max-w-[85%] sm:max-w-[70%] rounded-2xl px-4 py-2.5 text-sm',
                    msg.role === 'user'
                        ? 'bg-primary-600 text-white rounded-br-sm'
                        : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm'
                ]">
                    <div x-html="formatMessage(msg.content)" class="prose prose-sm max-w-none"
                        :class="msg.role === 'user' ? 'prose-invert' : ''"></div>

                    {{-- Badge "Đã lưu tự động" — hiện khi AI soạn văn khấn --}}
                    <template x-if="msg.isPrayer && msg.role === 'assistant'">
                        <div class="mt-2 pt-2 border-t border-gray-100 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2.5 py-1 rounded-full font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Đã lưu tự động
                            </span>
                            <a :href="msg.savedUrl" target="_blank"
                               class="text-xs text-primary-600 hover:underline font-medium">
                                Xem trong danh sách →
                            </a>
                        </div>
                    </template>

                    <p class="text-xs mt-1 opacity-50" x-text="msg.time"></p>
                </div>
            </div>
        </template>

        {{-- Streaming message --}}
        <template x-if="streaming">
            <div class="flex justify-start">
                <div class="max-w-[85%] sm:max-w-[70%] bg-white border border-gray-200 rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm text-gray-800">
                    <template x-if="streamingText">
                        <div x-html="formatMessage(streamingText)" class="prose prose-sm max-w-none"></div>
                    </template>
                    <template x-if="!streamingText">
                        <div class="flex items-center gap-1.5 py-1">
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Input --}}
    <div class="shrink-0 bg-white border-t border-gray-200 px-4 sm:px-6 py-3">
        <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
            <textarea
                x-model="input"
                @keydown="handleEnterKey($event)"
                rows="1"
                x-ref="inputBox"
                :disabled="streaming"
                placeholder="Hỏi về lịch giỗ, văn khấn..."
                class="flex-1 resize-none bg-gray-50 border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none disabled:opacity-50 transition-all"
                style="max-height: 120px; overflow-y: auto;"
                @input="autoResize($event.target)"
            ></textarea>
            <button type="submit"
                :disabled="streaming || !input.trim()"
                class="shrink-0 w-10 h-10 bg-primary-600 hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-1.5 text-center">
            Enter để gửi · Shift+Enter xuống dòng
        </p>
    </div>
</div>

@push('scripts')
<script>
function chatAgent() {
    return {
        messages: [],
        input: '',
        streaming: false,
        streamingText: '',
        conversationId: null,
        prayerDetected: false,  // flag khi PrayerTool được gọi trong turn hiện tại
        hints: [
            'Giỗ ông nội năm nay vào ngày mấy?',
            'Soạn văn khấn giỗ ông nội giúp tôi',
            'Tuần này ngày nào tốt để làm giỗ?',
            'Soạn văn khấn cúng Rằm tháng này',
        ],

        async init() {
            this.conversationId = localStorage.getItem('agent_conversation_id') || null;
            if (this.conversationId) {
                await this.loadHistory();
            }
        },

        async loadHistory() {
            try {
                const res = await fetch(
                    '{{ route('agent.history') }}?conversation_id=' + encodeURIComponent(this.conversationId),
                    { headers: { 'Accept': 'application/json' } }
                );
                const data = await res.json();
                if (data.messages?.length) {
                    this.messages = data.messages;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch { /* ignore — fresh start */ }
        },

        async sendMessage(text) {
            const message = text || this.input.trim();
            if (! message || this.streaming) return;

            this.input = '';
            this.$nextTick(() => {
                if (this.$refs.inputBox) {
                    this.$refs.inputBox.style.height = 'auto';
                }
            });

            this.messages.push({
                role: 'user',
                content: message,
                time: this.now(),
            });

            this.scrollToBottom();
            this.streaming = true;
            this.streamingText = '';
            this.prayerDetected = false;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;

                // Bước 1: lấy / tạo conversation_id
                if (! this.conversationId) {
                    const convRes = await fetch('{{ route('agent.conversation') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ conversation_id: null }),
                    });
                    const convData = await convRes.json();
                    this.conversationId = convData.conversation_id;
                    localStorage.setItem('agent_conversation_id', this.conversationId);
                }

                // Bước 2: stream
                const response = await fetch('{{ route('agent.stream') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'text/event-stream',
                    },
                    body: JSON.stringify({
                        message,
                        conversation_id: this.conversationId,
                    }),
                });

                if (! response.ok) {
                    const err = await response.json().catch(() => ({}));
                    throw new Error(err.error || 'Có lỗi xảy ra. Vui lòng thử lại.');
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });

                    // Parse SSE events — Laravel AI SDK format
                    const lines = chunk.split('\n');
                    for (const line of lines) {
                        if (! line.startsWith('data: ')) continue;
                        const data = line.slice(6).trim();
                        if (data === '[DONE]') continue;
                        try {
                            const parsed = JSON.parse(data);
                            if (parsed?.type === 'text_delta' && parsed?.delta) {
                                this.streamingText += parsed.delta;
                            }
                            // tool_call không được stream ra client bởi Laravel AI SDK,
                            // nên detect prayer bằng nội dung text (xem bên dưới sau khi stream xong)
                        } catch { /* ignore non-JSON lines */ }
                    }

                    this.scrollToBottom();
                }

                if (this.streamingText) {
                    // Detect marker [PRAYER_SAVED:{id}] được PrayerTool nhúng vào response
                    const markerMatch = this.streamingText.match(/\[PRAYER_SAVED:(\d+)\]/);
                    const prayerId    = markerMatch ? parseInt(markerMatch[1]) : null;
                    // Strip marker khỏi nội dung hiển thị
                    const cleanText   = this.streamingText.replace(/\[PRAYER_SAVED:\d+\]\n*/g, '').trimEnd();

                    this.messages.push({
                        role: 'assistant',
                        content: cleanText,
                        time: this.now(),
                        isPrayer: prayerId !== null,
                        prayerId,
                        saved: prayerId !== null,  // đã lưu tự động
                        savedUrl: prayerId ? `/prayers/${prayerId}` : null,
                    });
                    this.prayerDetected = false;
                }

            } catch (error) {
                this.messages.push({
                    role: 'assistant',
                    content: `⚠️ ${error.message}`,
                    time: this.now(),
                });
            } finally {
                this.streaming = false;
                this.streamingText = '';
                this.$nextTick(() => this.scrollToBottom());
                this.$refs.inputBox?.focus();
            }
        },

        clearConversation() {
            this.messages = [];
            this.conversationId = null;
            localStorage.removeItem('agent_conversation_id');
        },

        formatMessage(text) {
            if (! text) return '';
            return text
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/`(.+?)`/g, '<code class="bg-gray-100 text-gray-800 px-1 rounded text-xs">$1</code>')
                .replace(/\n/g, '<br>');
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        handleEnterKey(event) {
            // Bỏ qua khi đang compose tiếng Việt (Telex/VNI IME)
            if (event.isComposing || event.keyCode === 229) return;
            if (event.key !== 'Enter') return;

            if (event.shiftKey) {
                // Shift+Enter: xuống dòng bình thường (không block)
                return;
            }

            // Enter đơn: gửi tin nhắn
            event.preventDefault();
            this.sendMessage();
        },

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        },

        now() {
            return new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        },
    }
}

function sharePanel() {
    return {
        open: false,
        loading: false,
        enabled: {{ $activeFamilyGroup->activeShare() ? 'true' : 'false' }},
        shareUrl: '{{ $activeFamilyGroup->activeShare() ? route('share.public', $activeFamilyGroup->activeShare()->share_token) : '' }}',
        copied: false,

        toggle() { this.open = !this.open; },

        async enableShare() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('share.enable') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await res.json();
                this.shareUrl = data.url;
                this.enabled  = true;
            } finally { this.loading = false; }
        },

        async disableShare() {
            if (! confirm('Tắt link chia sẻ? Người đang dùng link sẽ không vào được nữa.')) return;
            await fetch('{{ route('share.disable') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            this.enabled  = false;
            this.shareUrl = '';
        },

        copyUrl() {
            navigator.clipboard.writeText(this.shareUrl);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
    }
}
</script>
@endpush

</x-app-layout>
