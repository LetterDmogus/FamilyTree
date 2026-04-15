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
import { Checkbox } from '@/components/ui/checkbox';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
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
                title: 'Role Management',
                href: admin.roles.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Role Name', key: 'name', sortable: true },
    { label: 'Permissions Count', key: 'permissions_count' },
    { label: 'Created At', key: 'created_at', sortable: true },
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
    form.name = role.name;
    form.permissions = role.permissions?.map((p: any) => p.name) || [];
    showModal.value = true;
};

const submit = () => {
    if (editingRole.value) {
        form.put(admin.roles.update({ role: editingRole.value.id }), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(admin.roles.store(), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const togglePermission = (name: string) => {
    if (form.permissions.includes(name)) {
        form.permissions = form.permissions.filter(p => p !== name);
    } else {
        form.permissions.push(name);
    }
};
</script>

<template>
    <Head title="Role Management" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4 md:p-8">
        <CrudTable
            title="Role Management"
            description="Manage authorization roles and their specific permissions."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            @edit="handleEdit"
        >
            <template #actions>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Role
                </Button>
            </template>

            <template #cell(permissions_count)="{ item }">
                {{ item.permissions?.length || 0 }}
            </template>

            <template #cell(created_at)="{ item }">
                {{ new Date(item.created_at).toLocaleDateString() }}
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ editingRole ? 'Edit Role' : 'Create New Role' }}</DialogTitle>
                <DialogDescription>
                    Fill in the role name and select the permissions.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                </div>
                <div class="grid gap-2">
                    <Label>Permissions</Label>
                    <div class="grid grid-cols-2 gap-2 max-h-[200px] overflow-y-auto pr-2">
                        <div v-for="permission in allPermissions" :key="permission.id" class="flex items-center space-x-2">
                            <Checkbox
                                :id="'perm-' + permission.id"
                                :checked="form.permissions.includes(permission.name)"
                                @update:checked="togglePermission(permission.name)"
                            />
                            <label
                                :for="'perm-' + permission.id"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                {{ permission.name }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="submit" :disabled="form.processing">
                    {{ editingRole ? 'Update Role' : 'Create Role' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
