<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Globe, Sparkles } from 'lucide-vue-next';
import { ref, computed } from 'vue';
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
                title: 'Manajemen Sosial Media',
                href: admin.socialMedias.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Platform', key: 'identity' },
    { label: 'Prefix URL', key: 'prefix', sortable: true },
    { label: 'Terdaftar', key: 'created_at', sortable: true },
];

const routes = {
    index: admin.socialMedias.index,
    destroy: (id: number) => admin.socialMedias.destroy({ socialMedia: id }),
    restore: (id: number) => admin.socialMedias.restore({ socialMedia: id }),
    forceDestroy: (id: number) => admin.socialMedias.forceDestroy({ socialMedia: id }),
};

const showModal = ref(false);
const editingItem = ref<any>(null);

const form = useForm({
    name: '',
    prefix: '',
    icon_url: '', // We keep this for DB storage, but auto-fill it
});

// Presets for quick adding
const presets = [
    { name: 'WhatsApp', prefix: 'https://wa.me/', slug: 'whatsapp' },
    { name: 'Instagram', prefix: 'https://instagram.com/', slug: 'instagram' },
    { name: 'Facebook', prefix: 'https://facebook.com/', slug: 'facebook' },
    { name: 'X', prefix: 'https://x.com/', slug: 'x' },
    { name: 'TikTok', prefix: 'https://tiktok.com/@', slug: 'tiktok' },
    { name: 'LinkedIn', prefix: 'https://linkedin.com/in/', slug: 'linkedin' },
    { name: 'GitHub', prefix: 'https://github.com/', slug: 'github' },
];

function applyPreset(preset: any) {
    form.name = preset.name;
    form.prefix = preset.prefix;
    form.icon_url = `https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/${preset.slug}.svg`;
}

// Auto-lookup icon based on name
const autoIconUrl = computed(() => {
    if (!form.name) {
return null;
}

    const slug = form.name.toLowerCase().replace(/ /g, '').replace(/\(twitter\)/g, '');

    return `https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/${slug}.svg`;
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    showModal.value = true;
};

const handleEdit = (item: any) => {
    editingItem.value = item;
    form.name = item.name;
    form.prefix = item.prefix || '';
    form.icon_url = item.icon_url || '';
    showModal.value = true;
};

const submit = () => {
    // Always use the auto-detected icon URL if available
    if (autoIconUrl.value) {
        form.icon_url = autoIconUrl.value;
    }

    const options = {
        onSuccess: () => (showModal.value = false),
    };

    if (editingItem.value) {
        const routeDef = admin.socialMedias.update({ socialMedia: editingItem.value.id });
        form.put(typeof routeDef === 'string' ? routeDef : routeDef.url, options);
    } else {
        const routeDef = admin.socialMedias.store();
        form.post(typeof routeDef === 'string' ? routeDef : routeDef.url, options);
    }
};
</script>

<template>
    <Head title="Manajemen Sosmed" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-8 font-sans select-none">
        <CrudTable
            title="Platform Sosial Media"
            description="Atur daftar media sosial yang dapat digunakan oleh anggota keluarga di profil mereka."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            :can="can"
            @edit="handleEdit"
        >
            <template #header-actions>
                <Button @click="openCreateModal" class="bg-gray-900 text-white rounded-xl font-bold text-xs shadow-lg h-12 px-6">
                    <Plus class="mr-2 h-4 w-4 stroke-[3]" />
                    Tambah Platform
                </Button>
            </template>

            <!-- Slot: Identity (Icon + Name) -->
            <template #cell(identity)="{ item }">
                <div class="flex items-center gap-4 py-1">
                    <div class="w-10 h-10 bg-gray-50 rounded-xl border border-gray-100 p-2 flex items-center justify-center overflow-hidden">
                        <img v-if="item.icon_url" :src="item.icon_url" class="w-full h-full object-contain opacity-70 group-hover:opacity-100 transition-opacity" />
                        <Globe v-else class="w-5 h-5 text-gray-300" />
                    </div>
                    <span class="font-black text-gray-900 tracking-tight text-sm">{{ item.name }}</span>
                </div>
            </template>

            <template #cell(created_at)="{ item }">
                <span class="text-xs font-semibold text-gray-400">
                    {{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                </span>
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[500px] rounded-[2.5rem] p-8">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black tracking-tighter text-gray-900">
                    {{ editingItem ? 'Edit Platform' : 'Tambah Platform' }}
                </DialogTitle>
                <DialogDescription class="text-sm font-medium text-gray-400 mt-1">
                    Isi nama platform dan prefix URL untuk memudahkan input link nantinya.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-8 py-6 text-left">
                <!-- Preset Buttons -->
                <div class="space-y-3">
                    <Label class="text-xs font-bold text-gray-400 px-1 flex items-center gap-2">
                        <Sparkles class="w-3 h-3" /> Pilihan Cepat
                    </Label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="p in presets" :key="p.name" @click="applyPreset(p)" type="button" class="p-3 bg-gray-50 hover:bg-emerald-50 border border-transparent hover:border-blue-200 rounded-xl transition-all shadow-sm group">
                            <img :src="`https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/${p.slug}.svg`" class="w-5 h-5 opacity-40 group-hover:opacity-100 transition-opacity" />
                        </button>
                    </div>
                </div>

                <div class="grid gap-6">
                    <!-- Name & Icon Preview -->
                    <div class="grid gap-2">
                        <Label for="name" class="text-xs font-bold text-gray-500 px-1">Nama Platform</Label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <Input id="name" v-model="form.name" placeholder="Contoh: Instagram" class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6" />
                            </div>
                            <div class="w-14 h-14 bg-white border-2 border-dashed border-gray-100 rounded-2xl flex items-center justify-center p-3 shadow-inner">
                                <img v-if="autoIconUrl" :src="autoIconUrl" class="w-full h-full object-contain animate-in fade-in zoom-in duration-300" @error="(e: any) => e.target.style.display = 'none'" />
                                <Globe v-else class="w-6 h-6 text-gray-100" />
                            </div>
                        </div>
                    </div>

                    <!-- Prefix -->
                    <div class="grid gap-2">
                        <Label for="prefix" class="text-xs font-bold text-gray-500 px-1">Prefix URL (Opsional)</Label>
                        <Input id="prefix" v-model="form.prefix" placeholder="Contoh: instagram.com/" class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6" />
                        <p class="text-[10px] text-gray-400 font-medium px-1 italic">Membantu mempercepat anggota saat memasukkan username mereka.</p>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="submit" @click="submit" :disabled="form.processing" class="w-full py-7 bg-gray-900 text-white rounded-3xl font-bold text-sm hover:bg-emerald-600 transition-all shadow-xl shadow-gray-100">
                    {{ editingItem ? 'Simpan Perubahan' : 'Simpan Platform Baru' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
