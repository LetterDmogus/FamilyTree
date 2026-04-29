<script setup lang="ts">
import { Country, State, City } from 'country-state-city';
import { ref, watch, onMounted, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Map, MapMarker } from '@/components/ui/map';

const props = defineProps<{
    modelValue: any;
}>();

const emit = defineEmits(['update:modelValue']);

const localData = ref({
    country: '',
    province: '',
    city: '',
    address: '',
    lat: -6.200000,
    lng: 106.816666
});

const countries = ref(Country.getAllCountries());
const states = ref<any[]>([]);
const cities = ref<any[]>([]);
const mapZoom = ref(10);

onMounted(() => {
    if (props.modelValue && typeof props.modelValue === 'object') {
        localData.value = { ...localData.value, ...props.modelValue };

        if (localData.value.country) {
            states.value = State.getStatesOfCountry(localData.value.country);

            if (localData.value.province) {
                cities.value = City.getCitiesOfState(localData.value.country, localData.value.province);
            }
        }
    }
});

watch(() => localData.value.country, (newCountry) => {
    if (newCountry) {
        states.value = State.getStatesOfCountry(newCountry);
        
        // Auto-center to country
        const countryData = Country.getCountryByCode(newCountry);

        if (countryData && countryData.latitude && countryData.longitude) {
            localData.value.lat = Number(countryData.latitude);
            localData.value.lng = Number(countryData.longitude);
            mapZoom.value = 5;
        }
    } else {
        states.value = [];
    }

    localData.value.province = '';
    localData.value.city = '';
    updateValue();
});

watch(() => localData.value.province, (newProv) => {
    if (newProv && localData.value.country) {
        cities.value = City.getCitiesOfState(localData.value.country, newProv);

        // Auto-center to province
        const stateData = State.getStateByCodeAndCountry(newProv, localData.value.country);

        if (stateData && stateData.latitude && stateData.longitude) {
            localData.value.lat = Number(stateData.latitude);
            localData.value.lng = Number(stateData.longitude);
            mapZoom.value = 8;
        }
    } else {
        cities.value = [];
    }

    localData.value.city = '';
    updateValue();
});

watch(() => localData.value.city, (newCity) => {
    if (newCity && cities.value.length > 0) {
        const cityData = cities.value.find(c => c.name === newCity);

        if (cityData && cityData.latitude && cityData.longitude) {
            localData.value.lat = Number(cityData.latitude);
            localData.value.lng = Number(cityData.longitude);
            mapZoom.value = 12;
        }
    }

    updateValue();
});

const onMapClick = (e: any) => {
    if (e.lngLat) {
        localData.value.lat = e.lngLat.lat;
        localData.value.lng = e.lngLat.lng;
        updateValue();
    }
};

const updateValue = () => {
    emit('update:modelValue', { ...localData.value });
};

// Validasi koordinat untuk mencegah error peta
const mapCenter = computed<[number, number]>(() => [
    Number(localData.value.lng) || 106.816666, 
    Number(localData.value.lat) || -6.200000
]);

const markerPos = computed<[number, number]>(() => [
    Number(localData.value.lng), 
    Number(localData.value.lat)
]);

const hasValidCoords = computed(() => {
    return !isNaN(Number(localData.value.lng)) && 
           !isNaN(Number(localData.value.lat)) && 
           localData.value.lng !== undefined && 
           localData.value.lat !== undefined;
});
</script>

<template>
    <div class="space-y-4 border p-4 rounded-lg bg-card">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2">
                <Label>Negara</Label>
                <select 
                    v-model="localData.country" 
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="">Pilih Negara</option>
                    <option v-for="c in countries" :key="c.isoCode" :value="c.isoCode">{{ c.name }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <Label>Provinsi</Label>
                <select 
                    v-model="localData.province" 
                    :disabled="!states.length"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="">Pilih Provinsi</option>
                    <option v-for="s in states" :key="s.isoCode" :value="s.isoCode">{{ s.name }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <Label>Kota</Label>
                <select 
                    v-model="localData.city" 
                    :disabled="!cities.length"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="">Pilih Kota</option>
                    <option v-for="city in cities" :key="city.name" :value="city.name">{{ city.name }}</option>
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <Label>Alamat / Detail Tempat</Label>
            <Input v-model="localData.address" placeholder="Contoh: RS Hasan Sadikin, Nama Jalan, dsb." @input="updateValue" />
        </div>

        <div class="space-y-2">
            <Label class="text-xs text-muted-foreground italic">Klik pada peta untuk menentukan titik koordinat presisi</Label>
            <div class="h-[250px] w-full rounded-md overflow-hidden border bg-muted/5 relative">
                <Map 
                    :zoom="mapZoom" 
                    :center="mapCenter" 
                    @click="onMapClick"
                >
                    <MapMarker v-if="hasValidCoords" :lng-lat="markerPos" />
                </Map>
            </div>
            <div class="flex gap-4 text-[10px] text-muted-foreground">
                <span>Lat: {{ Number(localData.lat).toFixed(6) }}</span>
                <span>Lng: {{ Number(localData.lng).toFixed(6) }}</span>
            </div>
        </div>
    </div>
</template>
