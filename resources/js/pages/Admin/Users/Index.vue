<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudTable from '@/components/CrudTable.vue';
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
                title: 'User Management',
                href: admin.users.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Name', key: 'name', sortable: true },
    { label: 'Email', key: 'email', sortable: true },
    { label: 'Joined', key: 'created_at', sortable: true },
];

const routes = {
    index: admin.users.index,
    destroy: (id: number) => admin.users.destroy({ user: id }),
    restore: (id: number) => admin.users.restore({ user: id }),
    forceDestroy: (id: number) => admin.users.forceDestroy({ user: id }),
};

const handleResetPassword = (user: any) => {
    window.location.href = `/settings/password?user=${user.id}`;
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
            @reset-password="handleResetPassword"
        >
            <template #cell(created_at)="{ item }">
                {{ new Date(item.created_at).toLocaleDateString() }}
            </template>
        </CrudTable>
    </div>
</template>
