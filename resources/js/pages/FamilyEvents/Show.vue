<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import { Calendar, MapPin, Users, Clock, ArrowLeft, Check, X, HelpCircle, User as UserIcon, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import familyEvents from '@/routes/family-events'

const props = defineProps<{
  event: any
  myRsvp: any
}>()

const rsvpForm = useForm({
  status: props.myRsvp?.status || 'going',
  headcount: props.myRsvp?.headcount || 1,
  notes: props.myRsvp?.notes || '',
})

const submitRsvp = (status: string) => {
  rsvpForm.status = status
  rsvpForm.post(familyEvents.rsvp.url({ event: props.event.id }))
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const getRsvpStatusLabel = (status: string) => {
  switch (status) {
    case 'going': return 'Hadir'
    case 'not_going': return 'Tidak Hadir'
    case 'maybe': return 'Mungkin'
    default: return status
  }
}

const breadcrumbs = [
  { title: 'Dashboard', href: dashboard.url() },
  { title: 'Acara Keluarga', href: familyEvents.index.url() },
  { title: props.event.title, href: familyEvents.show.url({ event: props.event.id }) },
]

const deleteEvent = () => {
  if (confirm('Apakah Anda yakin ingin menghapus acara ini? Tindakan ini tidak dapat dibatalkan.')) {
    router.delete(familyEvents.destroy.url({ event: props.event.id }))
  }
}
</script>

<template>
  <Head :title="event.title" />

  <div class="px-4 py-12">
    <div class="flex justify-between items-center mb-8">
      <Link :href="familyEvents.index.url()" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-900 font-black uppercase tracking-widest text-[10px] transition-colors group">
        <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
        Kembali ke Daftar
      </Link>

      <button v-if="$page.props.auth.user.id === event.user_id || $page.props.auth.permissions.includes('manage_settings')" 
        @click="deleteEvent" 
        class="flex items-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 rounded-xl font-black uppercase tracking-widest text-[9px] hover:bg-red-600 hover:text-white transition-all border border-red-100">
        <Trash2 class="w-3.5 h-3.5" />
        Hapus Acara
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Left Column: Details -->
        <div class="lg:col-span-2 space-y-12">
          <div class="bg-white rounded-[3rem] p-12 shadow-sm border border-gray-100">
            <div class="flex items-center gap-4 mb-8">
              <span :class="[
                'px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border',
                event.status === 'confirmed' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 
                event.status === 'cancelled' ? 'bg-red-50 text-red-600 border-red-100' : 
                'bg-blue-50 text-blue-600 border-blue-100'
              ]">
                {{ event.status }}
              </span>
              <div class="h-1 w-1 bg-gray-200 rounded-full"></div>
              <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Dibuat oleh {{ event.creator.name }}</p>
            </div>

            <h1 class="text-5xl font-black text-gray-900 tracking-tighter mb-8 leading-tight">{{ event.title }}</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
              <div class="flex items-start gap-5 p-6 bg-gray-50 rounded-[2rem] border border-gray-100 transition-all hover:bg-white hover:shadow-xl group">
                <div class="w-12 h-12 bg-white text-emerald-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                  <Calendar class="w-6 h-6" />
                </div>
                <div>
                  <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal</h4>
                  <p class="text-lg font-black text-gray-900">{{ formatDate(event.event_date) }}</p>
                </div>
              </div>

              <div class="flex items-start gap-5 p-6 bg-gray-50 rounded-[2rem] border border-gray-100 transition-all hover:bg-white hover:shadow-xl group">
                <div class="w-12 h-12 bg-white text-blue-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                  <Clock class="w-6 h-6" />
                </div>
                <div>
                  <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Waktu</h4>
                  <p class="text-lg font-black text-gray-900">{{ event.event_time || '--:--' }} WIB</p>
                </div>
              </div>
            </div>

            <div class="space-y-8">
              <div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Lokasi</h4>
                <div class="flex items-start gap-4 p-6 bg-emerald-50/50 rounded-[2rem] border border-emerald-100">
                  <MapPin class="w-6 h-6 text-emerald-600 mt-1" />
                  <div>
                    <p class="font-black text-emerald-900 text-lg">{{ event.location?.address || 'Lokasi belum spesifik' }}</p>
                    <p class="text-sm font-bold text-emerald-600/70 uppercase tracking-wider mt-1">{{ event.location?.city }}, {{ event.location?.province }}</p>
                  </div>
                </div>
              </div>

              <div>
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Tentang Acara</h4>
                <p class="text-gray-600 text-lg leading-relaxed font-medium">{{ event.description }}</p>
              </div>
            </div>
          </div>

          <!-- RSVP List -->
          <div class="space-y-8">
            <h3 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-4 px-4">
              Daftar Kehadiran
              <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-lg text-[10px] uppercase font-black tracking-widest">
                {{ event.rsvps.length }} Respon
              </span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="rsvp in event.rsvps" :key="rsvp.id" class="flex items-center gap-5 p-5 bg-white rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-md transition-all group">
                <div class="relative">
                  <div class="w-14 h-14 rounded-2xl overflow-hidden bg-gray-50 flex items-center justify-center border-2 border-white shadow-sm">
                    <img v-if="rsvp.user.profile?.photo_url" :src="rsvp.user.profile.photo_url" class="w-full h-full object-cover" />
                    <UserIcon v-else class="w-6 h-6 text-gray-300" />
                  </div>
                  <div :class="[
                    'absolute -bottom-1 -right-1 w-6 h-6 rounded-lg flex items-center justify-center text-white border-2 border-white',
                    rsvp.status === 'going' ? 'bg-emerald-500' : rsvp.status === 'not_going' ? 'bg-red-500' : 'bg-blue-500'
                  ]">
                    <Check v-if="rsvp.status === 'going'" class="w-3 h-3" />
                    <X v-if="rsvp.status === 'not_going'" class="w-3 h-3" />
                    <HelpCircle v-if="rsvp.status === 'maybe'" class="w-3 h-3" />
                  </div>
                </div>
                <div>
                  <h4 class="font-black text-gray-900 text-sm group-hover:text-emerald-600 transition-colors">{{ rsvp.user.name }}</h4>
                  <div class="flex items-center gap-2 mt-1">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">
                      {{ getRsvpStatusLabel(rsvp.status) }} 
                      <span v-if="rsvp.headcount > 1" class="text-emerald-600 ml-1">+{{ rsvp.headcount - 1 }} Orang</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: RSVP Actions -->
        <div class="space-y-8">
          <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-gray-100 sticky top-12">
            <h3 class="text-xl font-black text-gray-900 tracking-tight mb-8">Apakah Anda Hadir?</h3>
            
            <div class="space-y-4 mb-10">
              <button @click="submitRsvp('going')" :class="['w-full py-5 rounded-2xl flex items-center justify-center gap-3 font-black uppercase tracking-[0.2em] text-[10px] transition-all', rsvpForm.status === 'going' ? 'bg-emerald-600 text-white shadow-xl shadow-emerald-200 scale-[1.02]' : 'bg-gray-50 text-gray-400 hover:bg-gray-100']">
                <Check class="w-4 h-4" />
                Saya Hadir
              </button>
              <button @click="submitRsvp('maybe')" :class="['w-full py-5 rounded-2xl flex items-center justify-center gap-3 font-black uppercase tracking-[0.2em] text-[10px] transition-all', rsvpForm.status === 'maybe' ? 'bg-blue-600 text-white shadow-xl shadow-blue-200 scale-[1.02]' : 'bg-gray-50 text-gray-400 hover:bg-gray-100']">
                <HelpCircle class="w-4 h-4" />
                Mungkin
              </button>
              <button @click="submitRsvp('not_going')" :class="['w-full py-5 rounded-2xl flex items-center justify-center gap-3 font-black uppercase tracking-[0.2em] text-[10px] transition-all', rsvpForm.status === 'not_going' ? 'bg-red-600 text-white shadow-xl shadow-red-200 scale-[1.02]' : 'bg-gray-50 text-gray-400 hover:bg-gray-100']">
                <X class="w-4 h-4" />
                Berhalangan
              </button>
            </div>

            <div class="space-y-8 pt-8 border-t border-gray-50">
              <div class="space-y-4">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Membawa Berapa Orang?</label>
                <div class="flex items-center gap-4 bg-gray-50 p-2 rounded-2xl border border-gray-100">
                  <button @click="rsvpForm.headcount = Math.max(1, rsvpForm.headcount - 1)" class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center font-black text-gray-400 hover:text-emerald-600 transition-colors">-</button>
                  <input v-model="rsvpForm.headcount" type="number" class="flex-1 bg-transparent text-center font-black text-lg text-gray-900 outline-none border-none" />
                  <button @click="rsvpForm.headcount++" class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center font-black text-gray-400 hover:text-emerald-600 transition-colors">+</button>
                </div>
              </div>

              <div class="space-y-4">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Catatan Tambahan</label>
                <textarea v-model="rsvpForm.notes" rows="3" class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent focus:border-emerald-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none resize-none" placeholder="Contoh: Bawa makanan ringan..."></textarea>
              </div>

              <button @click="submitRsvp(rsvpForm.status)" :disabled="rsvpForm.processing" class="w-full py-5 bg-gray-900 text-white rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] hover:bg-black transition-all shadow-xl shadow-gray-200 disabled:opacity-50">
                {{ rsvpForm.processing ? 'Mengirim...' : 'Update RSVP' }}
              </button>
            </div>
          </div>
        </div>
    </div>
  </div>
</template>
