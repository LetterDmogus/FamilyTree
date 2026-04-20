<script setup>
import { ref } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'

const props = defineProps({
  open: Boolean
})

const emit = defineEmits(['close', 'start-export'])

const settings = ref({
  quality: 2,
  background: 'grid',
  details: 'full',
  title: '' // New title state
})

function handleStart() {
  emit('start-export', { ...settings.value })
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[480px] rounded-[2.5rem] p-8 font-sans">
      <DialogHeader>
        <DialogTitle class="text-2xl font-black tracking-tighter text-gray-900">Export Silsilah</DialogTitle>
        <DialogDescription class="text-xs font-bold uppercase text-gray-400 tracking-widest mt-1">
          Simpan diagram sebagai file PNG berkualitas tinggi
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6 py-4">
        <!-- Title Input -->
        <div class="space-y-3">
          <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Judul Diagram (Opsional)</Label>
          <Input 
            v-model="settings.title" 
            placeholder="Contoh: Keluarga Besar Agus Santoso" 
            class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Quality Selection -->
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Kualitas</Label>
            <div class="grid grid-cols-3 gap-1.5 p-1 bg-gray-50 rounded-2xl">
              <button v-for="q in [1, 2, 3]" :key="q" @click="settings.quality = q" 
                :class="['py-2 text-[9px] font-black uppercase rounded-xl transition-all', settings.quality === q ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400']">
                {{ q }}x
              </button>
            </div>
          </div>

          <!-- Details Toggle -->
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Konten</Label>
            <div class="flex gap-1.5 p-1 bg-gray-50 rounded-2xl">
              <button @click="settings.details = 'full'" 
                :class="['flex-1 py-2 text-[9px] font-black uppercase rounded-xl transition-all', settings.details === 'full' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-400']">
                Lengkap
              </button>
              <button @click="settings.details = 'compact'" 
                :class="['flex-1 py-2 text-[9px] font-black uppercase rounded-xl transition-all', settings.details === 'compact' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-400']">
                Kompak
              </button>
            </div>
          </div>
        </div>

        <!-- Background Selection -->
        <div class="space-y-3">
          <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Latar Belakang</Label>
          <div class="grid grid-cols-3 gap-2">
            <button @click="settings.background = 'grid'" 
              :class="['p-3 rounded-2xl border-2 transition-all flex flex-col items-center gap-1.5', settings.background === 'grid' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-50 bg-gray-50 text-gray-400']">
              <div class="w-5 h-5 rounded-md border border-dashed border-current opacity-40"></div>
              <span class="text-[8px] font-black uppercase">Grid</span>
            </button>
            <button @click="settings.background = 'white'" 
              :class="['p-3 rounded-2xl border-2 transition-all flex flex-col items-center gap-1.5', settings.background === 'white' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-50 bg-gray-50 text-gray-400']">
              <div class="w-5 h-5 rounded-md bg-white border border-gray-200"></div>
              <span class="text-[8px] font-black uppercase">Putih</span>
            </button>
            <button @click="settings.background = 'transparent'" 
              :class="['p-3 rounded-2xl border-2 transition-all flex flex-col items-center gap-1.5', settings.background === 'transparent' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-50 bg-gray-50 text-gray-400']">
              <div class="w-5 h-5 rounded-md border-2 border-dotted border-gray-300"></div>
              <span class="text-[8px] font-black uppercase">Bening</span>
            </button>
          </div>
        </div>
      </div>

      <DialogFooter class="sm:justify-start gap-3 mt-4">
        <Button @click="handleStart" class="flex-1 py-6 bg-gray-900 text-white rounded-3xl font-black uppercase text-[10px] tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-gray-100">
          Unduh PNG
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
