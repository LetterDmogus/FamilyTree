<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Eye, PencilLine, RotateCcw, Search, ShieldBan, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Column {
    label: string;
    key: string;
    sortable?: boolean;
}

const props = defineProps<{
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
    };
    routes: {
        index: (params?: any) => string;
        destroy?: (id: any) => string;
        restore?: (id: any) => string;
        forceDestroy?: (id: any) => string;
    };
}>();

const emit = defineEmits<{
    edit: [item: any];
    resetPassword: [item: any];
}>();

const page = usePage();
const isSuperAdmin = computed(() => (page.props.auth.roles as string[]).includes('superadmin'));

const search = ref(props.filters.search || '');
const trashed = ref(props.filters.trashed || 'active');

const debouncedSearch = debounce((value: string) => {
    router.get(props.routes.index({ ...props.filters, search: value, page: 1 }), {}, { preserveState: true, replace: true });
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

const handleTrashedChange = (value: string) => {
    const filterValue = value === 'active' ? '' : value;
    router.get(props.routes.index({ ...props.filters, trashed: filterValue, page: 1 }), {}, { preserveState: true });
};

const handleSort = (key: string) => {
    let newSort = key;
    if (props.filters.sort === key) {
        newSort = `-${key}`;
    } else if (props.filters.sort === `-${key}`) {
        newSort = '';
    }

    router.get(props.routes.index({ ...props.filters, sort: newSort }), {}, { preserveState: true });
};

const deleteItem = (id: number) => {
    if (props.routes.destroy && confirm('Are you sure you want to delete this item?')) {
        router.delete(props.routes.destroy(id));
    }
};

const restoreItem = (id: number) => {
    if (props.routes.restore && confirm('Are you sure you want to restore this item?')) {
        router.put(props.routes.restore(id));
    }
};

const forceDeleteItem = (id: number) => {
    if (props.routes.forceDestroy && confirm('PERMANENT DELETE: This action cannot be undone. Continue?')) {
        router.delete(props.routes.forceDestroy(id));
    }
};
</script>

<template>
    <div class="flex w-full flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold tracking-tight">{{ title }}</h2>
            <p v-if="description" class="text-muted-foreground">{{ description }}</p>
        </div>

        <div class="flex items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" type="search" placeholder="Search..." class="pl-8" />
            </div>

            <div class="flex items-center gap-2">
                <Select :model-value="trashed" @update:model-value="handleTrashedChange">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="active">Active Only</SelectItem>
                        <SelectItem value="with">Include Deleted</SelectItem>
                        <SelectItem value="only">Deleted Only</SelectItem>
                    </SelectContent>
                </Select>

                <slot name="actions"></slot>
            </div>
        </div>

        <div class="w-full overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 transition-colors">
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="h-10 px-4 text-left align-middle font-medium text-muted-foreground"
                            :class="{ 'cursor-pointer hover:text-foreground': column.sortable }"
                            @click="column.sortable ? handleSort(column.key) : null"
                        >
                            <div class="flex items-center gap-2">
                                {{ column.label }}
                                <span v-if="column.sortable && filters.sort === column.key">↑</span>
                                <span v-if="column.sortable && filters.sort === `-${column.key}`">↓</span>
                            </div>
                        </th>
                        <th class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        class="border-b transition-colors hover:bg-muted/50"
                        :class="{ 'opacity-50 italic': item.deleted_at }"
                    >
                        <td v-for="column in columns" :key="column.key" class="p-4 align-middle">
                            <slot :name="`cell(${column.key})`" :item="item">
                                {{ item[column.key] }}
                            </slot>
                        </td>
                        <td class="p-4 align-middle text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    v-if="!item.deleted_at"
                                    variant="ghost"
                                    size="icon"
                                    class="text-sky-600 hover:bg-sky-100 hover:text-sky-700"
                                    @click="$emit('edit', item)"
                                >
                                    <PencilLine class="h-4 w-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>

                                <Button
                                    v-if="!item.deleted_at"
                                    variant="ghost"
                                    size="icon"
                                    class="text-amber-600 hover:bg-amber-100 hover:text-amber-700"
                                    @click="$emit('resetPassword', item)"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                    <span class="sr-only">Reset password</span>
                                </Button>

                                <Button
                                    v-if="!item.deleted_at && routes.destroy"
                                    variant="ghost"
                                    size="icon"
                                    class="text-rose-600 hover:bg-rose-100 hover:text-rose-700"
                                    @click="deleteItem(item.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    <span class="sr-only">Delete</span>
                                </Button>

                                <Button
                                    v-if="item.deleted_at && routes.restore"
                                    variant="ghost"
                                    size="icon"
                                    class="text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700"
                                    @click="restoreItem(item.id)"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                    <span class="sr-only">Restore</span>
                                </Button>

                                <Button
                                    v-if="item.deleted_at && isSuperAdmin && routes.forceDestroy"
                                    variant="ghost"
                                    size="icon"
                                    class="text-red-700 hover:bg-red-100 hover:text-red-800"
                                    @click="forceDeleteItem(item.id)"
                                >
                                    <ShieldBan class="h-4 w-4" />
                                    <span class="sr-only">Hard delete</span>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.data.length === 0">
                        <td :colspan="columns.length + 1" class="p-8 text-center text-muted-foreground">
                            No records found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ items.data.length }} of {{ items.meta?.total || items.data.length }} items
            </div>
            <div class="flex items-center gap-2">
                <Button
                    v-for="(link, index) in items.links"
                    :key="index"
                    variant="outline"
                    size="sm"
                    :disabled="!link.url || link.active"
                    @click="link.url ? router.get(link.url, {}, { preserveState: true }) : null"
                    v-html="link.label"
                    :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                />
            </div>
        </div>
    </div>
</template>
