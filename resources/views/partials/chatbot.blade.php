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
                        @click="showDebug = !showDebug"
                        :class="showDebug ? 'text-amber-400 bg-slate-800 ring-1 ring-amber-400/50' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                        title="Mode Debugging Analitik (Production)"
                        class="p-1.5 rounded-lg transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </button>
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

        {{-- Production Debug Console Bar (Kompak & Ramping) --}}
        <div x-show="showDebug"
             x-cloak
             class="px-3 py-1.5 bg-slate-950 text-slate-200 border-b border-slate-800 text-[10px] font-mono flex items-center justify-between gap-2 shrink-0">
            <div class="flex items-center gap-2 truncate">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                <template x-if="lastDebug">
                    <span class="truncate text-slate-300">
                        <span class="text-amber-300 font-bold" x-text="lastDebug.engine || 'online'"></span>
                        <span class="text-slate-500">|</span>
                        <span class="text-blue-300" x-text="lastDebug.model || '-'"></span>
                        <span class="text-slate-500">|</span>
                        <span class="text-emerald-300" x-text="(lastDebug.duration_ms || 0) + 'ms'"></span>
                        <span class="text-slate-500">|</span>
                        <span class="text-sky-300" x-text="(lastDebug.answer_length || 0) + 'c'"></span>
                    </span>
                </template>
                <template x-if="!lastDebug">
                    <span class="text-amber-400 font-bold">DEBUG AKTIF (PROD)</span>
                </template>
            </div>
            <button type="button"
                    @click="testPingServer()"
                    :disabled="pingLoading"
                    class="px-2 py-0.5 rounded bg-blue-600/80 hover:bg-blue-600 text-white text-[10px] font-sans font-bold transition cursor-pointer shrink-0">
                <span x-text="pingLoading ? '...' : 'Ping DB'"></span>
            </button>
        </div>

        {{-- Messages Container --}}
        <div x-ref="chatScroll" class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/50">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                            ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm px-4 py-2.5 max-w-[85%] text-sm leading-relaxed shadow-sm'
                            : 'bg-white text-slate-800 border border-slate-200/90 rounded-2xl rounded-bl-sm px-4 py-3 max-w-[92%] text-sm leading-relaxed shadow-2xs'">

                        {{-- Sender & Timestamp Header --}}
                        <div :class="msg.role === 'user' ? 'text-blue-100 justify-end' : 'text-slate-500 justify-between'"
                             class="text-[11px] font-semibold mb-1.5 flex items-center gap-1.5">
                            <span class="flex items-center gap-1" :class="msg.role === 'user' ? 'text-white' : 'text-blue-700 font-bold'">
                                <template x-if="msg.role !== 'user'">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </template>
                                <span x-text="msg.role === 'user' ? 'Anda' : 'Asisten Data'"></span>
                            </span>
                            <span x-show="msg.time" :class="msg.role === 'user' ? 'text-blue-200' : 'text-slate-400'" class="text-[10px] font-normal" x-text="msg.time"></span>
                        </div>

                        {{-- Formatted Content --}}
                        <template x-if="showDebug && msg.debug">
                            <div class="mb-2 px-2 py-1 rounded bg-slate-900 text-amber-300 font-mono text-[10px] border border-slate-800 flex items-center justify-between">
                                <span x-text="'⚡ ' + (msg.debug.engine || '-') + ' (' + (msg.debug.model || '-') + ')'"></span>
                                <span x-text="(msg.debug.duration_ms || 0) + 'ms'"></span>
                            </div>
                        </template>

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
        showDebug: false,
        pingLoading: false,
        pingStatus: null,
        lastDebug: null,
        input: '',
        messages: [],

        init() {
            this.resetChat();
        },

        formatTime() {
            return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
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
                    ],
                    time: this.formatTime(),
                    debug: { engine: 'system-init', model: 'local', duration_ms: 0 }
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

        async testPingServer() {
            this.pingLoading = true;
            this.pingStatus = null;
            try {
                const res = await fetch('{{ route("chatbot.ping") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                this.pingStatus = json;
            } catch (err) {
                this.pingStatus = { error: err.message };
            } finally {
                this.pingLoading = false;
            }
        },

        async submitQuestion() {
            const q = this.input.trim();
            if (!q || this.loading) return;

            const timeNow = this.formatTime();
            this.input = '';
            const historyPayload = this.messages.slice(-4).map(m => ({
                role: m.role,
                text: (m.text || '').substring(0, 800)
            }));

            this.messages.push({
                role: 'user',
                text: q,
                time: timeNow
            });
            this.scrollToBottom();
            this.loading = true;

            const startTime = performance.now();

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

                const durationMs = Math.round(performance.now() - startTime);

                if (!res.ok) {
                    throw new Error('HTTP ' + res.status + ' (' + res.statusText + ')');
                }

                const data = await res.json();
                const debugData = data.debug || {
                    engine: 'api-response',
                    model: 'default',
                    duration_ms: durationMs,
                    answer_length: (data.answer || '').length
                };
                debugData.http_status = '200 OK';
                debugData.client_ms = durationMs;

                this.lastDebug = debugData;

                this.messages.push({
                    role: 'model',
                    text: data.answer || 'Data analisis tidak ditemukan untuk pertanyaan ini.',
                    sources: data.sources || [],
                    followUps: data.follow_ups || [],
                    time: this.formatTime(),
                    debug: debugData
                });
            } catch (e) {
                const durationMs = Math.round(performance.now() - startTime);
                this.lastDebug = {
                    engine: 'client-error',
                    model: e.message,
                    http_status: 'FAILED',
                    duration_ms: durationMs
                };

                this.messages.push({
                    role: 'model',
                    text: 'Mohon maaf, saat ini sistem sedang memproses antrean data. Silakan kirim ulang pertanyaan Anda dalam beberapa saat.' + (this.showDebug ? '\n\n`[DEBUG ERROR: ' + e.message + ']`' : ''),
                    sources: ['Sistem Data Banjarnegara'],
                    followUps: [
                        'Bagaimana perbandingan rasio guru per kecamatan jika siswa MI dan MTs dipisahkan?',
                        'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
                        'Apa 3 keputusan kritis untuk Kepala Dinas pada tahun ini?'
                    ],
                    time: this.formatTime(),
                    debug: this.lastDebug
                });
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        },

        scrollToBottom(smooth = true) {
            this.$nextTick(() => {
                setTimeout(() => {
                    const el = this.$refs.chatScroll;
                    if (el) {
                        el.scrollTo({
                            top: el.scrollHeight,
                            behavior: smooth ? 'smooth' : 'auto'
                        });
                    }
                }, 50);
                setTimeout(() => {
                    const el = this.$refs.chatScroll;
                    if (el) {
                        el.scrollTop = el.scrollHeight;
                    }
                }, 250);
            });
        },

        renderMarkdown(raw) {
            if (!raw) return '';
            try {
                let text = String(raw)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                // Headings
                text = text.replace(/^###\s+(.+)$/gm, '<h4 class="font-bold text-slate-900 mt-2 text-sm">$1</h4>');
                text = text.replace(/^##\s+(.+)$/gm, '<h3 class="font-bold text-slate-900 mt-2.5 text-base">$1</h3>');

                // Horizontal Rule
                text = text.replace(/^---$/gm, '<hr class="my-2 border-slate-200">');

                // Bold & Inline Code
                text = text.replace(/\*\*(.+?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>');
                text = text.replace(/`([^`]+)`/g, '<code class="font-mono text-xs bg-slate-100 text-slate-800 px-1 py-0.5 rounded border border-slate-200">$1</code>');

                // Tables
                if (text.includes('|')) {
                    text = text.replace(/((?:\|[^\n]+\|\n?)+)/g, function(match) {
                        const rows = match.trim().split('\n').filter(r => r.includes('|'));
                        if (rows.length < 2) return match;
                        let html = '<div class="overflow-x-auto my-2 rounded border border-slate-200"><table class="w-full text-xs text-left border-collapse">';
                        rows.forEach((row, rIdx) => {
                            if (row.includes('---')) return; // separator
                            const cols = row.split('|').map(c => c.trim()).filter((c, i, a) => i > 0 && i < a.length - 1);
                            if (cols.length === 0) return;
                            html += '<tr class="' + (rIdx === 0 ? 'bg-slate-100 font-bold border-b border-slate-200' : 'border-b border-slate-100 hover:bg-slate-50') + '">';
                            cols.forEach(col => {
                                html += rIdx === 0 ? '<th class="px-2 py-1.5">' + col + '</th>' : '<td class="px-2 py-1.5">' + col + '</td>';
                            });
                            html += '</tr>';
                        });
                        html += '</table></div>';
                        return html;
                    });
                }

                // Paragraphs & Lists
                return text
                    .split(/\n\n+/)
                    .map(block => {
                        const lines = block.split('\n');
                        if (lines.every(l => /^\s*[-*]\s+/.test(l))) {
                            const items = lines.map(l => '<li class="ml-4 list-disc">' + l.replace(/^\s*[-*]\s+/, '') + '</li>').join('');
                            return '<ul class="space-y-1 my-1">' + items + '</ul>';
                        }
                        if (lines.every(l => /^\s*\d+\.\s+/.test(l))) {
                            const items = lines.map(l => '<li class="ml-4 list-decimal">' + l.replace(/^\s*\d+\.\s+/, '') + '</li>').join('');
                            return '<ol class="space-y-1 my-1">' + items + '</ol>';
                        }
                        return '<p>' + lines.join('<br>') + '</p>';
                    })
                    .join('');
            } catch (err) {
                console.error('Markdown format error:', err);
                return String(raw).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
            }
        }
    };
}
</script>
