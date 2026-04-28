<script setup lang="ts">
import { ref, onMounted, shallowRef, computed, inject, Ref, watch } from 'vue'

interface Props {
  lngLat: [number, number]
  color?: string
  draggable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  color: '#3b82f6',
  draggable: false
})

const emit = defineEmits(['dragend'])
const VMarker = shallowRef<any>(null)
const isMounted = ref(false)

// Ambil status load peta dari parent (Map.vue)
const isMapLoaded = inject<Ref<boolean>>('isMapLoaded', ref(false))

// Guard internal untuk data koordinat
const safeLngLat = ref<[number, number] | null>(null)

const validateAndSet = (val: [number, number]) => {
    if (Array.isArray(val) && val.length === 2 && 
        !isNaN(Number(val[0])) && !isNaN(Number(val[1]))) {
        safeLngLat.value = [Number(val[0]), Number(val[1])]
    }
}

// Pantau perubahan koordinat secara manual
watch(() => props.lngLat, (newVal) => {
    validateAndSet(newVal)
}, { immediate: true, deep: true })

onMounted(async () => {
  try {
    const module = await import('@geoql/v-maplibre')
    VMarker.value = module.VMarker
    isMounted.value = true
  } catch (error) {
    console.error('Failed to load MapMarker:', error)
  }
})
</script>

<template>
  <component
    :is="VMarker"
    v-if="isMounted && VMarker && isMapLoaded && safeLngLat"
    :lng-lat="safeLngLat"
    :color="color"
    :draggable="draggable"
    @dragend="(e: any) => emit('dragend', e)"
  />
</template>
