<script setup lang="ts">
import { useHttp, usePage } from '@inertiajs/vue3'
import { 
  Shield, 
  Crown, 
  Mars, 
  Venus, 
  Calendar, 
  MapPin, 
  Heart, 
  Skull, 
  Mail,
  Share2,
  ExternalLink,
  ArrowLeft,
  FileText,
  Eye,
  User as UserIcon,
  ChevronRight,
  BrainCircuit,
  Maximize2,
  AlertTriangle,
  Download,
  Table as TableIcon,
  Sparkles,
  CheckCircle2,
  UserPlus,
  Loader2
} from 'lucide-vue-next'
import { ref, watch, onMounted, computed } from 'vue'
import { Map, MapMarker } from '@/components/ui/map'

import LetterDetailModal from './FamilyTree/LetterDetailModal.vue'
import MemberModal from './MemberModal.vue'

const props = defineProps<{
  userId: number
}>()

defineEmits(['close', 'node-click'])

const details = ref<any>(null)
const selectedLetter = ref<any>(null)
const isLetterDetailOpen = ref(false)
const http = useHttp()
const page = usePage()

// --- TAB STATE ---
const activeProfileTab = ref('detail')

// --- EXTRACTION STATE ---
const isExtracting = ref(false)
const extractionResults = ref<any[]>([])
const showExtractionModal = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const memberModal = ref({ open: false, mode: 'create', type: 'child', data: null })

function triggerExtraction() {
  fileInput.value?.click()
}

function handleFileChange(e: any) {
  const file = e.target.files[0]

  if (!file) {
return
}

  isExtracting.value = true
  http.post(`/api/users/${props.userId}/extract-kk`, {
    file: file
  }, {
    onSuccess: (data) => {
      extractionResults.value = data
      showExtractionModal.value = true
    },
    onFinish: () => {
      isExtracting.value = false

      if (fileInput.value) {
fileInput.value.value = ''
}
    }
  })
}

function importExtractedMember(person: any) {
  // Convert extraction person data to the format MemberModal expects
  const memberData = {
    id: props.userId, // The parent/relative ID for creating relation
    panggilan: details.value.full_name,
    full_name: person.full_name,
    email: person.email,
    gender: person.gender,
    birth_date: person.birth_date,
    birth_place: person.birth_place,
    additional_info: {
        'NIK': person.nik,
        'Pekerjaan': person.occupation
    }
  }

  memberModal.value = {
    open: true,
    mode: 'create',
    type: person.relation_type === 'self' ? 'child' : person.relation_type,
    data: memberData
  }
}

const needsPhotoUpdate = computed(() => {
  if (!details.value?.profile?.profile_photo_updated_at) {
return false
}
  
  const updatedAt = new Date(details.value.profile.profile_photo_updated_at)
  const fiveYearsAgo = new Date()
  fiveYearsAgo.setFullYear(fiveYearsAgo.getFullYear() - 5)
  
  return updatedAt < fiveYearsAgo
})

function openLetter(letter: any) {
  selectedLetter.value = letter
  isLetterDetailOpen.value = true
  
  // Mark as read if I am the recipient
  if (!letter.read_at && letter.recipient_id === page.props.auth.user.id) {
    http.post(`/api/letters/${letter.id}/read`, {}, {
        onSuccess: () => {
            handleLetterRead(letter.id)
        }
    })
  }
}

function handleLetterRead(letterId: number) {
  const letter = details.value?.letters?.find((l: any) => l.id === letterId)

  if (letter) {
    letter.read_at = new Date().toISOString()
  }

  if (selectedLetter.value && selectedLetter.value.id === letterId) {
    selectedLetter.value.read_at = new Date().toISOString()
  }
}

function fetchDetails() {
  http.get(`/api/users/${props.userId}/details`, {
    onSuccess: (data) => {
      details.value = data
    }
  })
}

function formatDate(dateStr: string) {
  if (!dateStr) {
return null
}

  try {
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

const age = computed(() => {
  if (!details.value?.profile?.birth_date) {
return null
}

  const birth = new Date(details.value.profile.birth_date)
  const isAlive = details.value.profile.is_alive
  const end = isAlive ? new Date() : (details.value.profile.death_date ? new Date(details.value.profile.death_date) : new Date())
  let age = end.getFullYear() - birth.getFullYear()
  const m = end.getMonth() - birth.getMonth()

  if (m < 0 || (m === 0 && end.getDate() < birth.getDate())) {
age--
}

  return age
})

const getMapCenter = (place: any): [number, number] => {
    if (place && typeof place === 'object' && place.lng && place.lat) {
        return [Number(place.lng), Number(place.lat)]
    }

    return [106.8272, -6.1751]
}

onMounted(fetchDetails)
watch(() => props.userId, fetchDetails)
</script>

<template>
  <div v-if="!details" class="h-full flex flex-col items-center justify-center p-12 text-center space-y-4">
    <div class="w-16 h-16 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
    <p class="text-xs font-black uppercase tracking-[0.3em] text-emerald-600 animate-pulse">Menyusun Artikel Keluarga...</p>
  </div>

  <div v-else class="min-h-full bg-white selection:bg-emerald-100">
    <!-- Article Header -->
    <header class="relative h-[60vh] min-h-[400px] overflow-hidden bg-gray-50">
      <!-- Background Effect -->
      <div 
        class="absolute inset-0 opacity-20 blur-3xl scale-110"
        :style="{ 
            background: `radial-gradient(circle at 20% 30%, #10b981 0%, transparent 70%), radial-gradient(circle at 80% 70%, #3b82f6 0%, transparent 70%)` 
        }"
      ></div>
      
      <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>

      <!-- Content Overlay -->
      <div class="absolute inset-0 flex flex-col justify-end px-12 pb-20 max-w-7xl mx-auto w-full">
        <div class="flex flex-col md:flex-row items-end gap-10">
          <!-- Main Photo -->
          <div class="relative group flex-shrink-0">
            <div class="w-64 h-80 rounded-[3rem] overflow-hidden border-8 border-white shadow-2xl bg-gray-100 rotate-[-2deg] group-hover:rotate-0 transition-transform duration-700">
              <img v-if="details.profile?.profile_photo_path" :src="'/storage/' + details.profile.profile_photo_path" class="w-full h-full object-cover scale-110 group-hover:scale-100 transition-transform duration-700" />
              <div v-else class="w-full h-full flex items-center justify-center bg-emerald-50 text-emerald-200">
                <UserIcon class="w-24 h-24" />
              </div>
            </div>
            <div v-if="details.is_admin" class="absolute -top-4 -right-4 w-16 h-16 bg-emerald-600 border-4 border-white rounded-[2rem] flex items-center justify-center text-white shadow-xl">
              <Shield class="w-8 h-8 fill-current" />
            </div>
          </div>

          <!-- Title & Stats -->
          <div class="flex-1 pb-4">
            <!-- 5 Year Alert -->
            <div v-if="needsPhotoUpdate" class="mb-6 flex items-center gap-4 p-4 bg-amber-50 border border-amber-200 rounded-3xl animate-in slide-in-from-left duration-500">
               <div class="w-12 h-12 bg-amber-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-amber-200">
                 <AlertTriangle class="w-6 h-6" />
               </div>
               <div>
                 <p class="text-xs font-black text-amber-700 uppercase tracking-widest">Pembaruan Foto Diperlukan</p>
                 <p class="text-[10px] font-bold text-amber-600/80 uppercase tracking-wider mt-0.5">Foto profil ini sudah lebih dari 5 tahun. Mohon perbarui untuk keakuratan data.</p>
               </div>
            </div>

            <div class="flex items-center gap-4 mb-4">
               <span class="px-4 py-1.5 bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg">Biodata Lengkap</span>
               <span v-if="details.profile?.is_family_head" class="px-4 py-1.5 bg-amber-500 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg flex items-center gap-2">
                 <Crown class="w-3 h-3" /> Kepala Keluarga
               </span>
            </div>
            <h1 class="text-7xl font-black text-slate-900 tracking-tighter leading-none mb-6">{{ details.full_name }}</h1>
            
            <div class="flex flex-wrap gap-8">
               <div class="flex items-center gap-3">
                 <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center shadow-sm', details.profile.gender === 'F' ? 'bg-pink-100 text-pink-500' : 'bg-blue-100 text-blue-500']">
                    <component :is="details.profile.gender === 'F' ? Venus : Mars" class="w-5 h-5" />
                 </div>
                 <div>
                   <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Gender</p>
                   <p class="text-sm font-bold text-slate-700">{{ details.profile.gender === 'M' ? 'Laki-laki' : 'Perempuan' }}</p>
                 </div>
               </div>
               <div class="flex items-center gap-3">
                 <div class="w-10 h-10 rounded-2xl bg-rose-100 flex items-center justify-center text-rose-500 shadow-sm"><Heart class="w-5 h-5" /></div>
                 <div>
                   <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Umur / Status</p>
                   <p class="text-sm font-bold text-slate-700">{{ details.profile.is_alive ? (age ? `${age} Tahun` : 'Hidup') : 'Meninggal' }}</p>
                 </div>
               </div>
               <div v-if="details.profile.birth_date" class="flex items-center gap-3">
                 <div class="w-10 h-10 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-500 shadow-sm"><Calendar class="w-5 h-5" /></div>
                 <div>
                   <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Tanggal Lahir</p>
                   <p class="text-sm font-bold text-slate-700">{{ formatDate(details.profile.birth_date) }}</p>
                 </div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Article Content -->
    <main class="max-w-7xl mx-auto px-12 py-10 grid grid-cols-1 lg:grid-cols-3 gap-20">
      <!-- Left Column: Biography & Details -->
      <div class="lg:col-span-2 space-y-12">
        
        <!-- Tab Navigation -->
        <div class="flex items-center gap-2 p-1.5 bg-gray-50 rounded-2xl border border-gray-100 mb-4 sticky top-4 z-20 backdrop-blur shadow-sm">
           <button v-for="t in [
             { id: 'detail', label: 'Detail Profil', icon: UserIcon, color: 'text-emerald-600', bg: 'bg-emerald-50' },
             { id: 'file', label: 'Berkas & Arsip', icon: FileText, color: 'text-blue-600', bg: 'bg-blue-50' },
             { id: 'pesan', label: 'Pesan Keluarga', icon: Mail, color: 'text-amber-600', bg: 'bg-amber-50' }
           ]" :key="t.id"
             @click="activeProfileTab = t.id"
             :class="['flex-1 flex items-center justify-center gap-3 py-3 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
               activeProfileTab === t.id ? 'bg-white shadow-sm ' + t.color : 'text-gray-400 hover:text-gray-600']"
           >
             <component :is="t.icon" class="w-4 h-4" />
             <span class="hidden md:inline">{{ t.label }}</span>
           </button>
        </div>

        <!-- TAB CONTENT: DETAIL -->
        <div v-if="activeProfileTab === 'detail'" class="space-y-20 animate-in slide-in-from-bottom-4 duration-500">
          <!-- Story / About Section -->
          <section class="prose prose-slate max-w-none">
            <div class="flex items-center gap-4 mb-10">
              <div class="h-px flex-1 bg-gray-100"></div>
              <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">Tentang Anggota</h2>
              <div class="h-px w-20 bg-emerald-600"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
               <div class="space-y-6">
                  <h3 class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.2em] text-emerald-600">
                    <MapPin class="w-4 h-4" /> Informasi Kelahiran
                  </h3>
                  <div class="bg-emerald-50/30 p-8 rounded-[2.5rem] border border-emerald-100/50 space-y-4">
                     <p class="text-sm leading-relaxed text-gray-600">
                       <span class="font-black text-slate-900">{{ details.full_name }}</span> dilahirkan pada tanggal 
                       <span class="font-bold">{{ formatDate(details.profile.birth_date) }}</span>. 
                     </p>
                     <div v-if="details.profile.birth_place" class="pt-4 border-t border-emerald-200">
                        <p class="text-[10px] font-black text-emerald-600/40 uppercase tracking-widest mb-2">Lokasi Lahir</p>
                        <p class="font-bold text-slate-800">{{ details.profile.birth_place.address || details.profile.birth_place.city || details.profile.birth_place.country }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ details.profile.birth_place.city }}, {{ details.profile.birth_place.province }}</p>
                     </div>
                  </div>
               </div>

               <div v-if="!details.profile.is_alive" class="space-y-6">
                  <h3 class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.2em] text-slate-600">
                    <Skull class="w-4 h-4" /> Informasi Wafat
                  </h3>
                  <div class="bg-slate-100/50 p-8 rounded-[2.5rem] border border-slate-200 space-y-4">
                     <p class="text-sm leading-relaxed text-gray-600">
                       Dinyatakan wafat pada <span class="font-bold">{{ formatDate(details.profile.death_date) }}</span>. 
                     </p>
                     <div v-if="details.profile.death_place" class="pt-4 border-t border-slate-300">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Lokasi Terakhir</p>
                        <p class="font-bold text-slate-800">{{ details.profile.death_place.address || details.profile.death_place.city || details.profile.death_place.country }}</p>
                     </div>
                  </div>
               </div>
            </div>
          </section>

          <!-- Location Maps Section -->
          <section class="space-y-10">
            <div class="flex items-center gap-4">
              <h2 class="text-2xl font-black text-slate-900 tracking-tight">Geolokasi Penting</h2>
              <div class="h-px flex-1 bg-gray-100"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 h-[400px]">
               <div class="relative rounded-[3rem] overflow-hidden border-2 border-gray-100 shadow-sm flex flex-col">
                  <div class="h-12 bg-white px-6 flex items-center border-b font-black text-[10px] uppercase tracking-widest text-emerald-600">Peta Kelahiran</div>
                  <div class="flex-1 bg-slate-50 relative">
                     <Map v-if="details.profile.birth_place?.lat" :center="getMapCenter(details.profile.birth_place)" :zoom="14">
                        <MapMarker :lng-lat="getMapCenter(details.profile.birth_place)" />
                     </Map>
                     <div v-else class="absolute inset-0 flex items-center justify-center text-[9px] font-black text-gray-300 uppercase tracking-widest text-center px-10">Koordinat kelahiran tidak tersedia</div>
                  </div>
               </div>
               <div class="relative rounded-[3rem] overflow-hidden border-2 border-gray-100 shadow-sm flex flex-col">
                  <div class="h-12 bg-white px-6 flex items-center border-b font-black text-[10px] uppercase tracking-widest text-slate-500">Peta Wafat</div>
                  <div class="flex-1 bg-slate-50 relative">
                     <Map v-if="details.profile.death_place?.lat" :center="getMapCenter(details.profile.death_place)" :zoom="14">
                        <MapMarker :lng-lat="getMapCenter(details.profile.death_place)" />
                     </Map>
                     <div v-else class="absolute inset-0 flex items-center justify-center text-[9px] font-black text-gray-300 uppercase tracking-widest text-center px-10">Koordinat wafat tidak tersedia</div>
                  </div>
               </div>
            </div>
          </section>
        </div>

        <!-- TAB CONTENT: FILE & ARSIP -->
        <div v-if="activeProfileTab === 'file'" class="space-y-20 animate-in slide-in-from-bottom-4 duration-500">
          <!-- Documents Section -->
          <section class="space-y-10">
            <!-- Identity Documents -->
            <div class="space-y-8">
              <div class="flex items-center justify-between">
                <h3 class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.2em] text-blue-600">
                  <FileText class="w-4 h-4" /> Dokumen Identitas
                </h3>
                
                <!-- Hidden File Input -->
                <input type="file" ref="fileInput" class="hidden" accept=".xlsx,.csv,.xls" @change="handleFileChange" />
                
                <div class="flex gap-2">
                  <a href="/api/family-tree/mockup-kk" class="p-2.5 bg-gray-50 text-gray-400 rounded-xl hover:bg-gray-100 hover:text-gray-600 transition-all shadow-sm" title="Download Template Excel">
                    <Download class="w-4 h-4" />
                  </a>
                  <button @click="triggerExtraction" :disabled="isExtracting" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg flex items-center gap-2 disabled:opacity-50">
                    <Loader2 v-if="isExtracting" class="w-3.5 h-3.5 animate-spin" />
                    <TableIcon v-else class="w-3.5 h-3.5" />
                    {{ isExtracting ? 'Mengekstrak...' : 'Ekstrak Excel' }}
                  </button>
                </div>
              </div>

              <div v-if="details.attachments.identity.length === 0" class="p-8 bg-slate-50 border-2 border-dashed border-slate-200 rounded-[2rem] text-center">
                 <Shield class="w-8 h-8 mx-auto mb-4 text-slate-300" />
                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-loose">
                    Dokumen identitas (KK/KTP) hanya dapat <br/> diakses oleh keluarga inti anggota ini.
                 </p>
              </div>

              <div v-else class="grid gap-4">
                <div v-for="type in ['kk', 'ktp']" :key="type" class="flex items-center justify-between p-6 bg-slate-50 border border-slate-100 rounded-[2rem] group hover:bg-white hover:border-blue-200 transition-all">

                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-blue-500 shadow-sm"><FileText class="w-6 h-6" /></div>
                    <div>
                      <p class="text-sm font-black text-slate-900 uppercase">{{ type.toUpperCase() }}</p>
                      <p class="text-[9px] font-bold text-slate-400 uppercase">
                        {{ details.attachments.identity.find(a => a.type === type) ? 'Dokumen Terverifikasi' : 'Belum Tersedia' }}
                      </p>
                    </div>
                  </div>
                  <a v-if="details.attachments.identity.find(a => a.type === type)" 
                    :href="'/storage/' + details.attachments.identity.find(a => a.type === type).file_path" 
                    target="_blank"
                    class="p-3 bg-white text-blue-600 rounded-2xl hover:bg-blue-600 hover:text-white shadow-sm transition-all">
                    <Eye class="w-5 h-5" />
                  </a>
                </div>
              </div>
            </div>
          </section>

          <!-- Gallery / History Section -->
          <section v-if="details.attachments?.history?.length" class="space-y-10">
             <div class="flex items-center gap-4">
              <h2 class="text-2xl font-black text-slate-900 tracking-tight">Arsip Visual</h2>
              <div class="h-px flex-1 bg-gray-100"></div>
            </div>

            <div class="columns-2 md:columns-3 gap-8 space-y-8">
               <div v-for="h in details.attachments.history" :key="h.id" class="break-inside-avoid relative group">
                  <div class="rounded-[2rem] overflow-hidden border-4 border-white shadow-xl bg-gray-50 transform group-hover:scale-[1.02] transition-transform duration-500">
                     <img :src="'/storage/' + h.file_path" class="w-full h-auto object-cover" />
                     <div class="absolute inset-0 bg-emerald-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-6 text-center">
                        <div class="text-white">
                          <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-2">{{ formatDate(h.created_at) }}</p>
                          <a :href="'/storage/' + h.file_path" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-emerald-600 rounded-full font-black text-[9px] uppercase shadow-xl">
                            <Maximize2 class="w-3 h-3" /> Lihat Resolusi Penuh
                          </a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
          </section>
        </div>

        <!-- TAB CONTENT: PESAN -->
        <div v-if="activeProfileTab === 'pesan'" class="space-y-12 animate-in slide-in-from-bottom-4 duration-500">
          <!-- Private Letters -->
          <div class="space-y-8">
            <h3 class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.2em] text-amber-600">
              <Mail class="w-4 h-4" /> Pesan & Surat Keluarga
            </h3>

            <div v-if="!details.letters || details.letters.length === 0" class="h-40 flex items-center justify-center border-2 border-dashed border-gray-100 rounded-[2rem] text-center px-10">
              <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest leading-loose italic">Tidak ada surat keluarga untuk ditampilkan.</p>
            </div>

            <div v-else class="space-y-6">
               <div v-for="letter in details.letters" :key="letter.id" 
                  @click="openLetter(letter)"
                  :class="['p-6 bg-white border-2 rounded-[2rem] shadow-sm relative group transition-all cursor-pointer overflow-hidden', 
                    letter.sender_id === userId ? 'border-amber-50 hover:border-amber-200' : 'border-blue-50 hover:border-blue-200']">

                  <!-- Background Decor -->
                  <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <Mail class="w-24 h-24 rotate-12" />
                  </div>

                  <!-- Status Badge -->
                  <div class="absolute top-6 right-6 flex gap-2">
                     <div v-if="!letter.read_at && letter.recipient_id === $page.props.auth.user.id" class="px-3 py-1 bg-amber-500 text-white text-[8px] font-black uppercase tracking-widest rounded-full animate-pulse">Baru</div>
                     <div :class="['px-3 py-1 text-[8px] font-black uppercase tracking-widest rounded-full', 
                        letter.sender_id === $page.props.auth.user.id ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600']">
                        {{ letter.sender_id === $page.props.auth.user.id ? 'Terkirim' : 'Masuk' }}
                     </div>
                  </div>

                  <div class="flex items-center gap-4">
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center font-black shadow-sm text-xs', 
                       letter.sender_id === $page.props.auth.user.id ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600']">
                      {{ (letter.sender?.profile?.full_name || 'K').charAt(0) }}
                    </div>
                    <div>
                      <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                        {{ letter.sender_id === $page.props.auth.user.id ? 'Ke: ' + (letter.recipient?.profile?.full_name || 'Keluarga') : 'Dari: ' + (letter.sender?.profile?.full_name || 'Keluarga') }}
                      </p>
                      <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight group-hover:text-amber-600 transition-colors">{{ letter.subject }}</h4>
                      <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest mt-1">{{ formatDate(letter.created_at) }}</p>
                    </div>
                  </div>
               </div>
            </div>

          </div>
        </div>

      </div>
      <!-- Right Column: Relationships & Actions -->
      <div class="space-y-12">
        <!-- Relationship Web -->
        <section class="bg-slate-50 p-10 rounded-[3rem] border border-slate-100 space-y-10 sticky top-24">
          <div class="space-y-2">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Koneksi Darah</h2>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">Silsilah & Hubungan</p>
          </div>

          <!-- Parents List -->
          <div v-if="details.relations?.parents?.length" class="space-y-4">
             <h3 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-2">
               <ArrowLeft class="w-3 h-3 rotate-90" /> Orang Tua
             </h3>
             <div class="space-y-3">
               <div v-for="p in details.relations.parents" :key="p.id" @click="$emit('node-click', p)"
                  class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-emerald-50 shadow-sm cursor-pointer hover:border-emerald-500 hover:shadow-md transition-all group">
                  <div class="w-12 h-12 rounded-full bg-emerald-50 border-2 border-white overflow-hidden flex-shrink-0">
                    <img v-if="p.photo_url" :src="p.photo_url" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-emerald-300 font-black">{{ p.full_name.charAt(0) }}</div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-slate-900 uppercase truncate group-hover:text-emerald-600">{{ p.full_name }}</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase">Orang Tua Terdaftar</p>
                  </div>
               </div>
             </div>
          </div>

          <!-- Spouse List -->
          <div v-if="details.relations?.spouses?.length" class="space-y-4">
             <h3 class="text-[10px] font-black text-rose-500 uppercase tracking-widest flex items-center gap-2">
               <Heart class="w-3 h-3" /> Pasangan
             </h3>
             <div class="space-y-3">
               <div v-for="s in details.relations.spouses" :key="s.id" @click="$emit('node-click', s)"
                  class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-rose-50 shadow-sm cursor-pointer hover:border-rose-500 hover:shadow-md transition-all group">
                  <div class="w-12 h-12 rounded-full bg-rose-50 border-2 border-white overflow-hidden flex-shrink-0">
                    <img v-if="s.photo_url" :src="s.photo_url" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-rose-300 font-black">{{ s.full_name.charAt(0) }}</div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-slate-900 uppercase truncate group-hover:text-rose-500">{{ s.full_name }}</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase">Pasangan Terdaftar</p>
                  </div>
               </div>
             </div>
          </div>

          <!-- Children List -->
          <div v-if="details.relations?.children?.length" class="space-y-4">
             <h3 class="text-[10px] font-black text-blue-500 uppercase tracking-widest flex items-center gap-2">
               <ChevronRight class="w-3 h-3 rotate-90" /> Keturunan
             </h3>
             <div class="space-y-3">
               <div v-for="c in details.relations.children" :key="c.id" @click="$emit('node-click', c)"
                  class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-blue-50 shadow-sm cursor-pointer hover:border-blue-500 hover:shadow-md transition-all group">
                  <div class="w-12 h-12 rounded-full bg-blue-50 border-2 border-white overflow-hidden flex-shrink-0">
                    <img v-if="c.photo_url" :src="c.photo_url" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-blue-300 font-black">{{ c.full_name.charAt(0) }}</div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-slate-900 uppercase truncate group-hover:text-blue-600">{{ c.full_name }}</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase">Anak Terdaftar</p>
                  </div>
               </div>
             </div>
          </div>

          <!-- Parents & Children would be added here dynamically from a dedicated relation API -->
          <div class="pt-8 border-t border-dashed border-slate-200">
             <div class="p-6 bg-emerald-600 rounded-3xl text-white shadow-xl relative overflow-hidden">
                <BrainCircuit class="absolute -right-4 -bottom-4 w-24 h-24 opacity-20 rotate-12" />
                <p class="text-[9px] font-black uppercase tracking-[0.3em] opacity-80 mb-2">Insight Keluarga</p>
                <p class="text-sm font-bold leading-relaxed">{{ details.relation_label }}</p>
             </div>
          </div>

          <!-- Social Links -->
          <div v-if="details.profile?.social_media?.length" class="space-y-4 pt-8">
             <h3 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-2">
               <Share2 class="w-3 h-3" /> Media Sosial
             </h3>
             <div class="grid grid-cols-1 gap-2">
                <a v-for="(sm, idx) in details.profile.social_media" :key="idx" href="#" target="_blank"
                   class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl group hover:border-emerald-500 transition-all">
                   <div class="flex items-center gap-3">
                      <ExternalLink class="w-4 h-4 text-slate-300 group-hover:text-emerald-500" />
                      <span class="text-[10px] font-black uppercase tracking-widest text-slate-600">{{ sm.platform_name }}</span>
                   </div>
                   <span class="text-[10px] font-bold text-slate-400">@{{ sm.username }}</span>
                </a>
             </div>
          </div>

          <!-- Administrative Footer -->
          <div class="pt-10">
             <button @click="$emit('close')" class="w-full py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-xl flex items-center justify-center gap-3">
               <ArrowLeft class="w-4 h-4" /> Kembali ke Diagram
             </button>
          </div>
        </section>
      </div>
    </main>

    <LetterDetailModal 
      v-if="selectedLetter"
      :open="isLetterDetailOpen"
      :letter="selectedLetter"
      @close="isLetterDetailOpen = false"
      @read="handleLetterRead"
    />

    <!-- KK Extraction Results Modal -->
    <div v-if="showExtractionModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/90 backdrop-blur-sm animate-in fade-in duration-300">
       <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[80vh]">
          <div class="p-8 border-b flex items-center justify-between">
             <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center">
                   <Sparkles class="w-6 h-6" />
                </div>
                <div>
                   <h2 class="text-xl font-black text-slate-900 tracking-tight">Hasil Ekstraksi KK</h2>
                   <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Ditemukan {{ extractionResults.length }} Anggota Keluarga</p>
                </div>
             </div>
             <button @click="showExtractionModal = false" class="p-3 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-2xl transition-all">
                <X class="w-6 h-6" />
             </button>
          </div>
          
          <div class="flex-1 overflow-y-auto p-8 space-y-4 custom-scrollbar">
             <div v-for="(person, idx) in extractionResults" :key="idx" 
                class="flex items-center justify-between p-5 bg-slate-50 border border-slate-100 rounded-3xl group hover:bg-white hover:border-blue-200 hover:shadow-xl hover:shadow-blue-900/5 transition-all">
                <div class="flex items-center gap-4">
                   <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg', person.gender === 'F' ? 'bg-pink-500 shadow-pink-200' : 'bg-blue-500 shadow-blue-200']">
                      <UserIcon class="w-6 h-6" />
                   </div>
                   <div>
                      <p class="text-sm font-black text-slate-900 uppercase">{{ person.full_name }}</p>
                      <div class="flex items-center gap-2 mt-0.5">
                         <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest">{{ person.relation_type }}</span>
                         <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                         <span class="text-[9px] font-bold text-slate-400 uppercase">{{ person.birth_date }}</span>
                      </div>
                   </div>
                </div>
                <button v-if="person.relation_type !== 'self'" @click="importExtractedMember(person)" class="p-3 bg-white text-blue-600 border border-slate-100 rounded-2xl hover:bg-blue-600 hover:text-white hover:border-blue-600 shadow-sm transition-all group/btn">
                   <UserPlus class="w-5 h-5 group-hover/btn:scale-110 transition-transform" />
                </button>
                <div v-else class="flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-600 rounded-2xl">
                   <CheckCircle2 class="w-4 h-4" />
                   <span class="text-[9px] font-black uppercase">Ini Anda</span>
                </div>
             </div>
          </div>

          <div class="p-8 bg-slate-50 border-t border-slate-100">
             <button @click="showExtractionModal = false" class="w-full py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl">
                Selesai
             </button>
          </div>
       </div>
    </div>

    <MemberModal v-if="memberModal.open" :mode="memberModal.mode" :member="memberModal.data" :type="memberModal.type" :master="page.props.master" @close="memberModal.open = false" />
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 20px; }
</style>
