<script setup>
import { 
  Download, 
  Settings2, 
  Type, 
  Layers, 
  ImageIcon,
  X,
  Check,
  FileImage,
  Monitor
} from 'lucide-vue-next'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  open: Boolean
})

const emit = defineEmits(['close', 'start-export'])

const settings = ref({
  quality: 2,
  background: 'grid',
  details: 'full',
  title: ''
})

function handleStart() {
  emit('start-export', { ...settings.value })
}

const backgroundOptions = [
  { id: 'grid', label: 'Grid', icon: 'grid' },
  { id: 'white', label: 'Putih', icon: 'square' },
  { id: 'transparent', label: 'Bening', icon: 'monitor' },
]
</script>

<template>
  <transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform translate-x-full"
    enter-to-class="transform translate-x-0"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="transform translate-x-0"
    leave-to-class="transform translate-x-full"
  >
    <div v-if="open" class="fixed top-0 right-0 w-96 h-full bg-white border-l shadow-2xl z-50 flex flex-col font-sans select-none text-gray-900">
      <!-- Header (Matching AI/Detail style) -->
      <div class="p-6 border-b flex items-center justify-between bg-gradient-to-r from-emerald-50 to-emerald-50 flex-shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-md border-2 border-emerald-500">
            <ImageIcon class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-black text-emerald-900 leading-tight">Export Silsilah</h3>
            <div class="flex items-center gap-1">
              <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest text-opacity-70">PNG Image Engine</span>
            </div>
          </div>
        </div>
        <button @click="$emit('close')" class="p-2 hover:bg-black/5 rounded-full transition-colors">
          <X class="w-5 h-5 text-gray-400" />
        </button>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar bg-slate-50/30">
        
        <!-- Summary Card -->
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
           <div class="w-12 h-12 bg-slate-50 rounded-xl border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden relative">
              <FileImage class="w-6 h-6 text-gray-300" />
              <div v-if="settings.background === 'grid'" class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#000 0.5px, transparent 0.5px); background-size: 3px 3px;"></div>
           </div>
           <div>
              <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Siap diunduh sebagai</p>
              <h4 class="text-xs font-black text-gray-900 uppercase">PNG • {{ settings.quality }}x Kualitas</h4>
           </div>
        </div>

        <!-- Section: Title -->
        <div class="space-y-3">
          <div class="flex items-center gap-2 text-gray-400 px-1">
            <Type class="w-3.5 h-3.5" />
            <span class="text-[10px] font-black uppercase tracking-widest">Judul Diagram</span>
          </div>
          <Input 
            v-model="settings.title" 
            placeholder="Ketik judul silsilah..." 
            class="rounded-2xl border-gray-100 bg-white font-bold text-sm focus:ring-2 focus:ring-emerald-500 py-6 px-5 transition-all"
          />
        </div>

        <!-- Section: Render Logic -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-3">
             <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1 block text-center">Resolusi</Label>
             <div class="flex gap-1 bg-gray-100/50 p-1 rounded-2xl border border-gray-100">
                <button v-for="q in [1, 2, 3]" :key="q" @click="settings.quality = q" 
                  :class="['flex-1 py-2 text-[10px] font-black rounded-xl transition-all', settings.quality === q ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-400 hover:opacity-70']">
                  {{ q }}x
                </button>
             </div>
          </div>
          <div class="space-y-3">
             <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1 block text-center">Detail</Label>
             <div class="flex gap-1 bg-gray-100/50 p-1 rounded-2xl border border-gray-100">
                <button @click="settings.details = 'full'" 
                  :class="['flex-1 py-2 text-[10px] font-black rounded-xl transition-all', settings.details === 'full' ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-400 hover:opacity-70']">
                  Full
                </button>
                <button @click="settings.details = 'compact'" 
                  :class="['flex-1 py-2 text-[10px] font-black rounded-xl transition-all', settings.details === 'compact' ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-400 hover:opacity-70']">
                  Kecil
                </button>
             </div>
          </div>
        </div>

        <!-- Section: Theme -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 text-gray-400 px-1">
            <Layers class="w-3.5 h-3.5" />
            <span class="text-[10px] font-black uppercase tracking-widest">Tema Latar</span>
          </div>
          <div class="space-y-2">
            <button v-for="bg in backgroundOptions" :key="bg.id" @click="settings.background = bg.id" 
              :class="['w-full p-3.5 rounded-2xl border-2 transition-all flex items-center justify-between text-left group', settings.background === bg.id ? 'border-emerald-500 bg-emerald-50/50' : 'border-gray-50 bg-white hover:border-gray-200 shadow-sm']">
              <div class="flex items-center gap-3">
                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center border transition-all', settings.background === bg.id ? 'bg-white border-emerald-200' : 'bg-slate-50 border-gray-100']">
                  <div v-if="bg.id === 'grid'" class="w-4 h-4 opacity-30" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 4px 4px;"></div>
                  <div v-if="bg.id === 'white'" class="w-4 h-4 bg-gray-200 rounded-sm"></div>
                  <div v-if="bg.id === 'transparent'" class="w-4 h-4 border border-dotted border-gray-300 rounded-sm flex items-center justify-center"><Monitor class="w-3 h-3 text-gray-300" /></div>
                </div>
                <span :class="['text-[11px] font-black uppercase tracking-widest', settings.background === bg.id ? 'text-emerald-900' : 'text-gray-500']">{{ bg.label }}</span>
              </div>
              <div v-if="settings.background === bg.id" class="w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center shadow-md animate-in zoom-in duration-200">
                <Check class="w-3 h-3 text-white stroke-[4]" />
              </div>
            </button>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="p-4 border-t bg-white flex-shrink-0">
        <Button @click="handleStart" class="w-full py-7 bg-emerald-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.15em] hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-100 flex items-center justify-center gap-3 active:scale-[0.98]">
          <Download class="w-4 h-4 stroke-[3]" />
          Unduh PNG
        </Button>
        <p class="text-center text-[9px] font-bold text-gray-300 uppercase tracking-widest mt-4">Proses render ~2-5 detik</p>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>