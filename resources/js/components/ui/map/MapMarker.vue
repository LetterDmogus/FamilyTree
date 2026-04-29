<script setup lang="ts">
import { onMounted, onUnmounted, inject, Ref, watch } from 'vue'

let leafletModule: typeof import('leaflet') | null = null

async function loadLeaflet(): Promise<typeof import('leaflet')> {
  if (leafletModule) {
    return leafletModule
  }

  leafletModule = await import('leaflet')
  return leafletModule
}

interface Props {
  lngLat: [number, number]
  color?: string
  draggable?: boolean
  popup?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  color: '#3b82f6',
  draggable: false,
  popup: null
})

const emit = defineEmits(['dragend'])

// Inject the map instance provided by Map.vue
const map = inject<Ref<import('leaflet').Map | null>>('leafletMap')
const isMapLoaded = inject<Ref<boolean>>('isMapLoaded')

let marker: import('leaflet').Marker | null = null

const createMarker = async () => {
  if (!map?.value || !props.lngLat) return

  const L = await loadLeaflet()

  // Leaflet expects [lat, lng], we swap from our [lng, lat]
  const latLng: import('leaflet').LatLngExpression = [props.lngLat[1], props.lngLat[0]]
  
  marker = L.marker(latLng, {
    draggable: props.draggable
  }).addTo(map.value)

  if (props.popup) {
    marker.bindPopup(props.popup)
  }

  if (props.draggable) {
    marker.on('dragend', (e) => {
      const target = e.target as import('leaflet').Marker
      const newLatLng = target.getLatLng()
      // Emit in format compatible with MapLibre event payload
      emit('dragend', {
        target: {
          getLngLat: () => ({
            lng: newLatLng.lng,
            lat: newLatLng.lat
          })
        }
      })
    })
  }
}

onMounted(() => {
  if (isMapLoaded?.value) {
    createMarker()
  }
})

// Wait for map to be loaded before adding marker
watch(() => isMapLoaded?.value, (loaded) => {
  if (loaded && !marker) {
    createMarker()
  }
})

// React to coordinate changes
watch(() => props.lngLat, (newLngLat) => {
  if (marker && newLngLat) {
    marker.setLatLng([newLngLat[1], newLngLat[0]])
  }
}, { deep: true })

onUnmounted(() => {
  if (marker) {
    marker.remove()
  }
})
</script>

<template>
  <!-- Marker is managed programmatically via Leaflet API -->
</template>
