<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CrudTable from '@/components/CrudTable.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { 
    Plus, 
    User, 
    Briefcase, 
    Home, 
    Mail, 
    Phone, 
    Heart, 
    Book, 
    X,
    LayoutList,
    Folder
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import admin from '@/routes/admin';

const props = defineProps<{
    items: {
        data: any[];
        links: any[];
        meta?: any;
    };
    filters: any;
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        access_trash: boolean;
    }
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Manajemen Bidang Data',
                href: admin.dataDetails.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Nama Bidang', key: 'identity' },
    { label: 'Grup', key: 'group_name', sortable: true },
    { label: 'Tipe', key: 'input_type' },
    { label: 'Dibuat', key: 'created_at', sortable: true },
];

const routes = {
    index: admin.dataDetails.index,
    destroy: (id: number) => admin.dataDetails.destroy({ dataDetail: id }),
    restore: (id: number) => admin.dataDetails.restore({ dataDetail: id }),
    forceDestroy: (id: number) => admin.dataDetails.forceDestroy({ dataDetail: id }),
};

const icons = [
    { key: 'user', component: User },
    { key: 'briefcase', component: Briefcase },
    { key: 'home', component: Home },
    { key: 'mail', component: Mail },
    { key: 'phone', component: Phone },
    { key: 'heart', component: Heart },
    { key: 'book', component: Book },
];

const showModal = ref(false);
const editingItem = ref<any>(null);
const newOption = ref('');

const form = useForm({
    name: '',
    group_name: 'Lainnya',
    icon_key: 'user',
    input_type: 'text' as 'text' | 'textarea' | 'date' | 'select',
    options: {
        placeholder: '',
        template: '',
        choices: [] as string[],
        allow_custom: false,
        date_format: 'd MMMM yyyy'
    },
});

const availableGroups = computed(() => {
    const groups = new Set(props.items.data.map(item => item.group_name));
    groups.add('Data Personal');
    groups.add('Cerita & Kenangan');
    groups.add('Pendidikan');
    groups.add('Pekerjaan');
    return Array.from(groups).sort();
});

function addChoice() {
    const val = newOption.value.trim();
    if (val && !form.options.choices.includes(val)) {
        form.options.choices = [...form.options.choices, val];
        newOption.value = '';
    }
}

function removeChoice(opt: string) {
    form.options.choices = form.options.choices.filter(o => o !== opt);
}

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    showModal.value = true;
};

const handleEdit = (item: any) => {
    editingItem.value = item;
    form.clearErrors();
    form.name = item.name;
    form.group_name = item.group_name || 'Lainnya';
    form.icon_key = item.icon_key || 'user';
    form.input_type = item.input_type;
    
    // Merge existing options with defaults to prevent errors
    const savedOptions = item.options || {};
    form.options = {
        placeholder: savedOptions.placeholder || '',
        template: savedOptions.template || '',
        choices: Array.isArray(savedOptions.choices) ? savedOptions.choices : (Array.isArray(item.options) ? item.options : []),
        allow_custom: savedOptions.allow_custom || false,
        date_format: savedOptions.date_format || 'd MMMM yyyy'
    };
    
    showModal.value = true;
};

const submit = () => {
    const options = {
        onSuccess: () => (showModal.value = false),
    };
    if (editingItem.value) {
        const routeDef = admin.dataDetails.update({ dataDetail: editingItem.value.id });
        form.put(typeof routeDef === 'string' ? routeDef : routeDef.url, options);
    } else {
        const routeDef = admin.dataDetails.store();
        form.post(typeof routeDef === 'string' ? routeDef : routeDef.url, options);
    }
};
</script>

<template>
    <Head title="Manajemen Bidang Data" />

    <div class="flex h-screen flex-col font-sans select-none bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between px-8 py-6 border-b bg-white/80 backdrop-blur-md sticky top-0 z-20">
            <div>
                <h1 class="text-2xl font-black tracking-tighter text-gray-900">Manajemen Bidang Data</h1>
                <p class="text-xs font-bold text-gray-400 mt-0.5 uppercase tracking-widest">Kustomisasi Informasi Tambahan Profil Anggota</p>
            </div>
            <div class="flex items-center gap-4">
                <Button v-if="!showModal && can.create" @click="openCreateModal" class="bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl h-12 px-8 hover:bg-emerald-600 transition-all">
                    <Plus class="mr-2 h-4 w-4 stroke-[3]" />
                    Tambah Bidang Baru
                </Button>
                <Button v-if="showModal" @click="showModal = false" variant="outline" class="rounded-2xl border-2 border-gray-100 font-black text-[10px] uppercase tracking-widest h-12 px-8 hover:bg-red-50 hover:text-red-500 hover:border-red-100 transition-all">
                    <X class="mr-2 h-4 w-4 stroke-[3]" />
                    Tutup Builder
                </Button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden relative">
            <Transition
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <!-- Table View -->
                <div v-if="!showModal" class="p-8 h-full overflow-y-auto">
                    <CrudTable
                        :items="items"
                        :columns="columns"
                        :filters="filters"
                        :routes="routes"
                        :can="can"
                        @edit="handleEdit"
                        class="border-none shadow-none p-0"
                    >
                        <!-- Slot: Identity -->
                        <template #cell(identity)="{ item }">
                            <div class="flex items-center gap-4 py-1">
                                <div class="w-12 h-12 bg-emerald-50 rounded-2xl border border-emerald-100/50 flex items-center justify-center shadow-sm">
                                    <component :is="icons.find(i => i.key === item.icon_key)?.component || User" class="w-6 h-6 text-emerald-600 stroke-[2]" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-gray-900 tracking-tight text-sm uppercase">{{ item.name }}</span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ item.input_type }}</span>
                                </div>
                            </div>
                        </template>

                        <template #cell(group_name)="{ item }">
                            <Badge variant="secondary" class="bg-emerald-50 text-emerald-700 border-none font-black text-[10px] px-3 py-1 rounded-lg uppercase tracking-widest">
                                {{ item.group_name }}
                            </Badge>
                        </template>

                        <template #cell(created_at)="{ item }">
                            <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">
                                {{ new Date(item.created_at).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) }}
                            </span>
                        </template>
                    </CrudTable>
                </div>

                <!-- Split Pane Builder -->
                <div v-else class="flex h-full w-full bg-gray-50/50">
                    <!-- Left: Editor -->
                    <div class="w-[450px] bg-white border-r h-full flex flex-col shadow-2xl z-10 animate-in slide-in-from-left duration-500">
                        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                            <div class="space-y-10">
                                <!-- Section 1: Identity -->
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2 px-1 text-emerald-600">
                                        <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center">
                                            <Folder class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Identitas & Kategori</span>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div class="grid gap-2">
                                            <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Nama Bidang</Label>
                                            <Input v-model="form.name" placeholder="Contoh: Riwayat Sekolah" class="rounded-2xl border-gray-100 bg-gray-50/50 font-black tracking-tight text-sm focus:bg-white focus:ring-4 focus:ring-emerald-50 transition-all py-7 px-6" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Grup / Kategori</Label>
                                            <div class="flex gap-2">
                                                <Select v-model="form.group_name">
                                                    <SelectTrigger class="rounded-2xl border-gray-100 bg-gray-50/50 h-14 font-black text-xs uppercase flex-1 px-6">
                                                        <SelectValue placeholder="Pilih Grup" />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-2xl">
                                                        <SelectItem v-for="group in availableGroups" :key="group" :value="group" class="font-black text-[10px] uppercase tracking-widest py-3">{{ group }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-2 pt-2">
                                        <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Pilih Ikon</Label>
                                        <div class="flex flex-wrap gap-2 p-1 bg-gray-50/50 rounded-2xl border border-gray-100">
                                            <button v-for="icon in icons" :key="icon.key" type="button" @click="form.icon_key = icon.key" 
                                                :class="['p-4 rounded-xl border-2 transition-all flex items-center justify-center flex-1 min-w-[50px]', form.icon_key === icon.key ? 'border-emerald-600 bg-white text-emerald-600 shadow-lg scale-110 z-10' : 'border-transparent text-gray-300 hover:text-gray-600']">
                                                <component :is="icon.component" class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="h-px bg-gray-100"></div>

                                <!-- Section 2: Input Type -->
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2 px-1 text-emerald-600">
                                        <div class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center">
                                            <LayoutList class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Tipe Input & Konfigurasi</span>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="grid grid-cols-2 gap-2 p-1.5 bg-gray-100 rounded-2xl">
                                            <button v-for="t in ['text', 'textarea', 'date', 'select']" :key="t" @click="form.input_type = t" type="button" 
                                                :class="['py-3 text-[10px] font-black uppercase rounded-xl transition-all tracking-widest', form.input_type === t ? 'bg-white shadow-md text-emerald-600 scale-[1.02]' : 'text-gray-400 hover:text-gray-600']">
                                                {{ t === 'select' ? 'Dropdown' : (t === 'textarea' ? 'Paragraf' : t) }}
                                            </button>
                                        </div>

                                        <div class="space-y-6 pt-2">
                                            <!-- Placeholder -->
                                            <div v-if="['text', 'textarea'].includes(form.input_type)" class="grid gap-2 animate-in fade-in slide-in-from-top-2">
                                                <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Petunjuk (Placeholder)</Label>
                                                <Input v-model="form.options.placeholder" placeholder="Contoh: Masukkan riwayat..." class="rounded-2xl border-gray-100 bg-gray-50/50 font-black text-xs py-7 px-6" />
                                            </div>

                                            <!-- Template -->
                                            <div v-if="form.input_type === 'textarea'" class="grid gap-2 animate-in fade-in slide-in-from-top-2">
                                                <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Template Teks</Label>
                                                <Textarea v-model="form.options.template" placeholder="SD: &#10;SMP:" class="rounded-2xl border-gray-100 bg-gray-50/50 font-black text-xs min-h-[120px] p-6 focus:bg-white" />
                                            </div>

                                            <!-- Choices -->
                                            <div v-if="form.input_type === 'select'" class="space-y-4 animate-in fade-in slide-in-from-top-2">
                                                <div class="flex items-center justify-between px-1">
                                                    <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Daftar Pilihan</Label>
                                                    <div class="flex items-center gap-2">
                                                        <Label class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Kustom?</Label>
                                                        <Switch :checked="form.options.allow_custom" @update:checked="(v) => form.options.allow_custom = v" />
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <Input v-model="newOption" @keydown.enter.prevent="addChoice" placeholder="Ketik pilihan..." class="flex-1 rounded-2xl border-gray-100 bg-gray-50/50 font-black text-xs h-14 px-6" />
                                                    <Button @click="addChoice" type="button" variant="outline" class="h-14 w-14 rounded-2xl border-2 border-gray-100"><Plus class="h-5 w-5 stroke-[3]" /></Button>
                                                </div>
                                                <div class="flex flex-wrap gap-2 p-5 bg-gray-50/50 rounded-2xl border-2 border-dashed border-gray-200 min-h-[80px]">
                                                    <Badge v-for="opt in form.options.choices" :key="opt" class="bg-white text-gray-900 border-gray-200 px-4 py-1.5 rounded-xl flex items-center gap-2 shadow-sm">
                                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ opt }}</span>
                                                        <X @click="removeChoice(opt)" class="w-3.5 h-3.5 cursor-pointer text-gray-300 hover:text-red-500 transition-colors" />
                                                    </Badge>
                                                </div>
                                            </div>

                                            <!-- Date Format -->
                                            <div v-if="form.input_type === 'date'" class="grid gap-2 animate-in fade-in slide-in-from-top-2">
                                                <Label class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Format Tanggal</Label>
                                                <Select v-model="form.options.date_format">
                                                    <SelectTrigger class="rounded-2xl border-gray-100 bg-gray-50/50 h-14 font-black text-[10px] uppercase tracking-widest px-6">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-2xl">
                                                        <SelectItem value="d MMMM yyyy" class="font-black text-[10px] uppercase tracking-widest py-3">19 April 2026</SelectItem>
                                                        <SelectItem value="yyyy" class="font-black text-[10px] uppercase tracking-widest py-3">2026 (Hanya Tahun)</SelectItem>
                                                        <SelectItem value="MMMM yyyy" class="font-black text-[10px] uppercase tracking-widest py-3">April 2026</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-8 border-t bg-gray-50/30">
                            <Button @click="submit" :disabled="form.processing" class="w-full py-9 bg-gray-900 text-white rounded-[2rem] font-black uppercase text-xs tracking-[0.2em] hover:bg-emerald-600 transition-all shadow-2xl shadow-emerald-100 disabled:opacity-50">
                                {{ editingItem ? 'Simpan Perubahan' : 'Buat Bidang Sekarang' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Right: Preview -->
                    <div class="flex-1 h-full flex flex-col items-center justify-center p-12 relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-[0.03] pointer-events-none rotate-12 scale-150">
                            <div class="grid grid-cols-8 gap-12">
                                <component v-for="n in 64" :is="icons.find(i => i.key === form.icon_key)?.component || User" class="w-12 h-12" />
                            </div>
                        </div>

                        <div class="w-full max-w-md space-y-8 relative z-10 animate-in fade-in zoom-in duration-700">
                            <div class="text-center space-y-2">
                                <h3 class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.3em]">Live Preview</h3>
                                <p class="text-2xl font-black tracking-tighter text-gray-900">Tampilan Pada Form Profil</p>
                            </div>

                            <!-- Field Card Preview -->
                            <div class="bg-white p-8 rounded-[3rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.1)] border-t-8 border-emerald-600 ring-1 ring-gray-100">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shadow-inner">
                                            <component :is="icons.find(i => i.key === form.icon_key)?.component || User" class="w-4 h-4 stroke-[2.5]" />
                                        </div>
                                        <label class="text-[11px] font-black text-emerald-600 uppercase tracking-widest">{{ form.name || 'Nama Bidang' }}</label>
                                    </div>
                                    <div class="w-6 h-6 rounded-full bg-gray-50 flex items-center justify-center opacity-40">
                                        <X class="w-3 h-3 text-gray-400" />
                                    </div>
                                </div>
                                
                                <div class="mt-4 bg-gray-50/80 rounded-2xl p-4 border-2 border-transparent focus-within:border-emerald-100 focus-within:bg-white transition-all">
                                    <textarea v-if="form.input_type === 'textarea'" 
                                        :value="form.options.template"
                                        class="w-full bg-transparent font-black text-sm text-gray-800 outline-none border-none resize-none min-h-[100px]" 
                                        :placeholder="form.options.placeholder || 'Ketik sesuatu...'"></textarea>
                                    
                                    <input v-else-if="form.input_type === 'date'" 
                                        type="date" 
                                        class="w-full bg-transparent font-black text-sm text-gray-800 outline-none border-none" />
                                    
                                    <select v-else-if="form.input_type === 'select'" 
                                        class="w-full bg-transparent font-black text-sm text-gray-800 outline-none border-none appearance-none">
                                        <option value="">{{ form.options.placeholder || 'Pilih Opsi' }}</option>
                                        <option v-for="opt in form.options.choices" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                    
                                    <input v-else 
                                        type="text" 
                                        class="w-full bg-transparent font-black text-sm text-gray-800 outline-none border-none" 
                                        :placeholder="form.options.placeholder || 'Masukkan data...'" />
                                </div>

                                <div v-if="form.input_type === 'select' && form.options.allow_custom" class="mt-3 flex items-center gap-2 px-1">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Anggota dapat mengetik nilai kustom sendiri</span>
                                </div>
                            </div>

                            <p class="text-center text-[10px] font-bold text-gray-300 uppercase tracking-widest leading-loose">
                                Perubahan di sebelah kiri akan langsung <br/> memperbarui tampilan preview ini.
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e2e8f0; }

.select-none {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
</style>
