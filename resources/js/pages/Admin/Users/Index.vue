<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Crown, Heart, Skull, Venus, Mars } from 'lucide-vue-next';
import { ref } from 'vue';
import CrudTable from '@/components/CrudTable.vue';
import { Badge } from '@/components/ui/badge';
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
import admin from '@/routes/admin';

const props = defineProps<{
    items: {
        data: any[];
        links: any[];
        meta?: any;
    };
    filters: any;
    roles: Array<{ id: number; name: string }>;
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
                title: 'User Management',
                href: admin.users.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Identitas Anggota', key: 'identity' },
    { label: 'Status & Gender', key: 'status' },
    { label: 'Role', key: 'role_name' },
    { label: 'Tanggal Bergabung', key: 'created_at', sortable: true },
];

const routes = {
    index: admin.users.index,
    destroy: (id: number) => admin.users.destroy({ user: id }),
    restore: (id: number) => admin.users.restore({ user: id }),
    forceDestroy: (id: number) => admin.users.forceDestroy({ user: id }),
};

const showModal = ref(false);
const editingUser = ref<any>(null);

const form = useForm({
    name: '',
    email: '',
    role: '',
});

const handleEdit = (user: any) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles?.[0]?.name || 'member';
    showModal.value = true;
};

const submit = () => {
    const routeDef = admin.users.update({ user: editingUser.value.id });
    form.put(typeof routeDef === 'string' ? routeDef : routeDef.url, {
        onSuccess: () => (showModal.value = false),
    });
};

const handleResetPassword = (user: any) => {
    if (confirm(`Apakah Anda yakin ingin mereset password untuk ${user.name}? Password default akan menjadi "password".`)) {
        const routeDef = admin.users.resetPassword({ user: user.id });
        useForm({}).put(typeof routeDef === 'string' ? routeDef : routeDef.url);
    }
};

function getRoleColor(role: string) {
    if (role === 'superadmin') {
return 'bg-rose-600 text-white border-none'
}

    if (role === 'admin') {
return 'bg-emerald-600 text-white border-none'
}

    return 'bg-slate-100 text-slate-600 border-none'
}
</script>

<template>
    <Head title="Manajemen User" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-8 select-none font-sans">
        <CrudTable
            title="Manajemen Anggota"
            description="Kelola akun pengguna, hak akses, dan pantau status profil anggota silsilah."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            :can="can"
            show-reset-password
            @edit="handleEdit"
            @reset-password="handleResetPassword"
        >
            <!-- Slot: Identity -->
            <template #cell(identity)="{ item }">
                <div class="flex items-center gap-4 py-1">
                    <div class="relative flex-shrink-0">
                        <img 
                            :src="item.profile?.photo_url || `https://ui-avatars.com/api/?name=${item.name}&background=f1f5f9&color=64748b`" 
                            class="w-12 h-12 rounded-2xl object-cover border-2 border-white shadow-sm"
                        />
                        <div v-if="item.profile?.is_family_head" class="absolute -top-1 -left-1 w-5 h-5 bg-amber-500 text-white rounded-full flex items-center justify-center text-[8px] border-2 border-white shadow-sm" title="Kepala Keluarga">
                            <Crown class="w-2.5 h-2.5 fill-current" />
                        </div>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-black text-gray-900 leading-tight truncate tracking-tight text-sm">{{ item.profile?.full_name || item.name }}</span>
                        <span class="text-[10px] text-gray-400 font-bold tracking-widest mt-0.5 truncate">{{ item.email }}</span>
                    </div>
                </div>
            </template>

            <!-- Slot: Status & Gender -->
            <template #cell(status)="{ item }">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-2">
                        <Badge :class="item.profile?.is_alive ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200'" class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md border shadow-none flex items-center gap-1">
                            <Heart v-if="item.profile?.is_alive" class="w-2.5 h-2.5 fill-current" />
                            <Skull v-else class="w-2.5 h-2.5" />
                            {{ item.profile?.is_alive ? 'Hidup' : 'Wafat' }}
                        </Badge>
                        <Badge :class="item.profile?.gender === 'F' ? 'bg-pink-50 text-pink-500 border-pink-100' : 'bg-emerald-50 text-blue-500 border-emerald-100'" class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md border shadow-none flex items-center gap-1">
                            <component :is="item.profile?.gender === 'F' ? Venus : Mars" class="w-2.5 h-2.5 stroke-[3]" />
                            {{ item.profile?.gender === 'F' ? 'P' : 'L' }}
                        </Badge>
                    </div>
                </div>
            </template>

            <!-- Slot: Role -->
            <template #cell(role_name)="{ item }">
                <Badge :class="getRoleColor(item.roles?.[0]?.name || 'member')" class="text-[9px] font-black uppercase px-3 py-1 rounded-lg tracking-widest">
                    {{ item.roles?.[0]?.name || 'member' }}
                </Badge>
            </template>

            <template #cell(created_at)="{ item }">
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-gray-700">{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    <span class="text-[9px] text-gray-400 font-medium uppercase tracking-tighter">{{ new Date(item.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }} WIB</span>
                </div>
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[450px] rounded-[2rem] p-8">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black tracking-tighter text-gray-900">Edit Akun & Akses</DialogTitle>
                <DialogDescription class="text-xs font-bold uppercase text-gray-400 tracking-widest mt-1">
                    Sesuaikan informasi login dan peran pengguna.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-6 py-6 text-left">
                <div class="grid gap-2">
                    <Label for="name" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Nama Akun</Label>
                    <Input id="name" v-model="form.name" class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6" />
                </div>
                <div class="grid gap-2">
                    <Label for="email" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Alamat Email</Label>
                    <Input id="email" type="email" v-model="form.email" class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6" />
                </div>
                <div class="grid gap-2">
                    <Label for="role" class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Hak Akses (Role)</Label>
                    <Select v-model="form.role">
                        <SelectTrigger class="rounded-xl border-gray-100 bg-gray-50 font-bold h-12">
                            <SelectValue placeholder="Pilih Role" />
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem v-for="role in roles" :key="role.id" :value="role.name" class="rounded-lg">
                                <span class="capitalize font-bold">{{ role.name }}</span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="submit" :disabled="form.processing" class="w-full py-6 bg-gray-900 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-emerald-600 transition-all shadow-xl shadow-gray-100">
                    Simpan Perubahan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
