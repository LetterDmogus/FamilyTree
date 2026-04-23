<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue';
import { useSidePanels } from '@/composables/useSidePanels';
import { X, Send, Loader2, Sparkles, BrainCircuit, Trash2, Minimize2 } from 'lucide-vue-next';
import { queryFamily, getSummary, FamilyMember } from '@/lib/queryEngine';

const props = defineProps<{
    treeData: FamilyMember;
}>();

const { isAiPanelOpen, closeAiPanel } = useSidePanels();
const messages = ref<{ role: 'user' | 'assistant' | 'system' | 'tool'; content: string; thought?: string }[]>([]);
const inputMessage = ref('');
const isProcessing = ref(false);
const currentThought = ref<string | null>(null);
const chatContainer = ref<HTMLElement | null>(null);

const GROQ_API_KEY = import.meta.env.VITE_GROQ_API_KEY;

// Auto-scroll to bottom
const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const getSystemPrompt = () => {
    const summary = getSummary(props.treeData);
    return `You are "Wise Mystical Tree", a helpful family tree assistant. 
The user is viewing their family tree application.
You have access to tools to query the family data.
ALWAYS use tools first before answering questions about specific people or statistics.
Do NOT assume or hallucinate family data — only use what the tools return.

When answering:
- Be concise and friendly
- Use the user's local language (follow whatever language the user uses)
- If tool returns empty, say the data is not found
- Never expose raw JSON to the user unless specifically asked for debugging.

Current context summary:
${JSON.stringify(summary, null, 2)}
`;
};

async function sendMessage() {
    if (!inputMessage.value.trim() || isProcessing.value) return;

    const userMsg = inputMessage.value.trim();
    
    // Slash commands
    if (userMsg === '/clear') {
        messages.value = [];
        inputMessage.value = '';
        return;
    }
    
    if (userMsg === '/compact') {
        await compactHistory();
        inputMessage.value = '';
        return;
    }

    messages.value.push({ role: 'user', content: userMsg });
    inputMessage.value = '';
    isProcessing.value = true;
    
    await scrollToBottom();

    let attempts = 0;
    const maxRetries = 3;
    let success = false;

    while (attempts < maxRetries && !success) {
        try {
            attempts++;
            if (attempts > 1) {
                currentThought.value = `Terjadi error, mencoba mengirim ulang (${attempts}/${maxRetries})...`;
            } else {
                currentThought.value = 'Merenungkan pertanyaan Anda...';
            }

            await processChat();
            success = true;
        } catch (error) {
            console.error(`Attempt ${attempts} failed:`, error);
            if (attempts === maxRetries) {
                messages.value.push({ 
                    role: 'assistant', 
                    content: "Maaf, silsilah Anda terlalu dalam atau koneksi sedang sibuk. Saya sudah mencoba 3 kali namun tetap gagal. Silakan coba lagi nanti atau gunakan /clear." 
                });
            } else {
                // Wait a bit before retry
                await new Promise(resolve => setTimeout(resolve, 1000 * attempts));
            }
        }
    }

    isProcessing.value = false;
    currentThought.value = null;
    await scrollToBottom();
}

async function compactHistory() {
    if (messages.value.length < 2) return;
    
    isProcessing.value = true;
    currentThought.value = 'Meringkas percakapan...';
    
    try {
        const response = await fetch("https://api.groq.com/openai/v1/chat/completions", {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${GROQ_API_KEY}`,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                model: "llama-3.3-70b-versatile",
                messages: [
                    { role: 'system', content: 'Ringkas percakapan berikut dalam 1 paragraf padat agar context window hemat. Fokus pada fakta keluarga yang sudah ditanyakan.' },
                    ...messages.value.filter(m => m.role !== 'tool').map(m => ({ role: m.role, content: m.content }))
                ]
            })
        });
        
        const data = await response.json();
        const summary = data.choices[0].message.content;
        
        messages.value = [
            { role: 'assistant', content: `[History Ringkas]: ${summary}` }
        ];
    } catch (e) {
        console.error(e);
    } finally {
        isProcessing.value = false;
        currentThought.value = null;
    }
}

async function processChat() {
    const tools = [
        {
            type: "function",
            function: {
                name: "queryFamily",
                description: "Mencari data keluarga dengan filter (gender, is_alive, dll), sort, select, aggregate (count), atau traverse (navigasi silsilah).",
                parameters: {
                    type: "object",
                    properties: {
                        operations: {
                            type: "array",
                            items: {
                                type: "object",
                                properties: {
                                    op: { type: "string", enum: ["filter", "sort", "select", "aggregate", "traverse"] },
                                    field: { type: "string", description: "Field model (panggilan, full_name, gender, birth_date, is_alive, pekerjaan)" },
                                    value: { type: "string", description: "Nilai filter" },
                                    order: { type: "string", enum: ["asc", "desc"], description: "Urutan sort" },
                                    limit: { type: "number", description: "Batas data yang diambil" },
                                    relation: { type: "string", enum: ["anak", "cucu"], description: "Hubungan keluarga" },
                                    id: { type: "number", description: "ID anggota untuk traverse" },
                                    type: { type: "string", enum: ["ancestors", "descendants"], description: "Jenis navigasi" }
                                }
                            }
                        }
                    },
                    required: ["operations"]
                }
            }
        }
    ];

    let apiMessages = [
        { role: 'system', content: getSystemPrompt() },
        ...messages.value.filter(m => m.role !== 'tool').map(m => ({ role: m.role, content: m.content }))
    ];

    const response = await fetch("https://api.groq.com/openai/v1/chat/completions", {
        method: "POST",
        headers: {
            "Authorization": `Bearer ${GROQ_API_KEY}`,
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            model: "llama-3.3-70b-versatile",
            messages: apiMessages,
            tools: tools,
            tool_choice: "auto"
        })
    });

    const data = await response.json();
    if (data.error) throw new Error(data.error.message);
    
    const message = data.choices[0].message;

    if (message.tool_calls) {
        const toolResults = [];
        for (const toolCall of message.tool_calls) {
            const args = JSON.parse(toolCall.function.arguments);
            currentThought.value = `Menjalankan: ${toolCall.function.name} - ${args.operations.map((o: any) => o.op).join(' → ')}`;
            
            const result = queryFamily(props.treeData, args.operations);
            
            apiMessages.push(message);
            apiMessages.push({
                role: 'tool',
                tool_call_id: toolCall.id,
                name: toolCall.function.name,
                content: JSON.stringify(result)
            });
            
            toolResults.push({ thought: currentThought.value, result });
        }

        // Final response
        const finalResponse = await fetch("https://api.groq.com/openai/v1/chat/completions", {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${GROQ_API_KEY}`,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                model: "llama-3.3-70b-versatile",
                messages: apiMessages
            })
        });

        const finalData = await finalResponse.json();
        messages.value.push({ 
            role: 'assistant', 
            content: finalData.choices[0].message.content,
            thought: toolResults.map(tr => tr.thought).join('\n')
        });
    } else {
        messages.value.push({ role: 'assistant', content: message.content });
    }
}

watch(isAiPanelOpen, (val) => {
    if (val) {
        scrollToBottom();
    }
});
</script>

<template>
    <div v-if="isAiPanelOpen" class="w-96 border-l bg-white flex flex-col h-full shadow-2xl z-50 overflow-hidden text-gray-900 select-none animate-in slide-in-from-right duration-300">
        <!-- Header -->
        <div class="p-6 border-b flex items-center justify-between bg-gradient-to-r from-emerald-50 to-emerald-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shadow-lg overflow-hidden border-2 border-emerald-500">
                    <img src="/storage/profiles/WiseTree.png" alt="Wise Mystical Tree" class="w-full h-full object-cover" />
                </div>

                <div>
                    <h3 class="font-black text-emerald-900 leading-tight">Wise Mystical Tree</h3>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Online</span>
                    </div>
                </div>
            </div>
            <button @click="closeAiPanel" class="p-2 hover:bg-black/5 rounded-full transition-colors">
                <X class="w-5 h-5 text-gray-400" />
            </button>
        </div>

        <!-- Chat Area -->
        <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-50/30">
            <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center space-y-4 opacity-50">
                <BrainCircuit class="w-12 h-12 text-emerald-600 mb-2" />
                <p class="text-sm font-bold text-gray-500 uppercase tracking-tighter">Ajukan pertanyaan tentang silsilah keluarga Anda.</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <button @click="inputMessage = 'Siapa cucu tertua saya?'; sendMessage()" class="px-3 py-1.5 bg-white border border-gray-200 rounded-full text-[10px] font-bold hover:border-emerald-500 transition-all">Cucu Tertua?</button>
                    <button @click="inputMessage = 'Berapa total anggota keluarga yang masih hidup?'; sendMessage()" class="px-3 py-1.5 bg-white border border-gray-200 rounded-full text-[10px] font-bold hover:border-emerald-500 transition-all">Total Hidup?</button>
                </div>
            </div>

            <div v-for="(msg, idx) in messages" :key="idx" :class="['flex flex-col', msg.role === 'user' ? 'items-end' : 'items-start']">
                <!-- Thought Process (B) -->
                <div v-if="msg.thought" class="mb-2 w-full">
                    <details class="group">
                        <summary class="list-none flex items-center gap-2 text-[10px] font-black text-emerald-600 uppercase tracking-widest cursor-pointer hover:opacity-70">
                            <BrainCircuit class="w-3 h-3" />
                            <span>Proses Berpikir</span>
                        </summary>
                        <div class="mt-2 p-3 bg-emerald-50/50 border border-emerald-100 rounded-xl text-[11px] font-medium text-emerald-800 italic leading-relaxed">
                            {{ msg.thought }}
                        </div>
                    </details>
                </div>

                <div :class="['max-w-[85%] p-4 rounded-2xl shadow-sm text-sm font-medium leading-relaxed', 
                    msg.role === 'user' ? 'bg-emerald-600 text-white rounded-tr-none' : 'bg-white border border-gray-100 text-gray-800 rounded-tl-none']">
                    {{ msg.content }}
                </div>
            </div>

            <!-- Thinking Indicator -->
            <div v-if="isProcessing" class="flex flex-col items-start">
                <div class="flex items-center gap-2 text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-2">
                    <Loader2 class="w-3 h-3 animate-spin" />
                    <span>{{ currentThought }}</span>
                </div>
                <div class="max-w-[85%] p-4 bg-white border border-gray-100 rounded-2xl rounded-tl-none shadow-sm flex gap-1">
                    <span class="w-1.5 h-1.5 bg-gray-300 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-gray-300 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-gray-300 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 border-t bg-white">
            <div class="relative flex items-center gap-2">
                <input 
                    v-model="inputMessage" 
                    @keyup.enter="sendMessage"
                    placeholder="Tanya pohon kebijaksanaan..."
                    class="flex-1 bg-gray-50 border-none rounded-2xl px-5 py-3.5 text-sm font-bold placeholder:text-gray-400 focus:ring-2 focus:ring-emerald-500 transition-all"
                />
                <button 
                    @click="sendMessage" 
                    :disabled="!inputMessage.trim() || isProcessing"
                    class="p-3 bg-emerald-600 text-white rounded-xl shadow-lg hover:bg-emerald-700 disabled:opacity-50 disabled:shadow-none transition-all"
                >
                    <Send v-if="!isProcessing" class="w-5 h-5" />
                    <Loader2 v-else class="w-5 h-5 animate-spin" />
                </button>
            </div>
            <div class="mt-3 flex items-center justify-between px-2">
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Wise Mystical Tree Agent v1.0</p>
                <div class="flex gap-3">
                    <button @click="inputMessage = '/compact'; sendMessage()" title="Ringkas Sejarah" class="text-gray-400 hover:text-emerald-600 transition-colors"><Minimize2 class="w-3.5 h-3.5" /></button>
                    <button @click="inputMessage = '/clear'; sendMessage()" title="Hapus Chat" class="text-gray-400 hover:text-red-500 transition-colors"><Trash2 class="w-3.5 h-3.5" /></button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
