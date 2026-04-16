<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CrudTable from '@/components/CrudTable.vue';
import { BreadcrumbItem } from '@/types';
import admin from '@/routes/admin';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
    items: {
        data: any[];
        links: any[];
        meta?: any;
    };
    filters: any;
    roles: Array<{ id: number; name: string }>;
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
    { label: 'Name', key: 'name', sortable: true },
    { label: 'Email', key: 'email', sortable: true },
    { label: 'Role', key: 'role_name' },
    { label: 'Joined', key: 'created_at', sortable: true },
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
    if (confirm(`Are you sure you want to reset password for ${user.name}?`)) {
        const routeDef = admin.users.resetPassword({ user: user.id });
        useForm({}).put(typeof routeDef === 'string' ? routeDef : routeDef.url);
    }
};
</script>

<template>
    <Head title="User Management" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4 md:p-8">
        <CrudTable
            title="User Management"
            description="Manage all registered users and their account status."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            show-reset-password
            @edit="handleEdit"
            @reset-password="handleResetPassword"
        >
            <template #cell(role_name)="{ item }">
                <span class="capitalize">{{ item.roles?.[0]?.name || 'member' }}</span>
            </template>

            <template #cell(created_at)="{ item }">
                {{ new Date(item.created_at).toLocaleDateString() }}
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Edit User</DialogTitle>
                <DialogDescription>
                    Update user account details and role.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" v-model="form.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <Select v-model="form.role">
                        <SelectTrigger>
                            <SelectValue placeholder="Select role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                <span class="capitalize">{{ role.name }}</span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="submit" :disabled="form.processing">
                    Update User
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
