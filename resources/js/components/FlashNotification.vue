<script setup lang="ts">
import { watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const page = usePage();

const showToasts = () => {
    const flash = page.props.flash as any;
    const errors = page.props.errors as any;
    
    if (flash?.success) {
        toast.success(flash.success);
    }

    if (flash?.error) {
        toast.error(flash.error);
    }

    // Handle validation errors from Inertia
    const errorKeys = Object.keys(errors);
    if (errorKeys.length > 0) {
        // If it's a specific custom error from the backend (like our 'error' key in withErrors)
        if (errors.error) {
            toast.error(errors.error);
        } else {
            toast.error(`Terdapat ${errorKeys.length} kesalahan pada input Anda.`);
        }
    }
};

// Listen for flash and error changes
watch(() => [page.props.flash, page.props.errors], () => {
    showToasts();
}, { deep: true });

// Check on initial mount
onMounted(() => {
    showToasts();
});
</script>

<template>
    <!-- This component doesn't render anything, it just handles the logic -->
    <div v-if="false"></div>
</template>
