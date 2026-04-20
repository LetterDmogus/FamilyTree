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
  Book
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

const isViewerAdmin = computed(() => {
  const user = page.props.auth.user
  return user?.is_admin || user?.roles?.some(r => ['admin', 'superadmin'].includes(r.name))
})

const canManage = computed(() => {
  return isOwnAccount.value || isViewerAdmin.value
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
  const colors = ['from-blue-400 to-indigo-500', 'from-pink-400 to-rose-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500']
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
          <img :src="details?.profile?.photo_url || 'https://ui-avatars.com/api/?name=' + node.panggilan" :class="['w-28 h-28 rounded-full border-4 border-white object-cover', !details?.profile?.is_alive ? 'grayscale' : '']" />
          <div v-if="details?.is_admin" class="absolute -top-1 -right-1 w-8 h-8 bg-indigo-600 border-4 border-white rounded-full flex items-center justify-center text-white"><Shield class="w-3.5 h-3.5 fill-current" /></div>
          <div v-if="details?.profile?.is_family_head" class="absolute -bottom-1 -right-1 w-8 h-8 bg-amber-500 border-4 border-white rounded-full flex items-center justify-center text-white"><Crown class="w-3.5 h-3.5 fill-current" /></div>
        </div>
        <div v-if="!details?.profile?.is_alive" class="ml-2 mb-2 px-3 py-1 bg-slate-800 text-white text-[10px] font-black uppercase rounded-full shadow-lg flex items-center gap-1.5"><Skull class="w-3 h-3 text-slate-400" /> Meninggal</div>
      </div>
    </div>

    <!-- Identity & Tabs -->
    <div class="mt-4 px-6 flex-shrink-0">
      <div class="flex flex-col">
        <div class="flex items-center gap-2">
          <h2 class="text-2xl font-black text-gray-900 leading-none">{{ node.panggilan }}</h2>
          <span v-if="age" class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-black rounded-md">{{ age }} TAHUN</span>
        </div>
        <p class="text-sm text-gray-400 font-medium mt-1 uppercase tracking-tighter">{{ node.full_name }}</p>
      </div>
      <div class="flex border-b border-gray-100 mt-6">
        <button @click="activeTab = 'info'" :class="['px-4 py-2 text-xs font-black uppercase tracking-widest transition-all border-b-2', activeTab === 'info' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']">Informasi</button>
        <button @click="activeTab = 'manage'" :class="['px-4 py-2 text-xs font-black uppercase tracking-widest transition-all border-b-2', activeTab === 'manage' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']">Kelola</button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
      <div v-if="http.processing" class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div></div>
      <div v-else-if="details">
        <div v-if="activeTab === 'info'" class="space-y-10 animate-in fade-in slide-in-from-bottom-2 duration-300">
          
          <!-- Static: Birth & Status -->
          <section>
            <div class="grid gap-4">
              <div class="flex items-start gap-3">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><Calendar class="w-4 h-4 stroke-[2.5]" /></div>
                <div><p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Lahir</p><p class="text-sm font-bold text-gray-800">{{ details.profile?.birth_place }}, {{ formatDate(details.profile?.birth_date) }}</p></div>
              </div>
              <div v-if="!details.profile?.is_alive" class="flex items-start gap-3">
                <div class="p-2 bg-slate-100 text-slate-600 rounded-lg"><Skull class="w-4 h-4" /></div>
                <div><p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Wafat</p><p class="text-sm font-bold text-gray-800">{{ formatDate(details.profile?.death_date) }}</p></div>
              </div>
              <div class="flex items-start gap-3">
                <div :class="['p-2 rounded-lg', details.profile?.gender === 'F' ? 'bg-pink-50 text-pink-600' : 'bg-blue-50 text-blue-600']"><component :is="details.profile?.gender === 'F' ? Venus : Mars" class="w-4 h-4 stroke-[3]" /></div>
                <div><p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Jenis Kelamin</p><p class="text-sm font-bold text-gray-800">{{ details.profile?.gender === 'F' ? 'Perempuan' : 'Laki-laki' }}</p></div>
              </div>
              <div class="flex items-start gap-3">
                <div class="p-2 bg-violet-50 text-violet-600 rounded-lg"><Info class="w-4 h-4 stroke-[2.5]" /></div>
                <div><p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Hubungan</p><p class="text-sm font-black text-violet-600 uppercase">{{ details.relation_label }}</p></div>
              </div>
            </div>
          </section>

          <!-- Grouped Dynamic Content -->
          <template v-for="(fields, groupName) in groupedAdditionalInfo" :key="groupName">
            <section class="space-y-4">
              <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100 pb-2">{{ groupName }}</h3>
              
              <div v-for="field in fields" :key="field.label" class="animate-in fade-in duration-500">
                <!-- Group Style: Story/Textarea -->
                <div v-if="field.type === 'textarea'" class="bg-gray-50 rounded-2xl p-5 border border-gray-100 shadow-inner">
                  <div class="flex items-center gap-2 mb-3 text-indigo-600">
                    <component :is="iconsMap[field.icon] || UserIcon" class="w-3.5 h-3.5" />
                    <span class="text-[9px] font-black uppercase tracking-widest">{{ field.label }}</span>
                  </div>
                  <p class="text-sm font-medium text-gray-700 leading-relaxed whitespace-pre-wrap italic">{{ field.value }}</p>
                </div>
                
                <!-- Group Style: Personal/Short Info -->
                <div v-else class="flex items-center gap-4 bg-white p-3 rounded-2xl border border-gray-50 hover:border-blue-200 transition-all">
                  <div class="p-2 bg-gray-50 text-gray-400 rounded-xl group-hover:text-blue-600 transition-colors">
                    <component :is="iconsMap[field.icon] || UserIcon" class="w-4 h-4" />
                  </div>
                  <div>
                    <p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">{{ field.label }}</p>
                    <p class="text-sm font-bold text-gray-800">{{ field.value }}</p>
                  </div>
                </div>
              </div>
            </section>
          </template>

          <!-- Social Media -->
          <section v-if="details.profile?.social_media && details.profile?.social_media.length > 0">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100 pb-2">Media Sosial</h3>
            <div class="flex flex-wrap gap-3">
              <a v-for="(sm, idx) in details.profile.social_media" :key="idx" :href="getFullSocialUrl(sm)" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 px-4 py-2 bg-white rounded-2xl border border-gray-100 hover:border-blue-500 hover:bg-blue-50/20 transition-all shadow-sm group">
                <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center relative">
                   <img :src="`https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/${sm.platform_name.toLowerCase().replace(/ /g, '').replace(/\(twitter\)/g, '')}.svg`" class="w-4 h-4 opacity-50 group-hover:opacity-100 transition-opacity" />
                </div>
                <div class="flex flex-col">
                  <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">{{ sm.platform_name }}</span>
                  <span class="text-xs text-gray-900 font-bold group-hover:text-blue-700 transition-colors">@{{ sm.username }}</span>
                </div>
              </a>
            </div>
          </section>
        </div>

        <!-- Manage Tab -->
        <div v-if="activeTab === 'manage'" class="space-y-4">
           <!-- (Aksi Admin & Tombol Edit/Hapus tetap sama seperti sebelumnya) -->
           <div v-if="isViewerAdmin && !isOwnAccount" class="bg-indigo-50 border-2 border-indigo-100 p-5 rounded-3xl mb-8 flex flex-col gap-4">
              <div class="flex items-start gap-3">
                <div class="p-2 bg-indigo-600 text-white rounded-xl shadow-lg"><Shield class="w-4 h-4 fill-current" /></div>
                <div><p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest leading-none">Administrator</p><p class="text-[9px] text-indigo-400 font-bold uppercase mt-1 leading-relaxed">Kelola seluruh silsilah secara bebas.</p></div>
              </div>
              <button @click="handleToggleAdmin" :class="['w-full py-3 rounded-2xl text-[10px] font-black uppercase transition-all shadow-lg', details.is_admin ? 'bg-white text-red-600 border-2 border-red-50 hover:bg-red-50' : 'bg-indigo-600 text-white hover:bg-indigo-700']">{{ details.is_admin ? 'Cabut Akses Admin' : 'Jadikan Admin' }}</button>
            </div>
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 px-1">Tindakan</h3>
            <template v-if="canManage">
              <button @click="$emit('add-relation', 'child')" class="group w-full flex items-center justify-between p-4 bg-white border-2 border-blue-50 rounded-2xl hover:border-blue-500 transition-all"><div class="flex items-center gap-4"><div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors"><UserPlus class="w-6 h-6 stroke-[2.5]" /></div><div class="text-left"><p class="font-bold text-gray-800">Tambah Anak</p><p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Tambahkan keturunan baru</p></div></div></button>
              <button @click="$emit('add-relation', 'spouse')" class="group w-full flex items-center justify-between p-4 bg-white border-2 border-pink-50 rounded-2xl hover:border-pink-500 transition-all"><div class="flex items-center gap-4"><div class="p-3 bg-pink-50 text-pink-600 rounded-xl group-hover:bg-pink-600 group-hover:text-white transition-colors"><Heart class="w-6 h-6 stroke-[2.5]" /></div><div class="text-left"><p class="font-bold text-gray-800">Tambah Pasangan</p><p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Hubungkan suami atau istri</p></div></div></button>
              <button @click="$emit('add-relation', 'parent')" class="group w-full flex items-center justify-between p-4 bg-white border-2 border-emerald-50 rounded-2xl hover:border-emerald-500 transition-all"><div class="flex items-center gap-4"><div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors"><ArrowUpCircle class="w-6 h-6 stroke-[2.5]" /></div><div class="text-left"><p class="font-bold text-gray-800">Tambah Orang Tua</p><p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Hubungkan Ayah atau Ibu</p></div></div></button>
              <button @click="$emit('edit-profile', details)" class="group w-full flex items-center justify-between p-4 bg-white border-2 border-gray-50 rounded-2xl hover:border-blue-500 transition-all"><div class="flex items-center gap-4"><div class="p-3 bg-gray-50 text-gray-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors"><PenLine class="w-6 h-6 stroke-[2.5]" /></div><div class="text-left"><p class="font-bold text-gray-800">Perbarui Profil</p><p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Ubah data & modular info</p></div></div></button>
            </template>
            <button v-if="canManage && !details.profile?.is_family_head" @click="deleteMember" class="group w-full flex items-center justify-between p-4 bg-white border-2 border-red-50 rounded-2xl hover:border-red-500 transition-all"><div class="flex items-center gap-4"><div class="p-3 bg-red-50 text-red-600 rounded-xl group-hover:bg-red-600 group-hover:text-white transition-colors"><Trash2 class="w-6 h-6 stroke-[2.5]" /></div><div class="text-left"><p class="font-bold text-gray-800">Hapus Anggota</p><p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Hapus permanen dari silsilah</p></div></div></button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #f1f1f1; border-radius: 10px; }
</style>
