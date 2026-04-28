<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $share->name }} — Nhắc Lịch Giỗ</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 h-full">

<div class="flex flex-col h-screen"
    x-data="shareChat('{{ $share->share_token }}')"
    x-init="init()">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shrink-0">
        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </div>
        <div>
            <p class="font-semibold text-gray-900 text-sm">{{ $share->name }}</p>
            <p class="text-xs text-gray-400">Trợ lý lịch giỗ — chế độ xem</p>
        </div>
    </div>

    {{-- Messages --}}
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4" x-ref="messages">

        <template x-if="messages.length === 0">
            <div class="flex justify-center py-10">
                <div class="text-center max-w-sm">
                    <p class="text-sm text-gray-500 mb-3">Hỏi về lịch giỗ gia đình</p>
                    <div class="grid gap-2">
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

        <template x-for="(msg, i) in messages" :key="i">
            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                <div :class="[
                    'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm',
                    msg.role === 'user' ? 'bg-primary-600 text-white rounded-br-sm' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm'
                ]">
                    <div x-html="formatMessage(msg.content)"></div>
                    <p class="text-xs mt-1 opacity-50" x-text="msg.time"></p>
                </div>
            </div>
        </template>

        <template x-if="streaming">
            <div class="flex justify-start">
                <div class="max-w-[85%] bg-white border border-gray-200 rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm text-gray-800">
                    <template x-if="streamingText">
                        <div x-html="formatMessage(streamingText)"></div>
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

    {{-- Note: read-only mode --}}
    <div class="bg-amber-50 border-t border-amber-100 px-4 py-2 text-xs text-amber-700 text-center shrink-0">
        Chế độ xem — bạn có thể hỏi về lịch giỗ nhưng không thể thay đổi dữ liệu
    </div>

    {{-- Input --}}
    <div class="shrink-0 bg-white border-t border-gray-200 px-4 py-3">
        <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
            <textarea x-model="input" @keydown.enter.exact.prevent="sendMessage()"
                x-ref="inputBox" :disabled="streaming" rows="1"
                placeholder="Hỏi về lịch giỗ..."
                class="flex-1 resize-none bg-gray-50 border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none disabled:opacity-50"
                @input="autoResize($event.target)"></textarea>
            <button type="submit" :disabled="streaming || !input.trim()"
                class="shrink-0 w-10 h-10 bg-primary-600 hover:bg-primary-700 disabled:opacity-40 rounded-xl flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
function shareChat(token) {
    return {
        token,
        messages: [],
        input: '',
        streaming: false,
        streamingText: '',
        conversationId: null,
        hints: [
            'Xem tất cả ngày giỗ trong gia đình',
            'Giỗ gần nhất là ngày mấy?',
            'Soạn văn khấn giỗ giúp tôi',
        ],

        async init() {
            const key = 'share_conv_' + token;
            this.conversationId = localStorage.getItem(key) || null;
            if (this.conversationId) await this.loadHistory();
        },

        async loadHistory() {
            try {
                const res = await fetch(`/s/${this.token}/history?conversation_id=${this.conversationId}`);
                const data = await res.json();
                if (data.messages?.length) {
                    this.messages = data.messages;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch {}
        },

        async sendMessage(text) {
            const message = text || this.input.trim();
            if (!message || this.streaming) return;

            this.input = '';
            this.messages.push({ role: 'user', content: message, time: this.now() });
            this.scrollToBottom();
            this.streaming = true;
            this.streamingText = '';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;

                if (!this.conversationId) {
                    const convRes = await fetch('/agent/conversation', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ conversation_id: null }),
                    });
                    const convData = await convRes.json();
                    this.conversationId = convData.conversation_id;
                    localStorage.setItem('share_conv_' + this.token, this.conversationId);
                }

                const response = await fetch(`/s/${this.token}/stream`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'text/event-stream' },
                    body: JSON.stringify({ message, conversation_id: this.conversationId }),
                });

                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    throw new Error(err.error || 'Có lỗi xảy ra.');
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    const chunk = decoder.decode(value, { stream: true });
                    for (const line of chunk.split('\n')) {
                        if (!line.startsWith('data: ')) continue;
                        const data = line.slice(6).trim();
                        if (data === '[DONE]') continue;
                        try {
                            const parsed = JSON.parse(data);
                            if (parsed?.type === 'text_delta' && parsed?.delta) {
                                this.streamingText += parsed.delta;
                            }
                        } catch {}
                    }
                    this.scrollToBottom();
                }

                if (this.streamingText) {
                    this.messages.push({ role: 'assistant', content: this.streamingText, time: this.now() });
                }
            } catch (error) {
                this.messages.push({ role: 'assistant', content: `⚠️ ${error.message}`, time: this.now() });
            } finally {
                this.streaming = false;
                this.streamingText = '';
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        formatMessage(text) {
            if (!text) return '';
            return text
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
                .replace(/\n/g,'<br>');
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
</body>
</html>
