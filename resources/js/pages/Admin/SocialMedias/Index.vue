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
                title: 'Social Media Management',
                href: admin.socialMedias.index(),
            },
        ],
    },
});

const columns = [
    { label: 'Name', key: 'name', sortable: true },
    { label: 'Prefix (URL)', key: 'prefix', sortable: true },
    { label: 'Icon URL', key: 'icon_url' },
    { label: 'Created At', key: 'created_at', sortable: true },
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
    icon_url: '',
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
    if (editingItem.value) {
        const routeDef = admin.socialMedias.update({ socialMedia: editingItem.value.id });
        form.put(typeof routeDef === 'string' ? routeDef : routeDef.url, {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        const routeDef = admin.socialMedias.store();
        form.post(typeof routeDef === 'string' ? routeDef : routeDef.url, {
            onSuccess: () => (showModal.value = false),
        });
    }
};
</script>

<template>
    <Head title="Social Media Management" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4 md:p-8">
        <CrudTable
            title="Social Media Management"
            description="Manage available social media platforms for user profiles."
            :items="items"
            :columns="columns"
            :filters="filters"
            :routes="routes"
            @edit="handleEdit"
        >
            <template #actions>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Platform
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
                <DialogTitle>{{ editingItem ? 'Edit Social Media' : 'Add Social Media' }}</DialogTitle>
                <DialogDescription>
                    Fill in the details for the social media platform.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" placeholder="e.g. Instagram" />
                </div>
                <div class="grid gap-2">
                    <Label for="prefix">Prefix (URL Pattern)</Label>
                    <Input id="prefix" v-model="form.prefix" placeholder="e.g. instagram.com/" />
                </div>
                <div class="grid gap-2">
                    <Label for="icon_url">Icon URL (optional)</Label>
                    <Input id="icon_url" v-model="form.icon_url" placeholder="URL or icon class" />
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
