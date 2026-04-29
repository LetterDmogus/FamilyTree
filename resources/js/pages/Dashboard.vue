<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { 
  ChevronLeft, 
  ChevronRight, 
  Cake, 
  Skull, 
  UserPlus, 
  Heart,
  Users,
  Calendar as CalendarIcon,
  ArrowRight
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps({
  stats: Object,
  events: Array,
  oldestMember: Object
})

defineOptions({
  layout: {
    breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
  },
})

// --- CALENDAR LOGIC ---
const currentDate = ref(new Date())
const currentMonth = computed(() => currentDate.value.getMonth())
const currentYear = computed(() => currentDate.value.getFullYear())

const monthName = computed(() => {
  return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(currentDate.value)
})

const daysInMonth = computed(() => {
  const days = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1).getDay()
  const totalDays = new Date(currentYear.value, currentMonth.value + 1, 0).getDate()

  // Padding for empty days at start of week (Sunday = 0)
  for (let i = 0; i < (firstDay === 0 ? 6 : firstDay - 1); i++) {
    days.push({ day: null, date: null })
  }

  for (let d = 1; d <= totalDays; d++) {
    const fullDate = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    // Filter events for this specific day (matching day & month regardless of year for anniversaries)
    const dayEvents = props.events.filter(e => e.day === d && e.month === (currentMonth.value + 1))
    
    days.push({ 
      day: d, 
      date: fullDate,
      isToday: new Date().toDateString() === new Date(currentYear.value, currentMonth.value, d).toDateString(),
      events: dayEvents
    })
  }

  return days
})

function prevMonth() {
 currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1) 
}
function nextMonth() {
 currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1) 
}

const welcomeMessage = computed(() => {
  const hour = new Date().getHours()

  if (hour < 12) {
return 'Selamat Pagi'
}

  if (hour < 17) {
return 'Selamat Siang'
}

  return 'Selamat Malam'
})
</script>

<template>
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8 font-sans select-none overflow-y-auto custom-scrollbar bg-slate-50/50">
    <Head title="Dashboard" />
    
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ welcomeMessage }}!</h1>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Ikhtisar Silsilah & Acara Mendatang</p>
      </div>
      <Link href="/tree" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold text-xs tracking-tight hover:bg-emerald-700 transition-all shadow-lg flex items-center gap-2 group">
        Buka Diagram Silsilah
        <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
      </Link>
    </div>

    <!-- Quick Stats Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-32 border-b-4 border-b-blue-500">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Anggota</p>
        <div class="flex items-end justify-between">
          <span class="text-3xl font-black text-gray-900">{{ stats.total }}</span>
          <Users class="w-5 h-5 text-blue-500 opacity-20" />
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-32 border-b-4 border-b-emerald-500">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Masih Hidup</p>
        <div class="flex items-end justify-between">
          <span class="text-3xl font-black text-emerald-600">{{ stats.alive }}</span>
          <Heart class="w-5 h-5 text-emerald-500 fill-current opacity-20" />
        </div>
      </div>

      <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-32 border-b-4 border-b-slate-400">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mengenang</p>
        <div class="flex items-end justify-between">
          <span class="text-3xl font-black text-slate-400">{{ stats.deceased }}</span>
          <Skull class="w-5 h-5 text-slate-400 opacity-20" />
        </div>
      </div>

      <div v-if="oldestMember" class="bg-emerald-600 p-5 rounded-xl shadow-lg flex flex-col justify-between h-32 text-white">
        <p class="text-[10px] font-bold text-emerald-200 uppercase tracking-wider">Living Legend</p>
        <div>
          <p class="text-sm font-black truncate leading-tight">{{ oldestMember.full_name }}</p>
          <p class="text-[10px] font-bold text-emerald-300 mt-0.5 uppercase">{{ new Date().getFullYear() - new Date(oldestMember.birth_date).getFullYear() }} Tahun</p>
        </div>
      </div>
    </div>

    <!-- Main Content Area: Calendar & Event Feed -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      
      <!-- CALENDAR WIDGET (2/3 width) -->
      <div class="xl:col-span-2 space-y-4">
        <div class="flex items-center justify-between px-2">
          <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
            <CalendarIcon class="w-4 h-4 text-emerald-600" />
            Kalender Keluarga
          </h3>
          <div class="flex items-center gap-2 bg-white border border-gray-100 rounded-lg p-1">
            <button @click="prevMonth" class="p-1 hover:bg-gray-50 rounded-md transition-colors text-gray-400"><ChevronLeft class="w-4 h-4" /></button>
            <span class="text-[10px] font-black uppercase px-2 w-32 text-center text-gray-700 tracking-tighter">{{ monthName }}</span>
            <button @click="nextMonth" class="p-1 hover:bg-gray-50 rounded-md transition-colors text-gray-400"><ChevronRight class="w-4 h-4" /></button>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 overflow-hidden">
          <!-- Calendar Grid Header -->
          <div class="grid grid-cols-7 gap-px mb-2">
            <div v-for="d in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="d" class="text-center py-2 text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ d }}</div>
          </div>
          
          <!-- Calendar Days -->
          <div class="grid grid-cols-7 gap-px bg-gray-100 border border-gray-50">
            <div v-for="(day, idx) in daysInMonth" :key="idx" 
              :class="['min-h-[90px] p-2 bg-white flex flex-col gap-1 transition-all', day.date ? 'hover:bg-emerald-50/30' : 'bg-gray-50/50']">
              <span v-if="day.day" :class="['text-xs font-black w-6 h-6 flex items-center justify-center rounded-md', day.isToday ? 'bg-emerald-600 text-white shadow-md' : 'text-gray-400']">{{ day.day }}</span>
              
              <!-- Event Markers -->
              <div v-if="day.events?.length" class="flex flex-col gap-1 mt-1">
                <div v-for="(event, eIdx) in day.events" :key="eIdx" 
                  :class="['px-1.5 py-0.5 rounded text-[8px] font-bold truncate flex items-center gap-1 border', 
                    event.type === 'birth' ? 'bg-pink-50 text-pink-600 border-pink-100' : 
                    event.type === 'death' ? 'bg-slate-50 text-slate-500 border-slate-200' : 
                    'bg-emerald-50 text-emerald-600 border-emerald-100']"
                  :title="event.label"
                >
                  <Cake v-if="event.type === 'birth'" class="w-2.5 h-2.5" />
                  <Skull v-else-if="event.type === 'death'" class="w-2.5 h-2.5" />
                  <UserPlus v-else class="w-2.5 h-2.5" />
                  {{ event.user.split(' ')[0] }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SIDE FEED: LEGEND & SUMMARY -->
      <div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-6">
          <h3 class="text-xs font-black text-gray-900 uppercase tracking-widest border-b pb-3">Keterangan Kalender</h3>
          
          <div class="space-y-4">
            <div class="flex items-center gap-4 group">
              <div class="w-10 h-10 rounded-lg bg-pink-50 flex items-center justify-center text-pink-600 border border-pink-100 group-hover:scale-110 transition-transform"><Cake class="w-5 h-5" /></div>
              <div><p class="text-xs font-black text-gray-800 leading-none">Ulang Tahun</p><p class="text-[9px] font-bold text-gray-400 mt-1 uppercase">Peringatan hari kelahiran anggota.</p></div>
            </div>
            
            <div class="flex items-center gap-4 group">
              <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 border border-slate-200 group-hover:scale-110 transition-transform"><Skull class="w-5 h-5" /></div>
              <div><p class="text-xs font-black text-gray-800 leading-none">Mengenang</p><p class="text-[9px] font-bold text-gray-400 mt-1 uppercase">Peringatan hari berpulangnya anggota.</p></div>
            </div>

            <div class="flex items-center gap-4 group">
              <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-200 group-hover:scale-110 transition-transform"><UserPlus class="w-5 h-5" /></div>
              <div><p class="text-xs font-black text-gray-800 leading-none">Anggota Baru</p><p class="text-[9px] font-bold text-gray-400 mt-1 uppercase">Tanggal saat anggota baru didaftarkan.</p></div>
            </div>
          </div>

          <div class="pt-6 border-t">
             <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em] mb-3">Highlight Bulan Ini</p>
                <div v-if="events.filter(e => e.month === (currentMonth + 1)).length" class="space-y-2">
                   <p class="text-xs font-bold text-emerald-900 leading-relaxed">Terdapat <span class="text-emerald-600">{{ events.filter(e => e.month === (currentMonth + 1)).length }} acara</span> keluarga yang tercatat di kalender.</p>
                </div>
                <p v-else class="text-xs italic text-gray-400 font-medium">Tidak ada acara bulan ini.</p>
             </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<style scoped>
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
