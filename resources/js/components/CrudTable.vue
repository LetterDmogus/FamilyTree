<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { 
    KeyRound, 
    MoreHorizontal, 
    PencilLine, 
    RotateCcw, 
    Search, 
    ShieldBan, 
    Trash2, 
    ArrowUpDown,
    Check,
    ListOrdered
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface Column {
    label: string;
    key: string;
    sortable?: boolean;
}

const props = withDefaults(defineProps<{
    title: string;
    description?: string;
    items: {
        data: any[];
        links: any[];
        meta?: any;
    };
    columns: Column[];
    filters: {
        search?: string;
        trashed?: string;
        sort?: string;
        per_page?: string | number;
    };
    routes: {
        index: (params?: any) => string;
        destroy?: (id: any) => string;
        restore?: (id: any) => string;
        forceDestroy?: (id: any) => string;
    };
    can?: {
        create?: boolean;
        update?: boolean;
        delete?: boolean;
        access_trash?: boolean;
    };
    showResetPassword?: boolean;
}>(), {
    showResetPassword: false,
    can: () => ({
        create: true,
        update: true,
        delete: true,
        access_trash: true
    })
});

const emit = defineEmits<{
    edit: [item: any];
    resetPassword: [item: any];
}>();

const search = ref(props.filters.search || '');
const trashed = ref(props.filters.trashed || 'active');
const sort = ref(props.filters.sort || '-created_at');
const perPage = ref(String(props.filters.per_page || '10'));

watch(() => props.filters.trashed, (newVal) => { trashed.value = newVal || 'active'; });
watch(() => props.filters.sort, (newVal) => { sort.value = newVal || '-created_at'; });
watch(() => props.filters.per_page, (newVal) => { perPage.value = String(newVal || '10'); });

const debouncedSearch = debounce((value: string) => {
    router.get(props.routes.index({ query: { ...props.filters, search: value, page: 1 } }).url, {}, { preserveState: true, replace: true });
}, 300);

watch(search, (value) => { debouncedSearch(value); });

const handleFilterChange = (key: string, value: any) => {
    router.get(props.routes.index({ query: { ...props.filters, [key]: value, page: 1 } }).url, {}, { preserveState: true });
};

const handleSort = (key: string) => {
    let newSort = key;
    if (props.filters.sort === key) newSort = `-${key}`;
    else if (props.filters.sort === `-${key}`) newSort = '';
    handleFilterChange('sort', newSort);
};

const deleteItem = (id: number) => {
    if (props.routes.destroy && confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        const routeDef = props.routes.destroy(id);
        router.delete(typeof routeDef === 'string' ? routeDef : routeDef.url);
    }
};

const restoreItem = (id: number) => {
    if (props.routes.restore && confirm('Pulihkan data yang dihapus ini?')) {
        const routeDef = props.routes.restore(id);
        router.put(typeof routeDef === 'string' ? routeDef : routeDef.url);
    }
};

const forceDeleteItem = (id: number) => {
    if (props.routes.forceDestroy && confirm('HAPUS PERMANEN: Tindakan ini tidak dapat dibatalkan. Lanjutkan?')) {
        const routeDef = props.routes.forceDestroy(id);
        router.delete(typeof routeDef === 'string' ? routeDef : routeDef.url);
    }
};
</script>

<template>
    <TooltipProvider>
        <div class="flex w-full flex-col gap-8 select-none font-sans">
            <div class="flex flex-col gap-1.5 justify-between md:flex-row md:items-end">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-gray-900">{{ title }}</h2>
                    <p v-if="description" class="text-sm font-medium text-gray-400">{{ description }}</p>
                </div>
                <div v-if="can.create">
                    <slot name="header-actions"></slot>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="relative w-full md:max-w-md">
                    <Search class="absolute left-3.5 top-3.5 h-4 w-4 text-gray-400 stroke-[2.5]" />
                    <Input v-model="search" type="search" placeholder="Cari data..." class="pl-10 py-6 rounded-2xl border-gray-100 bg-gray-50 focus:bg-white transition-all font-semibold" />
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <!-- Per Page -->
                    <Select :model-value="perPage" @update:model-value="(val) => handleFilterChange('per_page', val)">
                        <SelectTrigger class="w-full md:w-[100px] h-12 rounded-xl border-gray-100 bg-white font-bold text-xs shadow-sm">
                            <div class="flex items-center gap-2">
                                <ListOrdered class="h-3.5 w-3.5 text-emerald-600 stroke-[3]" />
                                <SelectValue placeholder="Baris" />
                            </div>
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem v-for="size in ['10', '25', '50', '100']" :key="size" :value="size" class="text-xs font-semibold">{{ size }} Baris</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Order By -->
                    <Select v-model="sort" @update:model-value="(val) => handleFilterChange('sort', val)">
                        <SelectTrigger class="w-full md:w-[160px] h-12 rounded-xl border-gray-100 bg-white font-bold text-xs shadow-sm">
                            <div class="flex items-center gap-2">
                                <ArrowUpDown class="h-3.5 w-3.5 text-emerald-600 stroke-[3]" />
                                <SelectValue placeholder="Urutkan" />
                            </div>
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem value="-created_at" class="text-xs font-semibold">Terbaru</SelectItem>
                            <SelectItem value="created_at" class="text-xs font-semibold">Terlama</SelectItem>
                            <SelectItem value="name" class="text-xs font-semibold">Alfabet A-Z</SelectItem>
                            <SelectItem value="-name" class="text-xs font-semibold">Alfabet Z-A</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Recycle Bin -->
                    <Select v-if="can.access_trash" v-model="trashed" @update:model-value="(val) => handleFilterChange('trashed', val)">
                        <SelectTrigger class="w-full md:w-[160px] h-12 rounded-xl border-gray-100 bg-white font-bold text-xs shadow-sm">
                            <div class="flex items-center gap-2">
                                <Trash2 class="h-3.5 w-3.5 text-rose-500 stroke-[3]" />
                                <SelectValue placeholder="Status" />
                            </div>
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem value="active" class="text-xs font-semibold">Aktif Saja</SelectItem>
                            <SelectItem value="with" class="text-xs font-semibold">Semua Data</SelectItem>
                            <SelectItem value="only" class="text-xs font-semibold">Hanya Sampah</SelectItem>
                        </SelectContent>
                    </Select>

                    <slot name="actions"></slot>
                </div>
            </div>

            <div class="w-full overflow-hidden rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-100/50 bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50/50 transition-colors text-gray-500">
                            <th v-for="column in columns" :key="column.key" class="h-14 px-6 text-left align-middle font-bold text-xs tracking-tight" :class="{ 'cursor-pointer hover:text-gray-900': column.sortable }" @click="column.sortable ? handleSort(column.key) : null">
                                <div class="flex items-center gap-2">
                                    {{ column.label }}
                                    <ArrowUpDown v-if="column.sortable" class="h-3 w-3 opacity-30" />
                                    <span v-if="column.sortable && filters.sort === column.key" class="text-emerald-600">↑</span>
                                    <span v-if="column.sortable && filters.sort === `-${column.key}`" class="text-emerald-600">↓</span>
                                </div>
                            </th>
                            <th class="h-14 px-6 text-right align-middle font-bold text-xs tracking-tight">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="item in items.data" :key="item.id" class="transition-colors hover:bg-gray-50/50 group" :class="{ 'bg-rose-50/30 opacity-70 italic': item.deleted_at }">
                            <td v-for="column in columns" :key="column.key" class="px-6 py-4 align-middle">
                                <slot :name="`cell(${column.key})`" :item="item">
                                    <span class="font-semibold text-gray-700">{{ item[column.key] }}</span>
                                </slot>
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div v-if="!item.roles?.some((r: any) => r.name === 'superadmin')" class="flex items-center justify-end gap-1.5 transition-all">
                                    <Tooltip v-if="!item.deleted_at && can.update">
                                        <TooltipTrigger as-child><Button variant="outline" size="icon" class="w-8 h-8 rounded-lg text-sky-600 border-gray-100 hover:bg-sky-50 transition-all shadow-sm" @click="$emit('edit', item)"><PencilLine class="h-3.5 w-3.5 stroke-[2.5]" /></Button></TooltipTrigger>
                                        <TooltipContent side="top">Edit</TooltipContent>
                                    </Tooltip>
                                    <Tooltip v-if="!item.deleted_at && showResetPassword && can.update">
                                        <TooltipTrigger as-child><Button variant="outline" size="icon" class="w-8 h-8 rounded-lg text-amber-600 border-gray-100 hover:bg-amber-50 transition-all shadow-sm" @click="$emit('resetPassword', item)"><KeyRound class="h-3.5 w-3.5 stroke-[2.5]" /></Button></TooltipTrigger>
                                        <TooltipContent side="top">Reset Password</TooltipContent>
                                    </Tooltip>
                                    <Tooltip v-if="!item.deleted_at && routes.destroy && can.delete">
                                        <TooltipTrigger as-child><Button variant="outline" size="icon" class="w-8 h-8 rounded-lg text-rose-600 border-gray-100 hover:bg-rose-50 transition-all shadow-sm" @click="deleteItem(item.id)"><Trash2 class="h-3.5 w-3.5 stroke-[2.5]" /></Button></TooltipTrigger>
                                        <TooltipContent side="top">Hapus</TooltipContent>
                                    </Tooltip>
                                    <Tooltip v-if="item.deleted_at && routes.restore && can.access_trash">
                                        <TooltipTrigger as-child><Button variant="outline" size="icon" class="w-8 h-8 rounded-lg text-emerald-600 border-gray-100 hover:bg-emerald-50 transition-all shadow-sm" @click="restoreItem(item.id)"><RotateCcw class="h-3.5 w-3.5 stroke-[2.5]" /></Button></TooltipTrigger>
                                        <TooltipContent side="top">Pulihkan</TooltipContent>
                                    </Tooltip>
                                    <Tooltip v-if="item.deleted_at && routes.forceDestroy && can.access_trash">
                                        <TooltipTrigger as-child><Button variant="outline" size="icon" class="w-8 h-8 rounded-lg text-red-700 border-gray-100 hover:bg-red-50 transition-all shadow-sm" @click="forceDeleteItem(item.id)"><ShieldBan class="h-3.5 w-3.5 stroke-[2.5]" /></Button></TooltipTrigger>
                                        <TooltipContent side="top">Hapus Permanen</TooltipContent>
                                    </Tooltip>
                                </div>
                                <div v-else class="flex justify-end px-3">
                                    <ShieldCheck class="w-4 h-4 text-emerald-600 opacity-30" title="Protected Account" />
                                </div>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0"><td :colspan="columns.length + 1" class="p-20 text-center text-gray-400 font-bold text-xs italic">Tidak ada data ditemukan.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-12">
                <div class="text-xs font-bold text-gray-400 tracking-tight">Menampilkan <span class="text-gray-900">{{ items.data.length }}</span> dari <span class="text-gray-900">{{ items.meta?.total || items.data.length }}</span> data</div>
                <div class="flex items-center gap-1.5">
                    <Button v-for="(link, index) in items.links" :key="index" variant="ghost" size="sm" :disabled="!link.url || link.active" @click="link.url ? router.get(link.url, {}, { preserveState: true }) : null" v-html="link.label" :class="['min-w-[36px] h-9 rounded-lg font-bold text-xs transition-all', link.active ? 'bg-gray-900 text-white shadow-lg' : 'text-gray-400 hover:bg-gray-100 hover:text-gray-900', !link.url ? 'opacity-20' : '']" />
                </div>
            </div>
        </div>
    </TooltipProvider>
</template>

<style scoped>
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
