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
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Data Detail Management',
                href: admin.dataDetails.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Field Name', key: 'name', sortable: true },
    { label: 'Type', key: 'input_type' },
    { label: 'Created At', key: 'created_at', sortable: true },
];

const routes = {
    index: admin.dataDetails.index,
    destroy: (id: number) => admin.dataDetails.destroy({ dataDetail: id }),
    restore: (id: number) => admin.dataDetails.restore({ dataDetail: id }),
    forceDestroy: (id: number) => admin.dataDetails.forceDestroy({ dataDetail: id }),
};

const showModal = ref(false);
const editingItem = ref<any>(null);

const form = useForm({
    name: '',
    icon_key: '',
    input_type: 'text' as 'text' | 'textarea' | 'date' | 'select',
    options: [] as string[],
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    showModal.value = true;
};

const handleEdit = (item: any) => {
    editingItem.value = item;
    form.name = item.name;
    form.icon_key = item.icon_key || '';
    form.input_type = item.input_type;
    form.options = item.options || [];
    showModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        const routeDef = admin.dataDetails.update({ dataDetail: editingItem.value.id });
        form.put(typeof routeDef === 'string' ? routeDef : routeDef.url, {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        const routeDef = admin.dataDetails.store();
        form.post(typeof routeDef === 'string' ? routeDef : routeDef.url, {
            onSuccess: () => (showModal.value = false),
        });
    }
};
</script>

<template>
    <Head title="Data Detail Management" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4 md:p-8">
        <CrudTable
            title="Data Detail Management"
            description="Manage additional information fields for family member profiles."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            @edit="handleEdit"
        >
            <template #actions>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Field
                </Button>
            </template>

            <template #cell(created_at)="{ item }">
                {{ new Date(item.created_at).toLocaleDateString() }}
            </template>
        </CrudTable>
    </div>

    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ editingItem ? 'Edit Data Detail' : 'Add Data Detail' }}</DialogTitle>
                <DialogDescription>
                    Define a new additional field for family profiles.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Field Name</Label>
                    <Input id="name" v-model="form.name" placeholder="e.g. Occupation" />
                </div>
                <div class="grid gap-2">
                    <Label for="input_type">Input Type</Label>
                    <Select v-model="form.input_type">
                        <SelectTrigger>
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="text">Text</SelectItem>
                            <SelectItem value="textarea">Long Text</SelectItem>
                            <SelectItem value="date">Date</SelectItem>
                            <SelectItem value="select">Dropdown</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="icon_key">Icon Key (optional)</Label>
                    <Input id="icon_key" v-model="form.icon_key" placeholder="e.g. briefcase" />
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="submit" :disabled="form.processing">
                    {{ editingItem ? 'Update' : 'Create' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
