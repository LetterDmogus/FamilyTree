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

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-8 font-sans select-none">
        <CrudTable
            title="Bidang Data Profil"
            description="Atur kolom informasi tambahan yang bisa diisi oleh anggota keluarga, dikelompokkan per kategori."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            @edit="handleEdit"
        >
            <template #actions>
                <Button @click="openCreateModal" class="bg-gray-900 text-white rounded-xl font-bold text-xs shadow-lg h-12 px-6">
                    <Plus class="mr-2 h-4 w-4 stroke-[3]" />
                    Tambah Bidang
                </Button>
            </template>

            <!-- Slot: Identity -->
            <template #cell(identity)="{ item }">
                <div class="flex items-center gap-4 py-1">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center">
                        <component :is="icons.find(i => i.key === item.icon_key)?.component || User" class="w-5 h-5 text-indigo-600 stroke-[2.5]" />
                    </div>
                    <div class="flex flex-col">
                        <span class="font-black text-gray-900 tracking-tight text-sm">{{ item.name }}</span>
                        <span class="text-[10px] text-gray-400 font-bold">{{ item.input_type }}</span>
                    </div>
                </div>
            </template>

            <template #cell(group_name)="{ item }">
                <Badge variant="secondary" class="bg-blue-50 text-blue-700 border-none font-bold text-[10px] px-2.5 py-0.5 rounded-md">
                    {{ item.group_name }}
                </Badge>
            </template>

            <template #cell(created_at)="{ item }">
                <span class="text-xs font-semibold text-gray-400">
                    {{ new Date(item.created_at).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) }}
                </span>
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[600px] rounded-[2.5rem] p-0 overflow-hidden border-none shadow-2xl">
            <div class="bg-white p-8">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black tracking-tighter text-gray-900">
                        {{ editingItem ? 'Edit Bidang Data' : 'Tambah Bidang Baru' }}
                    </DialogTitle>
                    <DialogDescription class="text-sm font-medium text-gray-400 mt-1">
                        Konfigurasi grup dan cara input data untuk anggota keluarga.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-8 py-8 text-left max-h-[65vh] overflow-y-auto pr-4 custom-scrollbar">
                    
                    <!-- Section 1: Identity & Group -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 px-1 text-indigo-600">
                            <Folder class="w-4 h-4" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Identitas & Kategori</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label class="text-xs font-bold text-gray-500 px-1">Nama Bidang</Label>
                                <Input v-model="form.name" placeholder="Contoh: Riwayat Sekolah" class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6" />
                            </div>
                            <div class="grid gap-2">
                                <Label class="text-xs font-bold text-gray-500 px-1">Grup / Kategori</Label>
                                <div class="flex gap-2">
                                    <Select v-model="form.group_name">
                                        <SelectTrigger class="rounded-xl border-gray-100 bg-gray-50 h-12 font-bold flex-1">
                                            <SelectValue placeholder="Pilih Grup" />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl">
                                            <SelectItem v-for="group in availableGroups" :key="group" :value="group" class="font-bold text-xs">{{ group }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input v-model="form.group_name" placeholder="Atau ketik baru..." class="rounded-xl border-gray-100 bg-gray-50 font-bold h-12 text-xs w-32" />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-xs font-bold text-gray-500 px-1">Pilih Ikon</Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="icon in icons" :key="icon.key" type="button" @click="form.icon_key = icon.key" 
                                    :class="['p-3 rounded-xl border-2 transition-all flex items-center justify-center', form.icon_key === icon.key ? 'border-indigo-600 bg-indigo-50 text-indigo-600 shadow-md' : 'border-gray-50 text-gray-400 bg-gray-50']">
                                    <component :is="icon.component" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="h-px bg-gray-100 mx-1"></div>

                    <!-- Section 2: Input Type & Intelligent Config -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 px-1 text-emerald-600">
                            <LayoutList class="w-4 h-4" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Tipe Input & Konfigurasi</span>
                        </div>

                        <div class="grid gap-6">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 p-1 bg-gray-50 rounded-2xl">
                                <button v-for="t in ['text', 'textarea', 'date', 'select']" :key="t" @click="form.input_type = t" type="button" 
                                    :class="['py-2.5 text-[10px] font-black uppercase rounded-xl transition-all', form.input_type === t ? 'bg-white shadow-sm text-emerald-600' : 'text-gray-400']">
                                    {{ t === 'select' ? 'Dropdown' : (t === 'textarea' ? 'Teks Panjang' : t) }}
                                </button>
                            </div>

                            <!-- Contextual Settings based on Type -->
                            <div class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                
                                <!-- Placeholder (Common for Text & Textarea) -->
                                <div v-if="['text', 'textarea'].includes(form.input_type)" class="grid gap-2">
                                    <Label class="text-xs font-bold text-gray-500 px-1">Petunjuk Input (Placeholder)</Label>
                                    <Input v-model="form.options.placeholder" placeholder="Contoh: Masukkan riwayat lengkap..." class="rounded-xl border-gray-100 bg-gray-50 font-bold py-6" />
                                </div>

                                <!-- Template (Only for Textarea) -->
                                <div v-if="form.input_type === 'textarea'" class="grid gap-2">
                                    <Label class="text-xs font-bold text-gray-500 px-1">Template Teks Awal</Label>
                                    <Textarea v-model="form.options.template" placeholder="SD: &#10;SMP: &#10;SMA:" class="rounded-xl border-gray-100 bg-gray-50 font-bold min-h-[100px]" />
                                    <p class="text-[10px] text-gray-400 px-1 italic leading-relaxed">Teks ini akan otomatis terisi saat anggota mulai menginput bidang ini.</p>
                                </div>

                                <!-- Choices & Custom (Only for Select) -->
                                <div v-if="form.input_type === 'select'" class="space-y-4">
                                    <div class="grid gap-2">
                                        <div class="flex items-center justify-between px-1">
                                            <Label class="text-xs font-bold text-gray-500">Daftar Pilihan (Choices)</Label>
                                            <div class="flex items-center gap-2">
                                                <Label class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Izinkan Kustom?</Label>
                                                <Switch :checked="form.options.allow_custom" @update:checked="(v) => form.options.allow_custom = v" />
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <Input v-model="newOption" @keydown.enter.prevent="addChoice" placeholder="Ketik lalu Enter..." class="flex-1 rounded-xl border-gray-100 bg-gray-50 font-bold h-12" />
                                            <Button @click="addChoice" type="button" variant="outline" class="h-12 w-12 rounded-xl border-gray-100"><Plus class="h-5 w-5" /></Button>
                                        </div>
                                        <div class="flex flex-wrap gap-2 p-4 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-100 min-h-[60px]">
                                            <Badge v-for="opt in form.options.choices" :key="opt" class="bg-white text-gray-900 border-gray-200 px-3 py-1 rounded-lg flex items-center gap-2">
                                                <span class="text-xs font-bold">{{ opt }}</span>
                                                <X @click="removeChoice(opt)" class="w-3 h-3 cursor-pointer text-gray-400 hover:text-red-500" />
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Format (Only for Date) -->
                                <div v-if="form.input_type === 'date'" class="grid gap-2">
                                    <Label class="text-xs font-bold text-gray-500 px-1">Format Tampilan Tanggal</Label>
                                    <Select v-model="form.options.date_format">
                                        <SelectTrigger class="rounded-xl border-gray-100 bg-gray-50 h-12 font-bold">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl">
                                            <SelectItem value="d MMMM yyyy" class="font-bold text-xs">19 April 2026</SelectItem>
                                            <SelectItem value="yyyy" class="font-bold text-xs">2026 (Hanya Tahun)</SelectItem>
                                            <SelectItem value="MMMM yyyy" class="font-bold text-xs">April 2026</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="p-8 pt-0">
                <Button type="submit" @click="submit" :disabled="form.processing" class="w-full py-8 bg-gray-900 text-white rounded-3xl font-black uppercase text-xs tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-gray-100">
                    {{ editingItem ? 'Simpan Perubahan' : 'Buat Bidang Sekarang' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
