<script setup lang="ts">
import { ref, onMounted, onUnmounted, provide, watch, useTemplateRef } from 'vue'
import 'leaflet/dist/leaflet.css'

let leafletModule: typeof import('leaflet') | null = null

async function loadLeaflet(): Promise<typeof import('leaflet')> {
  if (leafletModule) {
    return leafletModule
  }

  leafletModule = await import('leaflet')

  const L = leafletModule

  // Fix default icon path issue in Leaflet with bundlers
  // @ts-ignore
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
  })

  return L
}

interface Props {
  center?: [number, number] // [lng, lat] to maintain compatibility with existing props
  zoom?: number
}

const props = withDefaults(defineProps<Props>(), {
  center: () => [106.8272, -6.1751],
  zoom: 12
})

const emit = defineEmits(['click', 'load'])

const mapContainer = useTemplateRef<HTMLDivElement>('mapContainer')
const map = ref<import('leaflet').Map | null>(null)
const isLoaded = ref(false)

// Provide map instance and load status to child components (e.g., MapMarker)
provide('leafletMap', map)
provide('isMapLoaded', isLoaded)

onMounted(async () => {
  if (!mapContainer.value) return

  // Leaflet expects [lat, lng], so we swap from our [lng, lat] prop
  const L = await loadLeaflet()

  const latLng: import('leaflet').LatLngExpression = [props.center[1], props.center[0]]
  
  map.value = L.map(mapContainer.value, {
    center: latLng,
    zoom: props.zoom,
    zoomControl: false
  })

  // Use a clean, modern base map (Carto Voyager matches the previous style well)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 20
  }).addTo(map.value)

  L.control.zoom({ position: 'bottomright' }).addTo(map.value)

  map.value.on('click', (e) => {
    // Emit in { lngLat: { lng, lat } } format to maintain compatibility with previous logic
    emit('click', {
      lngLat: {
        lng: e.latlng.lng,
        lat: e.latlng.lat
      }
    })
  })

  isLoaded.value = true
  emit('load', map.value)
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})

watch(() => props.center, (newCenter) => {
  if (map.value && newCenter) {
    map.value.setView([newCenter[1], newCenter[0]], map.value.getZoom())
  }
}, { deep: true })

watch(() => props.zoom, (newZoom) => {
  if (map.value && newZoom !== undefined) {
    map.value.setZoom(newZoom)
  }
})
</script>

<template>
  <div class="h-full w-full bg-slate-100 relative overflow-hidden min-h-[200px]">
    <div ref="mapContainer" class="h-full w-full absolute inset-0 z-0"></div>
    <slot v-if="isLoaded" />
  </div>
</template>

<style>
/* Leaflet specific style overrides to match UI theme */
.leaflet-container {
  font-family: inherit;
  background-color: #f1f5f9; /* slate-100 */
}
.leaflet-control-zoom {
  border: none !important;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1) !important;
  border-radius: 8px !important;
  overflow: hidden;
}
.leaflet-control-zoom-in, .leaflet-control-zoom-out {
  background-color: white !important;
  color: #64748b !important; /* slate-500 */
  border-bottom: 1px solid #f1f5f9 !important;
  width: 32px !important;
  height: 32px !important;
  line-height: 32px !important;
}
.leaflet-control-zoom-in:hover, .leaflet-control-zoom-out:hover {
  background-color: #f8fafc !important;
  color: #0f172a !important; /* slate-900 */
}
.leaflet-bar a:last-child {
  border-bottom: none !important;
}
</style>
