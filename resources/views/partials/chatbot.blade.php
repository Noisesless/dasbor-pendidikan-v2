{{-- Slide-Over & Floating Asisten Data Banjarnegara (Hybrid Online Gemini + Offline RAG) --}}
<div x-data="educationChatbot()"
     @open-chatbot.window="openWithPrompt($event.detail)"
     class=" relative z-50">

    {{-- Floating Trigger Button --}}
    <button type="button"
            @click="isOpen = !isOpen"
            aria-label="Buka Asisten Data Banjarnegara"
            class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2.5 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg ring-1 ring-blue-700/20 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition cursor-pointer">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <span>Tanya Asisten</span>
    </button>

    {{-- Slide-over Panel --}}
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-x-4 sm:translate-y-0"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-x-4 sm:translate-y-0"
         class="fixed bottom-20 right-4 sm:right-6 z-50 flex flex-col w-[calc(100vw-2rem)] sm:w-[440px] h-[600px] max-h-[calc(100dvh-7rem)] rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="px-4 py-3.5 bg-slate-900 text-white flex items-center justify-between gap-3 border-b border-slate-800">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-white truncate">Asisten Data Banjarnegara</h3>
                    <p class="text-[11px] text-slate-300 truncate">Dapodik, Kemenag, Walidata &amp; BPS</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button"
                        @click="resetChat()"
                        title="Bersihkan percakapan"
                        class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button type="button"
                        @click="isOpen = false"
                        title="Tutup panel"
                        class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Container --}}
        <div x-ref="chatScroll" class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/50">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                            ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm px-4 py-2.5 max-w-[85%] text-sm leading-relaxed'
                            : 'bg-white text-slate-800 border border-slate-200/90 rounded-2xl rounded-bl-sm px-4 py-3 max-w-[92%] text-sm leading-relaxed shadow-2xs'">

                        {{-- Formatted Content --}}
                        <div class="space-y-2 chatbot-content" x-html="renderMarkdown(msg.text)"></div>

                        {{-- Sources --}}
                        <template x-if="msg.sources && msg.sources.length">
                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex flex-wrap gap-1">
                                <template x-for="(src, sIdx) in msg.sources" :key="sIdx">
                                    <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500"
                                          x-text="src"></span>
                                </template>
                            </div>
                        </template>

                        {{-- Follow-up Suggestions --}}
                        <template x-if="msg.followUps && msg.followUps.length">
                            <div class="mt-2.5 space-y-1">
                                <template x-for="(q, fIdx) in msg.followUps" :key="fIdx">
                                    <button type="button"
                                            @click="sendQuick(q)"
                                            class="block w-full text-left rounded-lg border border-blue-100 bg-blue-50/60 hover:bg-blue-100/70 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition cursor-pointer"
                                            x-text="q"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Loading Indicator --}}
            <div x-show="loading" class="flex justify-start">
                <div class="bg-white border border-slate-200 rounded-2xl rounded-bl-sm px-4 py-3 text-xs font-semibold text-slate-500 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                    <span>Menyusun analisis dari basis data...</span>
                </div>
            </div>
        </div>

        {{-- Input Form --}}
        <form @submit.prevent="submitQuestion()" class="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
            <input type="text"
                   x-model="input"
                   :disabled="loading"
                   maxlength="600"
                   placeholder="Tulis pertanyaan satuan pendidikan..."
                   class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            <button type="submit"
                   :disabled="loading || !input.trim()"
                   class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer shrink-0">
                Kirim
            </button>
        </form>
    </div>
</div>

<script>
function educationChatbot() {
    return {
        isOpen: false,
        loading: false,
        input: '',
        messages: [],

        init() {
            this.resetChat();
        },

        resetChat() {
            this.messages = [
                {
                    role: 'model',
                    text: 'Halo, saya **Asisten Data Banjarnegara**. Saya membantu menganalisis data 2.054 sekolah, siswa, guru, sarana, serta menjelaskan mengapa indikator pada halaman **Rekomendasi Kepala Dinas** dipengaruhi oleh kekosongan data guru **MI dan MTs**.',
                    sources: ['Dapodik 20252', 'EMIS Kemenag', 'Walidata & BPS'],
                    followUps: [
                        'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
                        'Bagaimana perbandingan rasio guru per kecamatan jika siswa MI dan MTs dipisahkan?',
                        'Apa 3 keputusan kritis untuk Kepala Dinas pada tahun ini?'
                    ]
                }
            ];
        },

        openWithPrompt(detail) {
            this.isOpen = true;
            if (detail && detail.prompt) {
                this.sendQuick(detail.prompt);
            }
        },

        sendQuick(text) {
            if (this.loading) return;
            this.input = text;
            this.submitQuestion();
        },

        async submitQuestion() {
            const q = this.input.trim();
            if (!q || this.loading) return;

            this.input = '';
            const historyPayload = this.messages.slice(-4).map(m => ({
                role: m.role,
                text: (m.text || '').substring(0, 800)
            }));

            this.messages.push({
                role: 'user',
                text: q
            });
            this.scrollToBottom();
            this.loading = true;

            try {
                const res = await fetch('{{ route("chatbot.ask") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        question: q,
                        history: historyPayload
                    })
                });

                if (!res.ok) {
                    throw new Error('Request failed with status ' + res.status);
                }

                const data = await res.json();

                this.messages.push({
                    role: 'model',
                    text: data.answer || 'Data tidak ditemukan.',
                    sources: data.sources || [],
                    followUps: data.follow_ups || []
                });
            } catch (e) {
                this.messages.push({
                    role: 'model',
                    text: 'Mohon maaf, saat ini sistem sedang memproses antrean data. Silakan kirim ulang pertanyaan Anda dalam beberapa saat.',
                    sources: ['Sistem Data Banjarnegara'],
                    followUps: [
                        'Bagaimana perbandingan rasio guru per kecamatan jika siswa MI dan MTs dipisahkan?',
                        'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
                        'Apa 3 keputusan kritis untuk Kepala Dinas pada tahun ini?'
                    ]
                });
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                if (this.$refs.chatScroll) {
                    this.$refs.chatScroll.scrollTop = this.$refs.chatScroll.scrollHeight;
                }
            });
        },

        renderMarkdown(raw) {
            if (!raw) return '';
            const escaped = String(raw)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            const formatted = escaped
                .replace(/\*\*(.+?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
                .replace(/`([^`]+)`/g, '<code class="font-mono text-xs bg-slate-100 text-slate-800 px-1 py-0.5 rounded">$1</code>');

            return formatted
                .split(/\n\n+/)
                .map(block => {
                    const lines = block.split('\n');
                    if (lines.every(l => /^\s*[-*]\s+/.test(l))) {
                        const items = lines.map(l => '<li class="ml-4 list-disc">' + l.replace(/^\s*[-*]\s+/, '') + '</li>').join('');
                        return '<ul class="space-y-1">' + items + '</ul>';
                    }
                    return '<p>' + lines.join('<br>') + '</p>';
                })
                .join('');
        }
    };
}
</script>
