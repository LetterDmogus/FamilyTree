<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Share2 } from 'lucide-vue-next'

const props = defineProps({
  mode: {
    type: String,
    default: 'create' // 'create' | 'edit'
  },
  member: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    default: 'child'
  },
  master: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

const activeTab = ref('utama')
const photoPreview = ref(null)
const fieldSearch = ref('')

// Helper to format date for input[type=date]
function formatForInput(dateStr) {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    return date.toISOString().split('T')[0]
  } catch (e) {
    return ''
  }
}

const form = useForm({
  user_id: props.member.id,
  type: props.type,
  full_name: '',
  email: '',
  gender: 'M',
  is_alive: true,
  death_date: '',
  birth_date: '',
  birth_place: '',
  profile_photo: null,
  additional_info: {}, 
  social_media: [],
})

onMounted(() => {
  if (props.mode === 'edit') {
    // Standardize data mapping regardless of source (Inertia prop or JSON API)
    const profile = props.member.profile || props.member
    
    form.full_name = props.member.full_name || props.member.name || ''
    form.email = props.member.email || ''
    form.gender = profile.gender || 'M'
    form.is_alive = profile.is_alive ?? true
    form.death_date = formatForInput(profile.death_date)
    form.birth_date = formatForInput(profile.birth_date)
    form.birth_place = profile.birth_place || ''
    form.additional_info = { ...(profile.additional_info || {}) }
    form.social_media = profile.social_media || []
    photoPreview.value = profile.photo_url || profile.profile_photo_path ? `/storage/${profile.profile_photo_path}` : null
  }
})

// Icons mapping for master data fields
const icons = {
  user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  briefcase: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745V21a2 2 0 002 2h14a2 2 0 002-2v-7.745z M16 5V3a2 2 0 00-2-2h-4a2 2 0 00-2 2v2H4a2 2 0 00-2 2v1h10a4 4 0 008 0v-1a2 2 0 00-2-2h-4z',
  home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  mail: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  phone: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
  heart: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
  book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
}

const filteredMasterFields = computed(() => {
  if (!fieldSearch.value) return props.master.additionalFields
  return props.master.additionalFields.filter(f => 
    f.name.toLowerCase().includes(fieldSearch.value.toLowerCase())
  )
})

function addAdditionalField(field) {
  // Use spread to ensure Vue reactivity triggers
  form.additional_info = {
    ...form.additional_info,
    [field.name]: ''
  }
  fieldSearch.value = ''
}

function removeAdditionalField(fieldName) {
  const newInfo = { ...form.additional_info }
  delete newInfo[fieldName]
  form.additional_info = newInfo
}

function addSocialMedia() {
  const defaultPlatform = props.master.socialMedias[0]?.name || 'Instagram'
  form.social_media.push({ platform_name: defaultPlatform, username: '' })
}

function removeSocialMedia(index) {
  form.social_media.splice(index, 1)
}

function handlePhotoChange(e) {
  const file = e.target.files[0]
  if (file) {
    form.profile_photo = file
    const reader = new FileReader()
    reader.onload = (e) => photoPreview.value = e.target.result
    reader.readAsDataURL(file)
  }
}

function submit() {
  const url = props.mode === 'create' 
    ? `/api/relations`
    : `/api/users/${props.member.id}/update`
    
  form.post(url, {
    onSuccess: () => emit('close'),
  })
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md animate-in fade-in duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col h-[85vh]">
      
      <!-- Sticky Header -->
      <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
        <div>
          <h2 class="text-2xl font-black text-gray-900 tracking-tight">
            {{ mode === 'create' ? 'Tambah Anggota' : 'Edit Anggota' }}
          </h2>
          <div class="flex items-center gap-2 mt-1">
            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase rounded-md border border-emerald-100">
              {{ mode === 'create' ? type : 'PROFIL' }}
            </span>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">
              {{ member.panggilan }}
            </p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-3 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-2xl transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Tab Strip -->
      <div class="flex px-8 bg-gray-50/50 border-b border-gray-100 flex-shrink-0">
        <button v-for="tab in ['utama', 'tambahan', 'sosmed']" :key="tab" @click="activeTab = tab" 
          :class="['px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2', activeTab === tab ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-400 hover:text-gray-600']">
          {{ tab }}
        </button>
      </div>

      <!-- Scrollable Body -->
      <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
        <form @submit.prevent="submit" class="p-8 pb-12">
          
          <!-- TAB 1: UTAMA -->
          <div v-if="activeTab === 'utama'" class="space-y-10 animate-in slide-in-from-bottom-4 duration-500">
            <div class="flex items-center gap-8 bg-gray-50 p-6 rounded-[2rem] border-2 border-dashed border-gray-200">
              <div class="relative flex-shrink-0">
                <div class="w-24 h-24 rounded-3xl shadow-2xl overflow-hidden bg-white flex items-center justify-center border-4 border-white">
                  <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                  <div v-else class="text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>
                <label class="absolute -bottom-2 -right-2 p-2 bg-emerald-600 text-white rounded-xl shadow-xl cursor-pointer hover:bg-emerald-700 transition-all hover:scale-110">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                  </svg>
                  <input type="file" @change="handlePhotoChange" class="hidden" accept="image/*" />
                </label>
              </div>
              <div>
                <h4 class="text-sm font-black text-gray-900">Foto Profil</h4>
                <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase leading-relaxed">Identitas visual dalam silsilah.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 text-left">
              <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nama Lengkap</label>
                <input v-model="form.full_name" type="text" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" required />
              </div>

              <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Alamat Email (Untuk Login)</label>
                <input v-model="form.email" type="email" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" placeholder="email@contoh.com" :required="mode === 'create'" />
              </div>

              <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Jenis Kelamin</label>
                <div class="flex gap-2 p-1 bg-gray-50 rounded-2xl">
                  <button type="button" @click="form.gender = 'M'" :class="['flex-1 py-3 text-xs font-black uppercase rounded-xl transition-all', form.gender === 'M' ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-400']">Laki-laki</button>
                  <button type="button" @click="form.gender = 'F'" :class="['flex-1 py-3 text-xs font-black uppercase rounded-xl transition-all', form.gender === 'F' ? 'bg-white shadow-sm text-pink-600' : 'text-gray-400']">Perempuan</button>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Status Hidup</label>
                <div class="flex gap-2 p-1 bg-gray-50 rounded-2xl">
                  <button type="button" @click="form.is_alive = true" :class="['flex-1 py-3 text-xs font-black uppercase rounded-xl transition-all', form.is_alive ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-400']">Hidup</button>
                  <button type="button" @click="form.is_alive = false" :class="['flex-1 py-3 text-xs font-black uppercase rounded-xl transition-all', !form.is_alive ? 'bg-white shadow-sm text-slate-800' : 'text-gray-400']">Meninggal</button>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tempat Lahir</label>
                <input v-model="form.birth_place" type="text" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" required />
              </div>

              <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tanggal Lahir</label>
                <input v-model="form.birth_date" type="date" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" required />
              </div>

              <div v-if="!form.is_alive" class="md:col-span-2 animate-in slide-in-from-top-2">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tanggal Wafat</label>
                <input v-model="form.death_date" type="date" class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-red-500 focus:bg-white rounded-2xl transition-all font-bold text-gray-800 outline-none" />
              </div>
            </div>
          </div>

          <!-- TAB 2: TAMBAHAN -->
          <div v-if="activeTab === 'tambahan'" class="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
            <!-- Search-Select Field Builder -->
            <div v-if="master.settings?.allow_custom_metadata !== false" class="relative">
              <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-xl border-2 border-gray-100">
                <div class="pl-4 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input v-model="fieldSearch" type="text" class="flex-1 bg-transparent border-none outline-none text-[10px] font-black uppercase text-gray-900 placeholder-gray-400 tracking-widest py-2" placeholder="Cari & Tambah Bidang Data..." />
              </div>
              
              <!-- Results dropdown -->
              <div v-if="fieldSearch" class="absolute top-full mt-2 w-full bg-white rounded-2xl shadow-2xl border border-gray-100 z-30 p-2 max-h-48 overflow-y-auto">
                <button v-for="field in filteredMasterFields" :key="field.id" type="button" @click="addAdditionalField(field)" class="w-full px-4 py-3 text-left text-[10px] font-black uppercase text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 rounded-xl transition-all">
                  {{ field.name }} ({{ field.input_type }})
                </button>
                <div v-if="filteredMasterFields.length === 0" class="p-4 text-center text-[10px] font-black text-gray-300 uppercase">Tidak ditemukan</div>
              </div>
            </div>

            <!-- Active Fields List -->
            <div class="grid gap-4">
              <div v-for="(value, fieldName) in form.additional_info" :key="fieldName" class="group bg-gray-50 p-5 rounded-3xl border-2 border-transparent hover:border-gray-200 transition-all text-left">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                      <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                        <path :d="icons[master.additionalFields.find(f => f.name === fieldName)?.icon_key] || icons.user" />
                      </svg>
                    </div>
                    <label class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">{{ fieldName }}</label>
                  </div>
                  <button type="button" @click="removeAdditionalField(fieldName)" class="text-red-400 hover:text-red-600 p-1 opacity-0 group-hover:opacity-100 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
                
                <div class="mt-2 bg-white rounded-xl p-3 border border-gray-100 shadow-sm">
                  <textarea v-if="master.additionalFields.find(f => f.name === fieldName)?.input_type === 'textarea'" 
                    v-model="form.additional_info[fieldName]" 
                    class="w-full bg-transparent font-bold text-sm text-gray-800 outline-none border-none resize-none min-h-[80px]" 
                    placeholder="Masukkan data..."></textarea>
                  
                  <input v-else-if="master.additionalFields.find(f => f.name === fieldName)?.input_type === 'date'" 
                    v-model="form.additional_info[fieldName]" 
                    type="date" 
                    class="w-full bg-transparent font-bold text-sm text-gray-800 outline-none border-none" />
                  
                  <select v-else-if="master.additionalFields.find(f => f.name === fieldName)?.input_type === 'select'" 
                    v-model="form.additional_info[fieldName]" 
                    class="w-full bg-transparent font-bold text-sm text-gray-800 outline-none border-none appearance-none">
                    <option value="">Pilih Opsi</option>
                    <option v-for="opt in (master.additionalFields.find(f => f.name === fieldName)?.options || [])" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                  
                  <input v-else 
                    v-model="form.additional_info[fieldName]" 
                    type="text" 
                    class="w-full bg-transparent font-bold text-sm text-gray-800 outline-none border-none" 
                    placeholder="Masukkan data..." />
                </div>
              </div>
              
              <div v-if="Object.keys(form.additional_info).length === 0" class="py-16 text-center border-2 border-dashed border-gray-100 rounded-[2rem]">
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest italic leading-loose px-12">Gunakan bar pencarian di atas untuk menambah bidang profil.</p>
              </div>
            </div>
          </div>

          <!-- TAB 3: SOSIAL MEDIA -->
          <div v-if="activeTab === 'sosmed'" class="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Hubungkan Platform</h3>
              <button type="button" @click="addSocialMedia" class="px-4 py-2 bg-gray-900 text-white text-[10px] font-black uppercase rounded-xl hover:bg-emerald-600 transition-all flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Media Sosial
              </button>
            </div>

            <div class="grid gap-4">
              <div v-for="(sm, index) in form.social_media" :key="index" class="group flex items-center gap-4 bg-gray-50 p-4 rounded-3xl border-2 border-transparent hover:border-gray-200 transition-all">
                <div class="flex flex-col gap-2 w-full">
                  <div class="flex items-center justify-between">
                    <select v-model="sm.platform_name" class="bg-white px-4 py-2 rounded-2xl text-[10px] font-black uppercase border-none outline-none shadow-sm text-gray-600">
                      <option v-for="p in master.socialMedias" :key="p.id" :value="p.name">{{ p.name }}</option>
                    </select>
                    <button type="button" @click="removeSocialMedia(index)" class="text-red-400 hover:text-red-600 p-2">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <div class="flex items-center bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <span class="px-4 py-3 bg-gray-50 text-gray-400 font-bold text-xs border-r border-gray-100 flex-shrink-0">
                      {{ master.socialMedias.find(p => p.name === sm.platform_name)?.prefix !== 'none' ? master.socialMedias.find(p => p.name === sm.platform_name)?.prefix : '' }}
                    </span>
                    <input v-model="sm.username" type="text" class="flex-1 px-4 py-3 bg-transparent font-bold text-sm text-gray-800 outline-none border-none" placeholder="username" />
                  </div>
                </div>
              </div>

              <div v-if="form.social_media.length === 0" class="py-16 text-center border-2 border-dashed border-gray-100 rounded-[2rem]">
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest italic">Belum ada akun media sosial terhubung.</p>
              </div>
            </div>
          </div>

        </form>
      </div>

      <!-- Sticky Footer -->
      <div class="p-8 border-t bg-white flex gap-4 flex-shrink-0">
        <button type="button" @click="$emit('close')" class="flex-1 px-6 py-5 border-2 border-gray-100 text-gray-400 rounded-3xl font-black uppercase text-[10px] hover:bg-gray-50 hover:text-gray-600 transition-all tracking-widest">
          Batal
        </button>
        <button @click="submit" :disabled="form.processing" class="flex-[2] px-6 py-5 bg-gray-900 text-white rounded-3xl font-black uppercase text-[10px] hover:bg-emerald-600 transition-all tracking-widest disabled:opacity-50 shadow-xl shadow-gray-200">
          {{ form.processing ? 'Sedang Menyimpan...' : (mode === 'create' ? 'Simpan Anggota Baru' : 'Simpan Perubahan') }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #f1f1f1;
  border-radius: 100px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #e5e7eb;
}
</style>
