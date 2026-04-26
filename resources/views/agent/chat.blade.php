<x-app-layout>

<div class="flex flex-col h-[calc(100vh-4rem)] sm:h-[calc(100vh-2rem)] -m-4 sm:-m-6 lg:-m-8"
    x-data="chatAgent()"
    x-init="init()">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 sm:px-6 py-4 bg-white border-b border-gray-200 shrink-0">
        <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 001.357 2.059l.231.096c.808.34 1.412 1.04 1.612 1.886.218.96-.007 1.965-.623 2.72L15 21m-5.25-17.896c.251.023.501.05.75.082M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0z"/>
            </svg>
        </div>
        <div>
            <h1 class="font-semibold text-gray-900 text-sm">Trợ lý AI Gia Đình</h1>
            <p class="text-xs text-gray-400">Hỏi về lịch giỗ, soạn văn khấn, tìm kiếm tài liệu</p>
        </div>
        <div class="ml-auto">
            <button @click="clearConversation()"
                class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1 rounded hover:bg-gray-100 transition-colors">
                Cuộc trò chuyện mới
            </button>
        </div>
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
                @keydown.enter.exact.prevent="sendMessage()"
                @keydown.enter.shift.exact="input += '\n'"
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
        hints: [
            'Giỗ ông nội năm nay vào ngày mấy?',
            'Còn bao nhiêu ngày đến ngày giỗ gần nhất?',
            'Soạn văn khấn giỗ ông nội giúp tôi',
            'Xem tất cả ngày giỗ trong gia đình',
        ],

        init() {
            this.conversationId = localStorage.getItem('agent_conversation_id') || null;
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

                    // Parse SSE events — Laravel AI SDK format:
                    // data: {"type":"text_delta","delta":"..."}\n\n
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
                        } catch { /* ignore non-JSON lines */ }
                    }

                    this.scrollToBottom();
                }

                if (this.streamingText) {
                    this.messages.push({
                        role: 'assistant',
                        content: this.streamingText,
                        time: this.now(),
                    });
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

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        },

        now() {
            return new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        },
    }
}
</script>
@endpush

</x-app-layout>
