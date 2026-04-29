<script setup lang="ts">
import { useHttp, usePage } from '@inertiajs/vue3'
import { X, Calendar, User, ShieldCheck, CheckCheck } from 'lucide-vue-next'

const props = defineProps({
  open: { type: Boolean, required: true },
  letter: { type: Object, required: true }
})

const emit = defineEmits(['close', 'read'])
const http = useHttp()
const page = usePage()

function markAsRead() {
  if (props.letter.read_at) {
return
}

  http.post(`/api/letters/${props.letter.id}/read`, {}, {
    onSuccess: () => {
      emit('read', props.letter.id)
    }
  })
}

const parseMarkdown = (text: string) => {
  if (!text) {
return ''
}

  return text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/\n/g, '<br/>')
}

function formatDate(dateStr: string) {
  if (!dateStr) {
return null
}

  try {
    return new Intl.DateTimeFormat('id-ID', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}
</script>

<template>
  <transition
    enter-active-class="duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="open" class="fixed inset-0 z-[110] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-3xl rounded-[3rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300 flex flex-col max-h-[90vh]">
        <!-- Header / Envelope Top -->
        <div class="px-12 py-10 bg-amber-50 border-b border-amber-100 relative">
          <button @click="$emit('close')" class="absolute top-8 right-8 p-3 hover:bg-amber-100 rounded-full transition-colors text-amber-900/40 hover:text-amber-900">
            <X class="w-6 h-6" />
          </button>
          
          <div class="space-y-6">
            <div class="flex items-center gap-3">
               <span class="px-4 py-1 bg-amber-500 text-white text-[8px] font-black uppercase tracking-[0.3em] rounded-full shadow-lg shadow-amber-200">Surat Keluarga</span>
               <span v-if="letter.read_at" class="flex items-center gap-1.5 text-[8px] font-black uppercase tracking-widest text-emerald-600">
                  <ShieldCheck class="w-3 h-3" /> Terbuka
               </span>
            </div>
            
            <h2 class="text-4xl font-black text-slate-900 tracking-tighter leading-tight uppercase">{{ letter.subject }}</h2>
            
            <div class="flex flex-wrap gap-8 pt-2">
               <div class="flex items-center gap-3">
                 <div class="w-10 h-10 rounded-2xl bg-white shadow-sm flex items-center justify-center text-amber-500"><User class="w-5 h-5" /></div>
                 <div>
                   <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Pengirim</p>
                   <p class="text-sm font-bold text-slate-700">{{ letter.sender?.profile?.full_name || 'Keluarga' }}</p>
                 </div>
               </div>
               <div class="flex items-center gap-3">
                 <div class="w-10 h-10 rounded-2xl bg-white shadow-sm flex items-center justify-center text-amber-500"><Calendar class="w-5 h-5" /></div>
                 <div>
                   <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tanggal Kirim</p>
                   <p class="text-sm font-bold text-slate-700">{{ formatDate(letter.created_at) }}</p>
                 </div>
               </div>
            </div>
          </div>
        </div>

        <!-- Letter Body -->
        <div class="flex-1 overflow-y-auto p-12 bg-[#fffdfa] relative">
          <!-- Letter Texture Decor -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-amber-100/20 rounded-bl-full pointer-events-none"></div>
          
          <div 
            class="prose prose-slate max-w-none text-lg text-slate-800 leading-relaxed font-serif selection:bg-amber-100" 
            v-html="parseMarkdown(letter.content)"
          ></div>

          <!-- Formal Footer Decor -->
          <div class="mt-20 pt-10 border-t border-amber-100 flex justify-between items-end">
             <div class="space-y-1">
                <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.3em]">Dicatat Pada Sistem</p>
                <p class="text-[10px] font-bold text-slate-400 font-mono">{{ new Date(letter.created_at).getTime() }}</p>
             </div>
             <div class="text-right">
                <p class="text-xs italic font-serif text-slate-400">Salam hangat,</p>
                <p class="text-lg font-black text-slate-900 mt-1">{{ letter.sender?.profile?.full_name || 'Keluarga' }}</p>
             </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="px-12 py-6 bg-gray-50 border-t flex items-center justify-center gap-4">
            <button 
                v-if="!letter.read_at && letter.recipient_id === page.props.auth.user.id"
                @click="markAsRead" 
                :disabled="http.processing"
                class="px-8 py-4 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-emerald-700 transition-all shadow-xl flex items-center gap-3 disabled:opacity-50"
            >
                <CheckCheck class="w-4 h-4" />
                {{ http.processing ? 'Memproses...' : 'Tandai Sudah Dibaca' }}
            </button>
            <button @click="$emit('close')" class="px-10 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-emerald-600 transition-all shadow-xl">Tutup Surat</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.prose :deep(strong) { font-weight: 900; color: #0f172a; }
.prose :deep(em) { font-style: italic; color: #475569; }
</style>