<script setup>
import { router } from '@inertiajs/vue3'
import { X, Mail, Send, Type, AlignLeft, Info } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps({
  open: { type: Boolean, required: true },
  recipient: { type: Object, required: true }
})

const emit = defineEmits(['close'])

const form = ref({
  subject: '',
  content: '',
  style: 'classic',
  processing: false
})

function submit() {
  if (!form.value.subject.trim() || !form.value.content.trim()) {
return
}

  form.value.processing = true
  router.post(`/api/users/${props.recipient.id}/letter`, {
    subject: form.value.subject,
    content: form.value.content,
    style: form.value.style
  }, {
    onSuccess: () => {
      form.value.subject = ''
      form.value.content = ''
      form.value.processing = false
      emit('close')
    },
    onError: () => {
      form.value.processing = false
    }
  })
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
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-10 py-8 bg-amber-50 border-b border-amber-100 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-500 text-white rounded-2xl flex items-center justify-center shadow-lg">
              <Mail class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Kirim Surat Keluarga</h3>
              <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Kepada: {{ recipient.full_name }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="p-3 hover:bg-amber-100 rounded-full transition-colors text-amber-900/40 hover:text-amber-900">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Form Body -->
        <div class="flex-1 overflow-y-auto p-10 space-y-8">
          <!-- Subject Input -->
          <div class="space-y-3">
            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
              <Type class="w-3 h-3" /> Subjek Surat
            </label>
            <input 
              v-model="form.subject"
              type="text"
              placeholder="Contoh: Nasihat Untuk Masa Depanmu"
              class="w-full bg-slate-50 border-2 border-transparent focus:border-amber-400 focus:bg-white rounded-2xl px-6 py-4 text-sm font-bold text-slate-700 outline-none transition-all shadow-inner"
            />
          </div>

          <!-- Content Input -->
          <div class="space-y-3">
            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
              <AlignLeft class="w-3 h-3" /> Isi Pesan Formal
            </label>
            <textarea 
              v-model="form.content"
              placeholder="Tuliskan petuah, doa, atau pesan penting di sini... (Mendukung format Markdown)"
              class="w-full bg-slate-50 border-2 border-transparent focus:border-amber-400 focus:bg-white rounded-[2rem] px-6 py-6 text-sm font-bold text-slate-700 outline-none transition-all shadow-inner min-h-[250px] resize-none"
            ></textarea>
          </div>

          <!-- Helper -->
          <div class="flex items-start gap-4 p-5 bg-blue-50 rounded-2xl border border-blue-100">
             <Info class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
             <div class="text-[10px] text-blue-700 font-bold leading-relaxed uppercase tracking-widest">
                Surat ini bersifat privat dan hanya dapat dibaca oleh penerima. Kamu bisa menggunakan **tebal** dan *miring* untuk menekankan poin penting.
             </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-10 py-8 bg-gray-50 border-t flex items-center justify-end gap-4">
          <button @click="$emit('close')" class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">Batal</button>
          <button 
            @click="submit"
            :disabled="form.processing || !form.subject.trim() || !form.content.trim()"
            class="px-10 py-4 bg-amber-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-600 transition-all shadow-xl shadow-amber-200 disabled:opacity-50 flex items-center gap-3"
          >
            <Send class="w-4 h-4" /> {{ form.processing ? 'Mengirim...' : 'Kirim Surat Sekarang' }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>
