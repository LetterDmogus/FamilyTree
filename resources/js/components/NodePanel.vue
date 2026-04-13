<script setup>
import { ref, watch, computed } from 'vue'
import { useHttp, usePage, Link, router } from '@inertiajs/vue3'
import masterData from '@/routes/master-data'

const props = defineProps({
  node: {
    type: Object,
    required: true
  },
  rootId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['add-relation', 'edit-profile', 'close'])

const details = ref(null)
const activeTab = ref('info')
const http = useHttp()
const page = usePage()

const isOwnAccount = computed(() => {
  return page.props.auth.user?.id === props.node.id
})

const isFamilyHead = computed(() => {
  return page.props.auth.user?.profile?.is_family_head === true
})

// Helper to format date
function formatDate(dateStr) {
  if (!dateStr) return null
  try {
    const date = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(date)
  } catch (e) {
    return dateStr
  }
}

const displayBirthInfo = computed(() => {
  const place = details.value?.profile?.birth_place || 'Tidak diketahui'
  const date = formatDate(details.value?.profile?.birth_date) || 'Tidak diketahui'
  return `${place}, ${date}`
})

// Generate random pastel banner color
const bannerColor = computed(() => {
  const colors = [
    'from-blue-400 to-indigo-500',
    'from-pink-400 to-rose-500',
    'from-emerald-400 to-teal-500',
    'from-amber-400 to-orange-500',
    'from-violet-400 to-purple-500',
    'from-cyan-400 to-blue-500'
  ]
  return colors[props.node.id % colors.length]
})

function fetchDetails() {
  const viewerId = page.props.auth.user?.id
  http.get(`/api/users/${props.node.id}/details?from_id=${viewerId}`, {
    onSuccess: (data) => {
      details.value = data
    }
  })
}

function deleteMember() {
  if (confirm(`Apakah Anda yakin ingin menghapus ${props.node.panggilan} secara permanen dari silsilah?`)) {
    router.delete(`/api/users/${props.node.id}`, {
      onSuccess: () => emit('close')
    })
  }
}

watch(() => props.node.id, () => {
  fetchDetails()
  activeTab.value = 'info'
}, { immediate: true })
</script>

<template>
  <div class="w-96 border-l bg-white flex flex-col h-full shadow-2xl z-20 overflow-hidden text-gray-900 select-none">
    <!-- Header with Banner & Profile Photo -->
    <div class="relative h-48 flex-shrink-0">
      <div :class="['h-32 w-full bg-gradient-to-r', bannerColor]"></div>
      <button 
        @click="$emit('close')" 
        class="absolute top-4 right-4 p-2 bg-black/20 hover:bg-black/40 text-white rounded-full transition-colors backdrop-blur-sm"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <!-- Overlapping Profile Photo -->
      <div class="absolute top-20 left-6 flex items-end">
        <div class="p-1 bg-white rounded-full shadow-2xl">
          <img 
            :src="details?.profile?.photo_url || 'https://ui-avatars.com/api/?name=' + node.panggilan" 
            class="w-28 h-28 rounded-full border-4 border-white object-cover"
            alt="Profile"
          />
        </div>
      </div>
    </div>

    <!-- Identity & Tabs -->
    <div class="mt-4 px-6 flex-shrink-0">
      <div class="flex flex-col">
        <h2 class="text-2xl font-black text-gray-900 leading-none">{{ node.panggilan }}</h2>
        <p class="text-sm text-gray-400 font-medium mt-1 uppercase tracking-tighter">{{ node.full_name }}</p>
      </div>

      <div class="flex border-b border-gray-100 mt-6">
        <button 
          @click="activeTab = 'info'"
          :class="['px-4 py-2 text-xs font-black uppercase tracking-widest transition-all border-b-2', activeTab === 'info' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']"
        >
          Informasi
        </button>
        <button 
          @click="activeTab = 'manage'"
          :class="['px-4 py-2 text-xs font-black uppercase tracking-widest transition-all border-b-2', activeTab === 'manage' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']"
        >
          Kelola
        </button>
      </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
      <div v-if="http.processing" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>

      <div v-else-if="details">
        <!-- Tab: Informasi -->
        <div v-if="activeTab === 'info'" class="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
          <!-- Wajib Section -->
          <section>
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Data Penting</h3>
            <div class="grid gap-4">
              <div class="flex items-start gap-3">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <div>
                  <p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Nama Lengkap</p>
                  <p class="text-sm font-bold text-gray-800">{{ details.full_name || 'Tidak diketahui' }}</p>
                </div>
              </div>
              
              <div class="flex items-start gap-3">
                <div class="p-2 bg-pink-50 text-pink-600 rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div>
                  <p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Tempat & Tanggal Lahir</p>
                  <p class="text-sm font-bold text-gray-800">{{ displayBirthInfo }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                </div>
                <div>
                  <p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Jenis Kelamin</p>
                  <p class="text-sm font-bold text-gray-800">
                    {{ details.profile?.gender === 'M' ? 'Laki-laki' : (details.profile?.gender === 'F' ? 'Perempuan' : 'Tidak diketahui') }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-start gap-3">
                <div class="p-2 bg-violet-50 text-violet-600 rounded-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <p class="text-[8px] text-gray-400 uppercase font-black tracking-widest">Status</p>
                  <p class="text-sm font-black text-violet-600 uppercase">{{ details.relation_label }}</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Dynamic Section: Additional Fields -->
          <section v-if="details.profile?.additional_info && Object.keys(details.profile.additional_info).length > 0">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Informasi Tambahan</h3>
            <div class="grid gap-4">
              <div v-for="(value, key) in details.profile.additional_info" :key="key" class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                <span class="text-[8px] text-gray-400 font-black uppercase tracking-widest block mb-1">{{ key }}</span>
                <span class="text-sm font-bold text-gray-800 leading-relaxed whitespace-pre-wrap">{{ value || 'Tidak diketahui' }}</span>
              </div>
            </div>
          </section>

          <!-- Dynamic Section: Social Media -->
          <section v-if="details.profile?.social_media && details.profile?.social_media.length > 0">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Media Sosial</h3>
            <div class="flex flex-wrap gap-3">
              <div v-for="(sm, idx) in details.profile.social_media" :key="idx" class="flex items-center gap-3 px-4 py-2 bg-white rounded-2xl border border-gray-100 hover:border-blue-500 transition-all cursor-pointer shadow-sm group">
                <div class="w-5 h-5 flex-shrink-0">
                   <img :src="`https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/${sm.platform_name.toLowerCase().replace(/ /g, '').replace(/\(twitter\)/g, '')}.svg`" class="w-full h-full opacity-50 group-hover:opacity-100 transition-opacity" />
                </div>
                <div class="flex flex-col">
                  <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">{{ sm.platform_name }}</span>
                  <span class="text-xs text-gray-900 font-bold">@{{ sm.username }}</span>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- Tab: Kelola -->
        <div v-if="activeTab === 'manage'" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
          <div class="space-y-4">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Tindakan</h3>
            
            <!-- Add Actions (Only own account) -->
            <template v-if="isOwnAccount">
              <button 
                @click="$emit('add-relation', 'child')"
                class="group w-full flex items-center justify-between p-4 bg-white border-2 border-blue-50 rounded-2xl hover:border-blue-500 hover:shadow-lg transition-all"
              >
                <div class="flex items-center gap-4">
                  <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                  </div>
                  <div class="text-left">
                    <p class="font-bold text-gray-800">Tambah Anak</p>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Tambahkan keturunan baru</p>
                  </div>
                </div>
              </button>

              <button 
                @click="$emit('add-relation', 'spouse')"
                class="group w-full flex items-center justify-between p-4 bg-white border-2 border-pink-50 rounded-2xl hover:border-pink-500 hover:shadow-lg transition-all"
              >
                <div class="flex items-center gap-4">
                  <div class="p-3 bg-pink-50 text-pink-600 rounded-xl group-hover:bg-pink-600 group-hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                  </div>
                  <div class="text-left">
                    <p class="font-bold text-gray-800">Tambah Pasangan</p>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Hubungkan suami atau istri</p>
                  </div>
                </div>
              </button>
            </template>

            <!-- Edit (Only own account) -->
            <button 
              v-if="isOwnAccount"
              @click="$emit('edit-profile', details)"
              class="group w-full flex items-center justify-between p-4 bg-white border-2 border-gray-50 rounded-2xl hover:border-blue-500 hover:shadow-lg transition-all"
            >
              <div class="flex items-center gap-4">
                <div class="p-3 bg-gray-50 text-gray-600 group-hover:bg-blue-600 group-hover:text-white rounded-xl transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </div>
                <div class="text-left">
                  <p class="font-bold text-gray-800">Perbarui Profil</p>
                  <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Ubah data & modular info</p>
                </div>
              </div>
            </button>

            <!-- Master Data (Only family head) -->
            <Link 
              v-if="isFamilyHead"
              :href="masterData.index().url"
              class="group w-full flex items-center justify-between p-4 bg-white border-2 border-gray-50 rounded-2xl hover:border-emerald-500 hover:shadow-lg transition-all mt-8"
            >
              <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white rounded-xl transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div class="text-left">
                  <p class="font-bold text-gray-800">Kelola Master Data</p>
                  <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Atur platform medsos & bidang</p>
                </div>
              </div>
            </Link>

            <!-- Delete Member (Only family head, and not themselves) -->
            <button 
              v-if="isFamilyHead && !isOwnAccount"
              @click="deleteMember"
              class="group w-full flex items-center justify-between p-4 bg-white border-2 border-red-50 rounded-2xl hover:border-red-500 hover:shadow-lg transition-all"
            >
              <div class="flex items-center gap-4">
                <div class="p-3 bg-red-50 text-red-600 rounded-xl group-hover:bg-red-600 group-hover:text-white transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </div>
                <div class="text-left">
                  <p class="font-bold text-gray-800">Hapus Anggota</p>
                  <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Hapus permanen dari silsilah</p>
                </div>
              </div>
            </button>
          </div>
          
          <div v-if="!isOwnAccount && !isFamilyHead" class="p-8 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic leading-loose">Anda tidak memiliki izin untuk mengelola profil ini.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #f1f1f1;
  border-radius: 10px;
}
</style>
