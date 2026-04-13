<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  socialMedias: Array,
  additionalFields: Array
})

const activeTab = ref('sosmed')

// Social Media Form
const smForm = useForm({
  name: '',
  prefix: '@',
  icon_url: ''
})

// Additional Field Form
const fieldForm = useForm({
  name: '',
  icon_key: 'user',
  input_type: 'text',
  options: []
})

function submitSM() {
  smForm.post(route('master-data.social-media.store'), {
    onSuccess: () => smForm.reset()
  })
}

function submitField() {
  fieldForm.post(route('master-data.additional-fields.store'), {
    onSuccess: () => fieldForm.reset()
  })
}

function deleteSM(id) {
  if (confirm('Hapus platform ini?')) {
    smForm.delete(route('master-data.social-media.destroy', id))
  }
}

function deleteField(id) {
  if (confirm('Hapus bidang data ini?')) {
    fieldForm.delete(route('master-data.additional-fields.destroy', id))
  }
}

const icons = [
  { key: 'user', path: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { key: 'briefcase', path: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745V21a2 2 0 002 2h14a2 2 0 002-2v-7.745z M16 5V3a2 2 0 00-2-2h-4a2 2 0 00-2 2v2H4a2 2 0 00-2 2v1h10a4 4 0 008 0v-1a2 2 0 00-2-2h-4z' },
  { key: 'home', path: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { key: 'mail', path: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
  { key: 'phone', path: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
  { key: 'heart', path: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
  { key: 'book', path: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' }
]
</script>

<template>
  <Head title="Manajemen Data Master" />

  <div class="min-h-screen bg-[#f8fafc] p-12 select-none">
    <div class="max-w-5xl mx-auto">
      <div class="flex justify-between items-center mb-12">
        <div>
          <h1 class="text-4xl font-black text-gray-900 tracking-tighter">Pengaturan Data</h1>
          <p class="text-gray-400 font-bold uppercase text-[10px] tracking-widest mt-2">Kustomisasi Platform & Bidang Profil</p>
        </div>
        <Link href="/tree" class="px-6 py-3 bg-white border-2 border-gray-100 rounded-2xl font-black uppercase text-[10px] hover:border-gray-900 transition-all shadow-sm">Kembali ke Pohon</Link>
      </div>

      <!-- Main Tabs -->
      <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden flex flex-col min-h-[60vh]">
        <div class="flex border-b border-gray-100 px-8 bg-gray-50/50">
          <button @click="activeTab = 'sosmed'" :class="['px-8 py-6 text-xs font-black uppercase tracking-widest border-b-2 transition-all', activeTab === 'sosmed' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']">
            Platform Sosial Media
          </button>
          <button @click="activeTab = 'fields'" :class="['px-8 py-6 text-xs font-black uppercase tracking-widest border-b-2 transition-all', activeTab === 'fields' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']">
            Bidang Data Profil
          </button>
        </div>

        <div class="flex-1 p-12">
          <!-- SOSIAL MEDIA TAB -->
          <div v-if="activeTab === 'sosmed'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
              <!-- Form -->
              <div class="space-y-6">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tighter mb-6">Tambah Platform</h3>
                <form @submit.prevent="submitSM" class="space-y-4">
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nama Platform</label>
                    <input v-model="smForm.name" type="text" class="w-full px-4 py-3 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-xl font-bold outline-none transition-all shadow-inner" placeholder="Instagram" required />
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Icon URL (SVG/PNG)</label>
                    <input v-model="smForm.icon_url" type="text" class="w-full px-4 py-3 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-xl font-bold outline-none transition-all shadow-inner" placeholder="https://..." />
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Prefix (Opsional)</label>
                    <input v-model="smForm.prefix" type="text" class="w-full px-4 py-3 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-xl font-bold outline-none transition-all shadow-inner" placeholder="@ atau wa.me/" />
                  </div>
                  <button type="submit" :disabled="smForm.processing" class="w-full py-4 bg-gray-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-gray-200">Simpan Platform</button>
                </form>
              </div>

              <!-- List -->
              <div class="lg:col-span-2">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tighter mb-6">Daftar Aktif</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div v-for="sm in socialMedias" :key="sm.id" class="group p-4 bg-white border-2 border-gray-50 rounded-2xl flex items-center justify-between hover:border-gray-200 transition-all">
                    <div class="flex items-center gap-4">
                      <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center p-2 overflow-hidden border border-gray-100">
                        <img v-if="sm.icon_url" :src="sm.icon_url" class="w-full h-full object-contain" />
                        <span v-else class="font-black text-blue-600 text-xs">{{ sm.name.charAt(0) }}</span>
                      </div>
                      <div>
                        <p class="text-sm font-black text-gray-800">{{ sm.name }}</p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase">{{ sm.prefix || 'None' }}</p>
                      </div>
                    </div>
                    <button @click="deleteSM(sm.id)" class="p-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-all">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FIELDS TAB -->
          <div v-if="activeTab === 'fields'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
              <!-- Form -->
              <div class="space-y-6">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tighter mb-6">Tambah Bidang</h3>
                <form @submit.prevent="submitField" class="space-y-4">
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nama Bidang</label>
                    <input v-model="fieldForm.name" type="text" class="w-full px-4 py-3 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-xl font-bold outline-none transition-all shadow-inner" placeholder="Pekerjaan" required />
                  </div>
                  
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tipe Input</label>
                    <select v-model="fieldForm.input_type" class="w-full px-4 py-3 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-xl font-bold outline-none appearance-none transition-all shadow-inner">
                      <option value="text">1 Baris (Text)</option>
                      <option value="textarea">Kotak Teks (Textarea)</option>
                      <option value="date">Tanggal</option>
                      <option value="select">Pilihan (Select)</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Pilih Ikon</label>
                    <div class="grid grid-cols-4 gap-2">
                      <button v-for="icon in icons" :key="icon.key" type="button" @click="fieldForm.icon_key = icon.key" :class="['p-3 rounded-xl border-2 transition-all flex items-center justify-center', fieldForm.icon_key === icon.key ? 'border-blue-600 bg-blue-50 text-blue-600 shadow-md' : 'border-gray-50 text-gray-400']">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                          <path :d="icon.path" />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <button type="submit" :disabled="fieldForm.processing" class="w-full py-4 bg-gray-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-emerald-600 transition-all shadow-xl shadow-gray-200">Simpan Bidang</button>
                </form>
              </div>

              <!-- List -->
              <div class="lg:col-span-2">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tighter mb-6">Bidang Terdaftar</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div v-for="field in additionalFields" :key="field.id" class="group p-5 bg-white border-2 border-gray-50 rounded-3xl flex items-center justify-between hover:border-gray-200 transition-all">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 bg-gray-50 text-gray-900 rounded-2xl flex items-center justify-center border border-gray-100">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                          <path :d="icons.find(i => i.key === field.icon_key)?.path || icons[0].path" />
                        </svg>
                      </div>
                      <div>
                        <p class="text-sm font-black text-gray-800">{{ field.name }}</p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ field.input_type }}</p>
                      </div>
                    </div>
                    <button @click="deleteField(field.id)" class="p-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-all">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
