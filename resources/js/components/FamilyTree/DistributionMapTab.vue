<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3'
import { MapPin, Baby, Skull } from 'lucide-vue-next'
import { ref, onMounted, computed } from 'vue'
import { Map, MapMarker } from '@/components/ui/map'

const emit = defineEmits(['node-click'])

interface Location {
  user_id: number
  full_name: string
  type: 'birth' | 'death' | 'current'
  place: {
    address?: string
    city?: string
    province?: string
    country?: string
    lat: string
    lng: string
  }
}

const locations = ref<Location[]>([])
const isLoading = ref(true)
const filterType = ref<'all' | 'birth' | 'death' | 'current'>('all')
const http = useHttp()

const fetchLocations = () => {
  isLoading.value = true
  http.get('/api/locations', {
    onSuccess: (data: any) => {
      locations.value = data
      isLoading.value = false
    }
  })
}

const filteredLocations = computed(() => {
  if (filterType.value === 'all') {
return locations.value
}

  return locations.value.filter(loc => loc.type === filterType.value)
})

const getMarkerColor = (type: 'birth' | 'death' | 'current') => {
  if (type === 'birth') {
return '#10b981'
}

  if (type === 'death') {
return '#64748b'
}

  return '#3b82f6' // Current
}

const getPopupContent = (loc: Location) => {
  let typeLabel = 'Lokasi'

  if (loc.type === 'birth') {
typeLabel = 'Tempat Lahir'
} else if (loc.type === 'death') {
typeLabel = 'Tempat Wafat'
} else if (loc.type === 'current') {
typeLabel = 'Alamat Saat Ini'
}
  
  const address = loc.place.address || loc.place.city || loc.place.country || 'Lokasi tidak bernama'
  
  return `
    <div class="p-2 min-w-[150px]">
      <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">${typeLabel}</p>
      <p class="text-sm font-black text-slate-900 leading-tight mb-2">${loc.full_name}</p>
      <p class="text-xs text-slate-500 mb-3">${address}</p>
      <button 
        onclick="window.dispatchEvent(new CustomEvent('open-member-tab', { detail: ${loc.user_id} }))"
        class="w-full py-2 bg-slate-900 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-md"
      >
        Buka Profil
      </button>
    </div>
  `
}

onMounted(() => {
  fetchLocations()
  
  // Listen for the custom event from the popup button
  window.addEventListener('open-member-tab', ((e: CustomEvent) => {
    const userId = e.detail
    const member = locations.value.find(l => l.user_id === userId)

    if (member) {
      emit('node-click', { id: member.user_id, full_name: member.full_name })
    }
  }) as EventListener)
})
</script>

<template>
  <div class="h-full w-full relative bg-gray-50 flex flex-col">
    <!-- Overlay Loader -->
    <div v-if="isLoading" class="absolute inset-0 z-[100] bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center space-y-4">
      <div class="w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 animate-pulse">Memetakan Koordinat Keluarga...</p>
    </div>

    <!-- Map Header / Controls -->
    <div class="absolute top-6 left-6 right-6 z-30 pointer-events-none flex justify-between items-start">
      <div class="bg-white/90 backdrop-blur shadow-2xl rounded-[2rem] border border-gray-100 p-6 pointer-events-auto max-w-sm">
        <h2 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-3">
          <MapPin class="w-6 h-6 text-emerald-600" />
          Peta Persebaran
        </h2>
        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Titik Lokasi Bersejarah Keluarga</p>
        
        <div class="mt-6 flex flex-wrap gap-2">
          <button 
            @click="filterType = 'all'"
            :class="['px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all', filterType === 'all' ? 'bg-slate-900 text-white shadow-lg' : 'bg-gray-100 text-gray-400 hover:bg-gray-200']"
          >
            Semua
          </button>
          <button 
            @click="filterType = 'birth'"
            :class="['px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2', filterType === 'birth' ? 'bg-emerald-600 text-white shadow-lg' : 'bg-emerald-50 text-emerald-600/40 hover:bg-emerald-100']"
          >
            <Baby class="w-3 h-3" /> Lahir
          </button>
          <button 
            @click="filterType = 'current'"
            :class="['px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2', filterType === 'current' ? 'bg-blue-600 text-white shadow-lg' : 'bg-blue-50 text-blue-600/40 hover:bg-blue-100']"
          >
            <MapPin class="w-3 h-3" /> Saat Ini
          </button>
          <button 
            @click="filterType = 'death'"
            :class="['px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2', filterType === 'death' ? 'bg-slate-600 text-white shadow-lg' : 'bg-slate-100 text-slate-600/40 hover:bg-slate-200']"
          >
            <Skull class="w-3 h-3" /> Wafat
          </button>
        </div>
      </div>

      <div class="bg-white/90 backdrop-blur shadow-2xl rounded-[2rem] border border-gray-100 p-4 pointer-events-auto flex items-center gap-6">
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-200"></div>
          <span class="text-[9px] font-black uppercase tracking-widest text-slate-600">Lahir</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-200"></div>
          <span class="text-[9px] font-black uppercase tracking-widest text-slate-600">Saat Ini</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 rounded-full bg-slate-500 shadow-sm shadow-slate-200"></div>
          <span class="text-[9px] font-black uppercase tracking-widest text-slate-600">Wafat</span>
        </div>
      </div>
    </div>

    <!-- Fullscreen Map -->
    <div class="flex-1 w-full relative z-10">
      <Map :center="[118.0149, -2.5489]" :zoom="5">
        <MapMarker 
          v-for="(loc, idx) in filteredLocations" 
          :key="`${loc.user_id}-${loc.type}-${idx}`"
          :lng-lat="[Number(loc.place.lng), Number(loc.place.lat)]"
          :color="getMarkerColor(loc.type)"
          :popup="getPopupContent(loc)"
        />
      </Map>
    </div>
  </div>
</template>

<style>
/* Custom styling for Leaflet popups to match UI theme */
.leaflet-popup-content-wrapper {
  border-radius: 1.5rem !important;
  padding: 0 !important;
  box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.15) !important;
  border: 1px solid rgb(241 245 249) !important;
}
.leaflet-popup-content {
  margin: 0 !important;
  padding: 0 !important;
}
.leaflet-popup-tip {
  display: none !important;
}
</style>