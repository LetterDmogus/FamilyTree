<script setup>
import { ref, watch, computed } from 'vue'
import { useHttp, usePage, Link, router } from '@inertiajs/vue3'
import { 
  Shield, 
  Crown, 
  Mars, 
  Venus, 
  Briefcase, 
  Calendar, 
  MapPin, 
  Heart, 
  Skull, 
  UserPlus, 
  PlusCircle, 
  ArrowUpCircle, 
  PenLine, 
  Trash2, 
  X, 
  Settings,
  Info,
  ExternalLink,
  BookOpen,
  User as UserIcon,
  Home,
  Mail,
  Phone,
  Book,
  Share2
} from 'lucide-vue-next'

const props = defineProps({
  node: { type: Object, required: true },
  rootId: { type: Number, required: true }
})

const emit = defineEmits(['add-relation', 'edit-profile', 'close'])

const details = ref(null)
const activeTab = ref('info')
const http = useHttp()
const page = usePage()

const isOwnAccount = computed(() => {
  return page.props.auth.user?.id === props.node.id
})

const canManage = computed(() => {
  if (!details.value) return false
  return details.value.can?.edit
})

const canDelete = computed(() => {
  if (!details.value) return false
  return details.value.can?.delete
})

const canToggleAdmin = computed(() => {
  if (!details.value) return false
  return details.value.can?.toggle_admin
})

const canSetHead = computed(() => {
  if (!details.value) return false
  return details.value.can?.set_head
})

const masterFields = computed(() => page.props.master?.additionalFields || [])

// Logic to group user additional info based on Master Groups
const groupedAdditionalInfo = computed(() => {
  if (!details.value?.profile?.additional_info || masterFields.value.length === 0) return {}

  const info = details.value.profile.additional_info
  const groups = {}

  // 1. Map existing info to their master config
  Object.entries(info).forEach(([key, value]) => {
    if (!value || key === 'pekerjaan') return // Skip empty or special fields

    const fieldConfig = masterFields.value.find(f => f.name === key)
    const groupName = fieldConfig ? fieldConfig.group_name : 'Lainnya'

    if (!groups[groupName]) groups[groupName] = []
    groups[groupName].push({
      label: key,
      value: value,
      icon: fieldConfig?.icon_key || 'user',
      type: fieldConfig?.input_type || 'text'
    })
  })

  return groups
})

function getFullSocialUrl(sm) {
  if (!sm.prefix || sm.prefix === 'none') return null
  const cleanPrefix = sm.prefix.endsWith('/') ? sm.prefix : sm.prefix + '/'
  const cleanUsername = sm.username.startsWith('/') ? sm.username.substring(1) : sm.username
  return cleanPrefix + cleanUsername
}

const iconsMap = {
  user: UserIcon,
  briefcase: Briefcase,
  home: Home,
  mail: Mail,
  phone: Phone,
  heart: Heart,
  book: Book
}

function formatDate(dateStr) {
  if (!dateStr) return null
  try {
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(dateStr))
  } catch (e) { return dateStr }
}

const age = computed(() => {
  if (!details.value?.profile?.birth_date) return null
  const birth = new Date(details.value.profile.birth_date)
  const isAlive = details.value.profile.is_alive
  const end = isAlive ? new Date() : (details.value.profile.death_date ? new Date(details.value.profile.death_date) : new Date())
  let age = end.getFullYear() - birth.getFullYear()
  const m = end.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && end.getDate() < birth.getDate())) age--
  return age
})

const bannerColor = computed(() => {
  const colors = ['from-emerald-400 to-teal-500', 'from-pink-400 to-rose-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500']
  return colors[props.node.id % colors.length]
})

function fetchDetails() {
  http.get(`/api/users/${props.node.id}/details?from_id=${page.props.auth.user?.id}`, {
    onSuccess: (data) => { details.value = data }
  })
}

function handleToggleAdmin() {
  router.post(`/api/users/${props.node.id}/toggle-admin`, {}, { onSuccess: () => fetchDetails() })
}

function handleToggleFamilyHead() {
  const isHead = details.value.profile?.is_family_head
  const action = isHead ? 'Mencabut status' : 'Menjadikan'
  if (confirm(`${action} Kepala Keluarga untuk ${details.value.full_name}?`)) {
    router.post(`/api/users/${props.node.id}/toggle-head`, {}, { onSuccess: () => fetchDetails() })
  }
}

function deleteMember() {
  if (confirm(`Hapus ${props.node.panggilan} secara permanen?`)) {
    router.delete(`/api/users/${props.node.id}`, { onSuccess: () => emit('close') })
  }
}

watch(() => props.node.id, () => { fetchDetails(); activeTab.value = 'info' }, { immediate: true })
</script>

<template>
  <div class="w-96 border-l bg-white flex flex-col h-full shadow-2xl z-40 overflow-hidden text-gray-900 select-none">
    <!-- Header -->
    <div class="relative h-48 flex-shrink-0">
      <div :class="['h-32 w-full bg-gradient-to-r', bannerColor, !details?.profile?.is_alive ? 'grayscale opacity-70' : '']"></div>
      <button @click="$emit('close')" class="absolute top-4 right-4 p-2.5 bg-black/20 hover:bg-black/40 text-white rounded-full transition-colors"><X class="w-4 h-4 stroke-[3]" /></button>
      <div class="absolute top-20 left-6 flex items-end">
        <div class="p-1 bg-white rounded-full shadow-2xl relative">
          <div :class="['w-24 h-24 rounded-full border-4 border-white flex items-center justify-center text-3xl font-black text-white shadow-inner overflow-hidden bg-gray-200']">
            <img v-if="details?.profile?.profile_photo_path" :src="'/storage/' + details.profile.profile_photo_path" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center" :style="{ backgroundColor: '#cbd5e1' }">
              {{ node.panggilan.substring(0, 2).toUpperCase() }}
            </div>
          </div>
          <div v-if="details?.is_admin" class="absolute -top-1 -right-1 w-8 h-8 bg-emerald-600 border-4 border-white rounded-full flex items-center justify-center text-white"><Shield class="w-3.5 h-3.5 fill-current" /></div>
        </div>
        <div class="mb-2 ml-4">
          <h2 class="text-xl font-black tracking-tight text-gray-900 leading-none drop-shadow-sm">{{ details?.full_name || node.full_name }}</h2>
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1.5 flex items-center gap-1.5">
            <span class="px-2 py-0.5 bg-gray-100 rounded-md">{{ node.panggilan }}</span>
            <span v-if="details?.profile?.is_family_head" class="flex items-center gap-1 text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md">
              <Crown class="w-3 h-3" /> Kepala Keluarga
            </span>
          </p>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="px-6 flex border-b bg-gray-50/50">
      <button v-for="tab in ['info', 'settings']" :key="tab" @click="activeTab = tab"
        :class="['px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2', 
          activeTab === tab ? 'border-emerald-600 text-emerald-600 bg-white' : 'border-transparent text-gray-400 hover:text-gray-600']">
        {{ tab === 'info' ? 'Biodata' : 'Kelola' }}
      </button>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto custom-scrollbar">
      <div v-if="!details" class="p-12 flex flex-col items-center justify-center text-center space-y-4 opacity-30">
        <div class="w-12 h-12 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-[10px] font-black uppercase tracking-widest">Memuat Data...</p>
      </div>

      <div v-else class="p-8 space-y-10 animate-in fade-in slide-in-from-bottom-2 duration-500">
        
        <!-- Tab: Info -->
        <div v-if="activeTab === 'info'" class="space-y-10">
          <!-- Vital Stats -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 p-5 rounded-3xl space-y-1 border border-gray-100/50">
              <div class="flex items-center gap-2 text-gray-400 mb-1">
                <component :is="details.profile.gender === 'F' ? Venus : Mars" class="w-3.5 h-3.5" />
                <span class="text-[9px] font-black uppercase tracking-widest">Gender</span>
              </div>
              <p class="text-sm font-black text-gray-800">{{ details.profile.gender === 'M' ? 'Laki-laki' : 'Perempuan' }}</p>
            </div>
            <div class="bg-gray-50 p-5 rounded-3xl space-y-1 border border-gray-100/50">
              <div class="flex items-center gap-2 text-gray-400 mb-1">
                <component :is="details.profile.is_alive ? Heart : Skull" class="w-3.5 h-3.5" />
                <span class="text-[9px] font-black uppercase tracking-widest">Status</span>
              </div>
              <p class="text-sm font-black text-gray-800">{{ details.profile.is_alive ? (age ? `${age} Tahun` : 'Hidup') : 'Meninggal' }}</p>
            </div>
          </div>

          <!-- Basic Info -->
          <div class="space-y-6">
            <div class="flex items-center gap-2 px-1 text-emerald-600">
              <Info class="w-4 h-4" />
              <span class="text-[10px] font-black uppercase tracking-widest">Informasi Dasar</span>
            </div>
            <div class="space-y-5 px-1">
              <div v-if="details.profile.birth_date" class="flex gap-4">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-500 flex-shrink-0"><Calendar class="w-5 h-5" /></div>
                <div>
                  <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Lahir</label>
                  <p class="text-xs font-bold text-gray-700">{{ formatDate(details.profile.birth_date) }}</p>
                  <p v-if="details.profile.birth_place" class="text-[10px] font-bold text-gray-400 mt-0.5 flex items-center gap-1"><MapPin class="w-3 h-3" /> {{ details.profile.birth_place }}</p>
                </div>
              </div>
              <div v-if="!details.profile.is_alive && details.profile.death_date" class="flex gap-4">
                <div class="w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-500 flex-shrink-0"><Skull class="w-5 h-5" /></div>
                <div>
                  <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Wafat</label>
                  <p class="text-xs font-bold text-gray-700">{{ formatDate(details.profile.death_date) }}</p>
                </div>
              </div>
              <div v-if="details.profile.additional_info?.pekerjaan" class="flex gap-4">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500 flex-shrink-0"><Briefcase class="w-5 h-5" /></div>
                <div>
                  <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Pekerjaan</label>
                  <p class="text-xs font-bold text-gray-700">{{ details.profile.additional_info.pekerjaan }}</p>
                </div>
              </div>
              <div class="flex gap-4">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-500 flex-shrink-0"><Mail class="w-5 h-5" /></div>
                <div>
                  <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Email</label>
                  <p class="text-xs font-bold text-gray-700">{{ details.email }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Dynamic Master Data Sections -->
          <div v-for="(fields, group) in groupedAdditionalInfo" :key="group" class="space-y-6">
            <div class="flex items-center gap-2 px-1 text-gray-900/60">
              <BookOpen class="w-4 h-4" />
              <span class="text-[10px] font-black uppercase tracking-widest">{{ group }}</span>
            </div>
            <div class="grid gap-4">
               <div v-for="field in fields" :key="field.label" class="bg-white border-2 border-gray-50 p-4 rounded-3xl flex items-center gap-4 hover:border-emerald-100 transition-all group">
                  <div class="w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-emerald-50 group-hover:text-emerald-500 transition-all">
                    <component :is="iconsMap[field.icon] || UserIcon" class="w-5 h-5" />
                  </div>
                  <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">{{ field.label }}</label>
                    <p class="text-xs font-bold text-gray-700">{{ field.type === 'date' ? formatDate(field.value) : field.value }}</p>
                  </div>
               </div>
            </div>
          </div>

          <!-- Social Media -->
          <div v-if="details.profile.social_media?.length" class="space-y-6">
            <div class="flex items-center gap-2 px-1 text-pink-600">
              <Share2 class="w-4 h-4" />
              <span class="text-[10px] font-black uppercase tracking-widest">Media Sosial</span>
            </div>
            <div class="flex flex-wrap gap-2 px-1">
              <a v-for="(sm, idx) in details.profile.social_media" :key="idx" :href="getFullSocialUrl(sm)" target="_blank"
                class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-white hover:border-pink-200 hover:text-pink-600 hover:shadow-lg transition-all">
                {{ sm.platform_name }}: {{ sm.username }}
                <ExternalLink class="w-3 h-3 opacity-40" />
              </a>
            </div>
          </div>

          <!-- Direct Relations (Line Details) -->
          <div class="space-y-6 pt-4 border-t border-dashed">
            <div class="flex items-center gap-2 px-1 text-emerald-600">
              <Share2 class="w-4 h-4 rotate-90" />
              <span class="text-[10px] font-black uppercase tracking-widest">Garis Keturunan Langsung</span>
            </div>
            
            <div class="grid gap-3 px-1">
              <!-- Spouse(s) -->
              <div v-if="node.spouse?.length" class="flex flex-col gap-2">
                <p class="text-[8px] font-black text-gray-400 uppercase tracking-[0.2em] ml-1">Pasangan</p>
                <div v-for="s in node.spouse" :key="s.id" @click="$emit('node-click', s)" 
                  class="flex items-center gap-3 p-3 bg-rose-50/50 rounded-2xl border border-rose-100 hover:bg-rose-100 transition-all cursor-pointer">
                  <div class="w-8 h-8 rounded-full bg-white border-2 border-rose-200 flex items-center justify-center overflow-hidden">
                    <img v-if="s.photo_url" :src="s.photo_url" class="w-full h-full object-cover" />
                    <span v-else class="text-[10px] font-black text-rose-300">{{ s.full_name.charAt(0) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-black text-rose-900 truncate uppercase">{{ s.full_name }}</p>
                    <p class="text-[8px] font-bold text-rose-400 uppercase tracking-tighter">Pasangan Terdaftar</p>
                  </div>
                </div>
              </div>

              <!-- Children -->
              <div v-if="node.children?.length" class="flex flex-col gap-2">
                <p class="text-[8px] font-black text-gray-400 uppercase tracking-[0.2em] ml-1">Keturunan (Anak)</p>
                <div v-for="c in node.children" :key="c.id" @click="$emit('node-click', c)" 
                  class="flex items-center gap-3 p-3 bg-emerald-50/50 rounded-2xl border border-emerald-100 hover:bg-emerald-100 transition-all cursor-pointer">
                  <div class="w-8 h-8 rounded-full bg-white border-2 border-emerald-200 flex items-center justify-center overflow-hidden">
                    <img v-if="c.photo_url" :src="c.photo_url" class="w-full h-full object-cover" />
                    <span v-else class="text-[10px] font-black text-emerald-300">{{ c.full_name.charAt(0) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-black text-emerald-900 truncate uppercase">{{ c.full_name }}</p>
                    <p class="text-[8px] font-bold text-emerald-400 uppercase tracking-tighter">Generasi Berikutnya</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab: Settings -->
        <div v-if="activeTab === 'settings'" class="space-y-8 pb-12">
          
          <!-- Hierarchical Actions -->
          <div v-if="canManage" class="space-y-4 animate-in slide-in-from-top-4 duration-500">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] px-1">Koneksi Silsilah</h3>
            <div class="grid gap-3">
              <button @click="$emit('add-relation', { node, type: 'parent' })" class="w-full p-5 bg-white border-2 border-gray-50 rounded-[2rem] flex items-center justify-between hover:border-emerald-600 hover:shadow-xl transition-all group">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform"><ArrowUpCircle class="w-5 h-5" /></div>
                  <div class="text-left">
                    <p class="text-xs font-black text-gray-900 uppercase">Tambah Orang Tua</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Ayah atau Ibu</p>
                  </div>
                </div>
                <PlusCircle class="w-4 h-4 text-gray-200 group-hover:text-emerald-600 transition-colors" />
              </button>

              <button @click="$emit('add-relation', { node, type: 'spouse' })" class="w-full p-5 bg-white border-2 border-gray-50 rounded-[2rem] flex items-center justify-between hover:border-rose-500 hover:shadow-xl transition-all group">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform"><Heart class="w-5 h-5" /></div>
                  <div class="text-left">
                    <p class="text-xs font-black text-gray-900 uppercase">Tambah Pasangan</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Suami atau Istri</p>
                  </div>
                </div>
                <PlusCircle class="w-4 h-4 text-gray-200 group-hover:text-rose-500 transition-colors" />
              </button>

              <button @click="$emit('add-relation', { node, type: 'child' })" class="w-full p-5 bg-white border-2 border-gray-50 rounded-[2rem] flex items-center justify-between hover:border-emerald-500 hover:shadow-xl transition-all group">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform"><UserPlus class="w-5 h-5" /></div>
                  <div class="text-left">
                    <p class="text-xs font-black text-gray-900 uppercase">Tambah Anak</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Putra atau Putri</p>
                  </div>
                </div>
                <PlusCircle class="w-4 h-4 text-gray-200 group-hover:text-emerald-500 transition-colors" />
              </button>
            </div>
          </div>

          <!-- Administrative Controls -->
          <div class="space-y-4 pt-4 border-t border-dashed">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] px-1">Kontrol Keanggotaan</h3>
            <div class="grid gap-3">
              <button @click="$emit('edit-profile', details)" v-if="canManage" class="w-full py-4 bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg flex items-center justify-center gap-3">
                <PenLine class="w-4 h-4" /> Edit Profil Anggota
              </button>
              
              <button v-if="canToggleAdmin && !isOwnAccount && !details.is_admin" @click="handleToggleAdmin" :class="['w-full py-3 rounded-2xl text-[10px] font-black uppercase transition-all shadow-lg', details.is_admin ? 'bg-white text-red-600 border-2 border-red-50 hover:bg-red-50' : 'bg-emerald-600 text-white hover:bg-emerald-700']">
                {{ details.is_admin ? 'Cabut Akses Admin' : 'Jadikan Admin' }}
              </button>

              <button v-if="canSetHead" @click="handleToggleFamilyHead" :class="['w-full py-3 rounded-2xl text-[10px] font-black uppercase transition-all shadow-lg', details.profile?.is_family_head ? 'bg-white text-amber-600 border-2 border-amber-50 hover:bg-amber-50' : 'bg-amber-500 text-white hover:bg-amber-600']">
                <Crown class="w-4 h-4 mr-2 inline-block" />
                {{ details.profile?.is_family_head ? 'Cabut Kepala Keluarga' : 'Jadikan Kepala Keluarga' }}
              </button>

              <button v-if="canDelete" @click="deleteMember" class="w-full py-3 bg-white text-gray-400 hover:text-red-600 border-2 border-transparent hover:border-red-100 rounded-2xl text-[10px] font-black uppercase transition-all flex items-center justify-center gap-3">
                <Trash2 class="w-4 h-4" /> Hapus dari Silsilah
              </button>
            </div>
          </div>

          <div v-if="!canManage && !canDelete && !canToggleAdmin" class="py-12 text-center opacity-30 grayscale">
            <Shield class="w-12 h-12 mx-auto mb-4" />
            <p class="text-[10px] font-black uppercase tracking-widest leading-loose">Anda tidak memiliki izin <br/> untuk mengelola anggota ini.</p>
          </div>
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
