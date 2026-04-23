<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import CrudTable from '@/components/CrudTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { 
    User, 
    Plus, 
    PenLine, 
    Trash2, 
    ShieldCheck, 
    Database,
    Monitor,
    Eye,
    Activity,
    History
} from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
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
                title: 'Log Aktivitas',
                href: admin.logs.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Waktu', key: 'created_at', sortable: true },
    { label: 'Pelaku', key: 'user' },
    { label: 'Aksi', key: 'action_display' },
    { label: 'Keterangan', key: 'description' },
];

const selectedLog = ref<any>(null);
const showDetailModal = ref(false);

const openDetail = (log: any) => {
    selectedLog.value = log;
    showDetailModal.value = true;
};

const getActionColor = (action: string) => {
    return {
        CREATE: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        UPDATE: 'bg-blue-50 text-blue-700 border-blue-100',
        DELETE: 'bg-rose-50 text-rose-700 border-rose-100',
        SYSTEM: 'bg-purple-50 text-purple-700 border-purple-100',
    }[action] || 'bg-gray-50 text-gray-700';
};

const getActionIcon = (action: string) => {
    return {
        CREATE: Plus,
        UPDATE: PenLine,
        DELETE: Trash2,
        SYSTEM: Database,
    }[action] || Monitor;
};

const routes = {
    index: admin.logs.index,
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-8 font-sans select-none">
        <CrudTable
            title="Riwayat Jejak Digital"
            description="Audit seluruh perubahan data silsilah, pengaturan, dan aksi sistem secara real-time."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            :can="{
                create: false,
                update: false,
                delete: false,
                access_trash: false
            }"
            class="bg-white"
        >
            <!-- Slot: Time -->
            <template #cell(created_at)="{ item }">
                <div class="flex flex-col">
                    <span class="font-black text-gray-900 tracking-tight text-xs uppercase">{{ new Date(item.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                </div>
            </template>

            <!-- Slot: User -->
            <template #cell(user)="{ item }">
                <div v-if="item.user" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] font-black text-slate-500 overflow-hidden">
                        <img v-if="item.user.profile?.profile_photo_path" :src="'/storage/' + item.user.profile.profile_photo_path" class="w-full h-full object-cover" />
                        <span v-else>{{ item.user.name.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-black text-gray-900 tracking-tight text-[10px] uppercase">{{ item.user.name }}</span>
                        <span class="text-[8px] font-bold text-emerald-600 uppercase tracking-tighter">{{ item.ip_address }}</span>
                    </div>
                </div>
                <div v-else class="flex items-center gap-2 text-gray-400 italic">
                    <Monitor class="w-3 h-3" />
                    <span class="text-[10px] font-bold uppercase tracking-widest">System / Guest</span>
                </div>
            </template>

            <!-- Slot: Action -->
            <template #cell(action_display)="{ item }">
                <Badge variant="outline" :class="['px-3 py-1 rounded-lg border-2 font-black text-[9px] uppercase tracking-[0.1em] flex items-center gap-2 w-fit', getActionColor(item.action)]">
                    <component :is="getActionIcon(item.action)" class="w-3 h-3" />
                    {{ item.action }}
                </Badge>
            </template>

            <!-- Slot: Description -->
            <template #cell(description)="{ item }">
                <div class="max-w-md">
                    <p class="text-[11px] font-bold text-gray-600 leading-relaxed truncate">{{ item.description }}</p>
                </div>
            </template>

            <!-- Slot: Actions -->
            <template #item-actions="{ item }">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button @click="openDetail(item)" variant="outline" size="icon" class="h-8 w-8 rounded-lg text-emerald-600 border-gray-100 hover:bg-emerald-50 transition-all shadow-sm">
                                <Eye class="h-3.5 w-3.5 stroke-[2.5]" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent side="top">Lihat Detail</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </template>
        </CrudTable>
    </div>

    <!-- Audit Detail Modal -->
    <Dialog v-model:open="showDetailModal">
        <DialogContent class="sm:max-w-[600px] rounded-[2.5rem] p-0 overflow-hidden border-none shadow-2xl">
            <div v-if="selectedLog" class="flex flex-col h-full bg-white">
                <!-- Header with Action Color -->
                <div :class="['p-8 pb-12 relative overflow-hidden', getActionColor(selectedLog.action)]">
                    <div class="absolute top-0 right-0 p-8 opacity-10 rotate-12">
                        <component :is="getActionIcon(selectedLog.action)" class="w-32 h-32" />
                    </div>
                    
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <Badge variant="outline" :class="['px-4 py-1.5 rounded-xl border-2 font-black text-[10px] uppercase tracking-widest shadow-sm bg-white/50 backdrop-blur-sm', getActionColor(selectedLog.action)]">
                                {{ selectedLog.action }}
                            </Badge>
                            <span class="text-[10px] font-black text-gray-500/60 uppercase tracking-[0.2em]">{{ new Date(selectedLog.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                        <h2 class="text-2xl font-black tracking-tighter text-gray-900 leading-tight">
                            {{ selectedLog.description }}
                        </h2>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-8 -mt-6 bg-white rounded-t-[3rem] relative z-20 space-y-8 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    <!-- Identity Section -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-5 rounded-3xl space-y-1.5">
                            <div class="flex items-center gap-2 text-gray-400">
                                <User class="w-3.5 h-3.5" />
                                <span class="text-[9px] font-black uppercase tracking-widest">Pelaku</span>
                            </div>
                            <p class="text-xs font-black text-gray-800 uppercase">{{ selectedLog.user?.name || 'System' }}</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-3xl space-y-1.5">
                            <div class="flex items-center gap-2 text-gray-400">
                                <ShieldCheck class="w-3.5 h-3.5" />
                                <span class="text-[9px] font-black uppercase tracking-widest">IP Address</span>
                            </div>
                            <p class="text-xs font-black text-emerald-600 tracking-tighter">{{ selectedLog.ip_address || 'Internal' }}</p>
                        </div>
                    </div>

                    <!-- Properties / Diff Section -->
                    <div v-if="selectedLog.properties && (selectedLog.properties.new || selectedLog.properties.old)" class="space-y-4">
                        <div class="flex items-center gap-2 px-1 text-gray-400">
                            <History class="w-4 h-4" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Detail Perubahan Data</span>
                        </div>
                        
                        <div class="border border-slate-100 rounded-3xl overflow-hidden shadow-sm">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-slate-100">Bidang</th>
                                        <th v-if="selectedLog.properties.old" class="px-5 py-3 text-[9px] font-black text-rose-400 uppercase tracking-widest border-b border-slate-100">Sebelum</th>
                                        <th class="px-5 py-3 text-[9px] font-black text-emerald-500 uppercase tracking-widest border-b border-slate-100">Sesudah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(val, key) in (selectedLog.properties.new || selectedLog.properties)" :key="key" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-4 text-[10px] font-black text-gray-500 uppercase tracking-tighter border-b border-slate-50">{{ key }}</td>
                                        <td v-if="selectedLog.properties.old" class="px-5 py-4 border-b border-slate-50">
                                            <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-1 rounded-md">{{ selectedLog.properties.old[key] ?? '-' }}</span>
                                        </td>
                                        <td class="px-5 py-4 border-b border-slate-50">
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md">{{ val }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 px-1 text-gray-400">
                            <Activity class="w-4 h-4" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Metadata Sistem</span>
                        </div>
                        <div class="p-5 bg-slate-50 rounded-3xl">
                            <p class="text-[10px] font-medium text-gray-400 leading-relaxed font-mono">
                                {{ selectedLog.user_agent }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 border-t border-slate-50 flex justify-end">
                    <Button @click="showDetailModal = false" class="px-8 py-6 bg-gray-900 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-emerald-600 transition-all shadow-xl">
                        Tutup Audit
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
