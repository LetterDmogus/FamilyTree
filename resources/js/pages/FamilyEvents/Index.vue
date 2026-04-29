<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Calendar, MapPin, Users, Plus, Clock, ChevronRight } from 'lucide-vue-next'
import { ref } from 'vue'
import LocationInput from '@/components/LocationInput.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import familyEvents from '@/routes/family-events'

defineProps<{
  events: any[]
}>()

const showCreateModal = ref(false)

const form = useForm({
  title: '',
  description: '',
  event_date: '',
  event_time: '',
  location: { country: 'ID', province: '', city: '', address: '', lat: -6.2, lng: 106.81 },
  status: 'planning',
})

const submit = () => {
  form.post(familyEvents.store.url(), {
    onSuccess: () => {
      showCreateModal.value = false
      form.reset()
    }
  })
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const breadcrumbs = [
  { title: 'Dashboard', href: dashboard.url() },
  { title: 'Acara Keluarga', href: familyEvents.index.url() },
]
</script>

<template>
  <Head title="Acara Keluarga" />

  <div class="px-4 py-12">
    <div class="flex justify-between items-end mb-12">
        <div>
          <h2 class="text-4xl font-black text-gray-900 tracking-tight">Acara Keluarga</h2>
          <p class="text-gray-500 font-bold mt-2 uppercase tracking-widest text-xs">Rencanakan momen kebersamaan kita</p>
        </div>
        <button @click="showCreateModal = true" class="flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-200">
          <Plus class="w-4 h-4" />
          Buat Acara
        </button>
      </div>

      <div v-if="events.length === 0" class="bg-white rounded-[3rem] p-20 text-center border-2 border-dashed border-gray-100">
        <div class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-gray-300">
          <Calendar class="w-10 h-10" />
        </div>
        <h3 class="text-xl font-black text-gray-900">Belum ada acara</h3>
        <p class="text-gray-400 font-bold mt-2 uppercase text-[10px] tracking-widest">Mulai buat acara keluarga pertama Anda</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="event in events" :key="event.id" class="group bg-white rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all overflow-hidden flex flex-col">
          <div class="p-8 flex-1">
            <div class="flex items-center justify-between mb-6">
              <span :class="[
                'px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border',
                event.status === 'confirmed' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 
                event.status === 'cancelled' ? 'bg-red-50 text-red-600 border-red-100' : 
                'bg-blue-50 text-blue-600 border-blue-100'
              ]">
                {{ event.status }}
              </span>
              <div class="flex items-center gap-1 text-gray-400 text-[10px] font-bold uppercase tracking-widest">
                <Clock class="w-3 h-3" />
                {{ event.event_time || '--:--' }}
              </div>
            </div>

            <h3 class="text-xl font-black text-gray-900 mb-2 group-hover:text-emerald-600 transition-colors">{{ event.title }}</h3>
            <p class="text-gray-500 text-sm line-clamp-2 mb-6 font-medium leading-relaxed">{{ event.description }}</p>

            <div class="space-y-3 mb-8">
              <div class="flex items-center gap-3 text-gray-400 font-bold text-[10px] uppercase tracking-widest">
                <Calendar class="w-4 h-4 text-emerald-500" />
                {{ formatDate(event.event_date) }}
              </div>
              <div class="flex items-center gap-3 text-gray-400 font-bold text-[10px] uppercase tracking-widest">
                <MapPin class="w-4 h-4 text-emerald-500" />
                {{ event.location?.city || 'Lokasi belum ditentukan' }}
              </div>
            </div>

            <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-2xl">
              <div class="flex -space-x-2">
                <div v-for="i in 3" :key="i" class="w-8 h-8 rounded-xl border-2 border-white bg-gray-200 flex items-center justify-center text-[10px] font-black text-gray-400">
                  <Users class="w-3 h-3" />
                </div>
              </div>
              <div class="text-[9px] font-black uppercase tracking-widest text-emerald-600">
                {{ event.going_count }} Hadir • {{ event.maybe_count }} Mungkin
              </div>
            </div>
          </div>

          <Link :href="familyEvents.show.url({ event: event.id })" class="bg-gray-50 hover:bg-emerald-600 hover:text-white p-6 flex items-center justify-between transition-all group/btn border-t border-gray-100">
            <span class="text-[10px] font-black uppercase tracking-[0.2em]">Lihat Detail</span>
            <ChevronRight class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" />
          </Link>
        </div>
      </div>
    </div>

    <!-- Create Event Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
      <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col h-[85vh]">
        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
          <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Buat Acara Baru</h2>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Rencanakan hari spesial keluarga</p>
          </div>
          <button @click="showCreateModal = false" class="p-3 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-2xl transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
          <form @submit.prevent="submit" class="space-y-8">
            <div class="space-y-2">
              <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Judul Acara</label>
              <input v-model="form.title" type="text" class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent focus:border-emerald-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" placeholder="Contoh: Arisan Keluarga Besar" required />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Tanggal</label>
                <input v-model="form.event_date" type="date" class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent focus:border-emerald-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" required />
              </div>
              <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Waktu</label>
                <input v-model="form.event_time" type="time" class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent focus:border-emerald-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" />
              </div>
            </div>

            <div class="space-y-2">
              <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Deskripsi</label>
              <textarea v-model="form.description" rows="3" class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent focus:border-emerald-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none resize-none" placeholder="Apa saja yang perlu dipersiapkan?"></textarea>
            </div>

            <div class="space-y-2">
              <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Lokasi</label>
              <LocationInput v-model="form.location" />
            </div>
          </form>
        </div>

        <div class="p-8 border-t border-gray-100 flex gap-4">
          <button @click="showCreateModal = false" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-all">Batal</button>
          <button @click="submit" :disabled="form.processing" class="flex-[2] py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-200 disabled:opacity-50">
            {{ form.processing ? 'Menyimpan...' : 'Simpan Acara' }}
          </button>
        </div>
      </div>
    </div>
</template>
