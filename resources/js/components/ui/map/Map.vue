<script setup lang="ts">
import { ref, onMounted, shallowRef, provide } from 'vue'
import '@geoql/v-maplibre/dist/v-maplibre.css'
import 'maplibre-gl/dist/maplibre-gl.css'

interface Props {
  center?: [number, number]
  zoom?: number
  mapStyle?: string
}

const props = withDefaults(defineProps<Props>(), {
  center: () => [106.8272, -6.1751],
  zoom: 12,
  mapStyle: 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json'
})

const emit = defineEmits(['click', 'load'])

const isMounted = ref(false)
const isLoaded = ref(false)
const VMap = shallowRef<any>(null)

// Sediakan status load peta ke komponen anak (Marker)
provide('isMapLoaded', isLoaded)

onMounted(async () => {
  try {
    const module = await import('@geoql/v-maplibre')
    VMap.value = module.VMap
    isMounted.value = true
  } catch (error) {
    console.error('Failed to load map library:', error)
  }
})

const handleLoad = (e: any) => {
  console.log('Map successfully loaded')
  isLoaded.value = true
  emit('load', e)
}
</script>

<template>
  <div class="h-full w-full bg-slate-100 relative overflow-hidden min-h-[200px]">
    <component
      :is="VMap"
      v-if="isMounted && VMap"
      :center="center"
      :zoom="zoom"
      :map-style="mapStyle"
      :style-url="mapStyle"
      :mapStyle="mapStyle"
      class="h-full w-full absolute inset-0"
      @click="(e: any) => emit('click', e)"
      @load="handleLoad"
    >
      <slot v-if="isLoaded" />
    </component>
    <div v-else class="h-full w-full animate-pulse bg-slate-200 flex items-center justify-center text-[10px] uppercase font-black tracking-widest text-slate-400">
      Memuat Peta...
    </div>
  </div>
</template>

<style>
/* Memastikan container pembungkus benar-benar mengambil ruang */
.v-maplibre-container {
  width: 100% !important;
  height: 100% !important;
  display: block !important;
}

/* Memastikan canvas MapLibre tidak berukuran 0px */
.maplibregl-canvas {
  width: 100% !important;
  height: 100% !important;
  outline: none;
}
</style>
