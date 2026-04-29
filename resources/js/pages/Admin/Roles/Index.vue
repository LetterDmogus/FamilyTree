<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Shield, CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';
import CrudTable from '@/components/CrudTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    allPermissions: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Manajemen Role',
                href: admin.roles.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Nama Peran', key: 'name', sortable: true },
    { label: 'Izin Fitur', key: 'permissions' },
    { label: 'Dibuat Pada', key: 'created_at', sortable: true },
];

const routes = {
    index: admin.roles.index,
    destroy: (id: number) => admin.roles.destroy({ role: id }),
    restore: (id: number) => admin.roles.restore({ role: id }),
    forceDestroy: (id: number) => admin.roles.forceDestroy({ role: id }),
};

const showModal = ref(false);
const editingRole = ref<any>(null);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const openCreateModal = () => {
    editingRole.value = null;
    form.reset();
    showModal.value = true;
};

const handleEdit = (role: any) => {
    editingRole.value = role;
    form.clearErrors();
    form.name = role.name;
    form.permissions = role.permissions ? role.permissions.map((p: any) => p.name) : [];
    showModal.value = true;
};

const togglePermission = (name: string, checked: boolean) => {
    if (checked) {
        if (!form.permissions.includes(name)) {
            form.permissions = [...form.permissions, name];
        }
    } else {
        form.permissions = form.permissions.filter(p => p !== name);
    }
};

const submit = () => {
    const options = {
        onSuccess: () => (showModal.value = false),
    };
    
    if (editingRole.value) {
        const routeDef = admin.roles.update({ role: editingRole.value.id });
        form.put(typeof routeDef === 'string' ? routeDef : routeDef.url, options);
    } else {
        const routeDef = admin.roles.store();
        form.post(typeof routeDef === 'string' ? routeDef : routeDef.url, options);
    }
};
</script>

<template>
    <Head title="Manajemen Role" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-8 font-sans select-none">
        <CrudTable
            title="Peran & Hak Akses"
            description="Atur pembagian tanggung jawab dan izin fitur untuk setiap kelompok anggota."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            @edit="handleEdit"
        >
            <template #actions>
                <Button @click="openCreateModal" class="bg-gray-900 text-white rounded-xl font-bold text-xs shadow-lg h-12 px-6">
                    <Plus class="mr-2 h-4 w-4 stroke-[3]" />
                    Tambah Peran
                </Button>
            </template>

            <!-- Slot: Role Name -->
            <template #cell(name)="{ item }">
                <div class="flex items-center gap-3">
                    <div :class="['p-2 rounded-lg border shadow-sm', item.name === 'superadmin' ? 'bg-rose-50 text-rose-600' : 'bg-gray-50 text-gray-400']">
                        <Shield class="h-4 w-4" />
                    </div>
                    <span class="font-black text-gray-900 tracking-tight text-sm capitalize">{{ item.name }}</span>
                </div>
            </template>

            <!-- Slot: Permissions -->
            <template #cell(permissions)="{ item }">
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="bg-slate-100 text-slate-600 border-none text-[10px] font-bold px-2 py-0.5">
                        {{ item.permissions?.length || 0 }} Izin
                    </Badge>
                    <div class="flex -space-x-1 overflow-hidden">
                        <div v-for="i in Math.min(item.permissions?.length || 0, 3)" :key="i" class="w-4 h-4 rounded-full bg-white border border-gray-200 flex items-center justify-center text-[7px] font-bold text-gray-400">
                            {{ item.permissions[i-1].name.charAt(0).toUpperCase() }}
                        </div>
                        <div v-if="(item.permissions?.length || 0) > 3" class="text-[9px] font-bold text-gray-400 ml-1.5">
                            +{{ item.permissions.length - 3 }}
                        </div>
                    </div>
                </div>
            </template>

            <template #cell(created_at)="{ item }">
                <span class="text-xs font-semibold text-gray-400 tracking-tight">
                    {{ new Date(item.created_at).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                </span>
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[500px] rounded-[2.5rem] p-8">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black tracking-tighter text-gray-900">
                    {{ editingRole ? 'Edit Peran' : 'Buat Peran' }}
                </DialogTitle>
                <DialogDescription class="text-sm font-medium text-gray-400 mt-1">
                    Tentukan nama peran dan atur izin akses fiturnya.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-8 py-6 text-left">
                <!-- Role Name -->
                <div class="grid gap-2">
                    <Label for="name" class="text-xs font-bold text-gray-500 px-1">Nama Peran</Label>
                    <Input id="name" v-model="form.name" placeholder="Contoh: Editor" class="rounded-xl border-gray-100 bg-gray-50 font-bold focus:bg-white transition-all py-6" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 font-bold px-1">{{ form.errors.name }}</p>
                </div>

                <!-- Permissions Grid -->
                <div class="grid gap-4">
                    <div class="flex items-center justify-between px-1">
                        <Label class="text-xs font-bold text-gray-500">Izin Akses (Permissions)</Label>
                        <Badge class="bg-emerald-50 text-emerald-600 border-none text-[10px] font-bold">{{ form.permissions.length }} Dipilih</Badge>
                    </div>

                    <div class="grid grid-cols-1 gap-2 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                        <div 
                            v-for="permission in allPermissions" 
                            :key="permission.id" 
                            @click="togglePermission(permission.name, !form.permissions.includes(permission.name))"
                            :class="['flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer group', 
                                form.permissions.includes(permission.name) ? 'border-emerald-600 bg-emerald-50/50' : 'border-gray-50 bg-gray-50 hover:border-gray-200']"
                        >
                            <div class="flex items-center gap-3">
                                <Checkbox
                                    :id="'perm-' + permission.id"
                                    :checked="form.permissions.includes(permission.name)"
                                    class="border-2 rounded-md h-5 w-5"
                                    @click.stop
                                    @update:checked="(val) => togglePermission(permission.name, val)"
                                />
                                <span :class="['text-sm font-bold transition-colors capitalize', form.permissions.includes(permission.name) ? 'text-emerald-700' : 'text-gray-600']">
                                    {{ permission.name.replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <CheckCircle2 v-if="form.permissions.includes(permission.name)" class="h-5 w-5 text-emerald-600 animate-in zoom-in duration-300" />
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="submit" @click="submit" :disabled="form.processing" class="w-full py-7 bg-gray-900 text-white rounded-3xl font-bold text-sm hover:bg-emerald-600 transition-all shadow-xl shadow-gray-100">
                    {{ editingRole ? 'Simpan Perubahan' : 'Buat Peran Sekarang' }}
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
