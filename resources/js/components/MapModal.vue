<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Map, MapMarker } from '@/components/ui/map';
import { MapPinIcon } from 'lucide-vue-next';

interface LocationData {
    country?: string;
    province?: string;
    city?: string;
    address?: string;
    lat?: number | string;
    lng?: number | string;
}

const props = defineProps<{
    open: boolean;
    title: string;
    locationData?: LocationData | null;
}>();

const emit = defineEmits(['update:open']);

// Guard: Ensure valid coordinates for MapLibre
const hasValidCoords = computed(() => {
    if (!props.locationData) return false;
    const lng = Number(props.locationData.lng);
    const lat = Number(props.locationData.lat);
    return !isNaN(lng) && !isNaN(lat) && props.locationData.lng !== undefined && props.locationData.lat !== undefined;
});

const mapCenter = computed<[number, number]>(() => {
    return [Number(props.locationData?.lng) || 0, Number(props.locationData?.lat) || 0];
});
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    <div class="flex items-start mt-2">
                        <MapPinIcon class="w-4 h-4 mr-2 mt-0.5 text-muted-foreground" />
                        <span v-if="locationData && typeof locationData === 'object'">
                            {{ locationData.address ? locationData.address + ', ' : '' }}
                            {{ locationData.city ? locationData.city + ', ' : '' }}
                            {{ locationData.province ? locationData.province + ', ' : '' }}
                            {{ locationData.country || '' }}
                            <span v-if="!locationData.city && !locationData.country">{{ locationData }}</span>
                        </span>
                        <span v-else-if="locationData">{{ locationData }}</span>
                        <span v-else>Lokasi tidak tersedia</span>
                    </div>
                </DialogDescription>
            </DialogHeader>
            <div class="h-[400px] w-full rounded-md overflow-hidden border mt-4 bg-muted/5 relative">
                <Map 
                    v-if="hasValidCoords"
                    :zoom="14" 
                    :center="mapCenter"
                >
                    <MapMarker :lng-lat="mapCenter" />
                </Map>
                <div v-else class="flex items-center justify-center h-full bg-muted/10 text-muted-foreground text-xs uppercase font-black tracking-widest">
                    Titik koordinat tidak tersedia pada peta.
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
