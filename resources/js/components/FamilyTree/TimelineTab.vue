<script setup>
import { User, Calendar } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { computeTimelineLayout } from './timelineEngine'

const props = defineProps({
  tree: { type: Object, required: true }
})

const emit = defineEmits(['node-click'])

const pixelsPerYear = ref(15)
const rowHeight = 60
const barHeight = 36

const layout = computed(() => {
  return computeTimelineLayout(props.tree, {
    pixelsPerYear: pixelsPerYear.value,
    rowHeight,
    barHeight
  })
})

const timelineContainer = ref(null)

// Pan Logic (Horizontal only for timeline usually, but we'll support both)
const isPanning = ref(false)
const startX = ref(0)
const startY = ref(0)
const scrollLeft = ref(0)
const scrollTop = ref(0)

function handleMouseDown(e) {
  if (e.button !== 0 && e.button !== 2) {
return
}

  isPanning.value = true
  startX.value = e.pageX - timelineContainer.value.offsetLeft
  startY.value = e.pageY - timelineContainer.value.offsetTop
  scrollLeft.value = timelineContainer.value.scrollLeft
  scrollTop.value = timelineContainer.value.scrollTop
}

function handleMouseMove(e) {
  if (!isPanning.value) {
return
}

  e.preventDefault()
  const x = e.pageX - timelineContainer.value.offsetLeft
  const y = e.pageY - timelineContainer.value.offsetTop
  timelineContainer.value.scrollLeft = scrollLeft.value - (x - startX.value)
  timelineContainer.value.scrollTop = scrollTop.value - (y - startY.value)
}

function handleMouseUp() {
  isPanning.value = false
}

function getAge(node) {
  if (!node.birthYear) {
return null
}

  return node.deathYear - node.birthYear
}
</script>

<template>
  <div 
    ref="timelineContainer"
    class="w-full h-full overflow-auto bg-[#f8f9fa] relative select-none cursor-grab active:cursor-grabbing"
    @mousedown="handleMouseDown"
    @mousemove="handleMouseMove"
    @mouseup="handleMouseUp"
    @mouseleave="handleMouseUp"
  >
    <div 
      class="relative"
      :style="{ 
        width: `${layout.dimensions.width}px`, 
        height: `${layout.dimensions.height}px`,
        minWidth: '100%'
      }"
    >
      <!-- Decade Grid Lines -->
      <div 
        v-for="decade in layout.decades" 
        :key="decade.year"
        class="absolute top-0 bottom-0 border-l border-gray-200/60 z-0"
        :style="{ left: `${decade.x}px` }"
      >
        <div class="sticky top-0 bg-white/80 backdrop-blur-sm px-2 py-1 text-[10px] font-black text-gray-400 border-b border-gray-100 z-10">
          {{ decade.year }}
        </div>
      </div>

      <!-- Connections (SVG) -->
      <svg 
        class="absolute inset-0 pointer-events-none z-10"
        :width="layout.dimensions.width"
        :height="layout.dimensions.height"
      >
        <g v-for="(link, i) in layout.links" :key="i">
          <!-- Line from parent row to child row -->
          <path 
            :d="`M ${link.x} ${link.parentRow * rowHeight + rowHeight/2 + 40} L ${link.x} ${link.childRow * rowHeight + rowHeight/2 + 40}`"
            stroke="#cbd5e1"
            stroke-width="1.5"
            fill="none"
          />
          <!-- Small dot at parent intersection -->
          <circle :cx="link.x" :cy="link.parentRow * rowHeight + rowHeight/2 + 40" r="3" fill="#cbd5e1" />
        </g>
      </svg>

      <!-- Member Bars -->
      <div 
        v-for="node in layout.nodes" 
        :key="node.id + (node.is_spouse ? '-spouse' : '')"
        class="absolute z-20 transition-all duration-300 group"
        :style="{ 
          left: `${node.x}px`, 
          top: `${node.y}px`, 
          width: `${node.width}px`,
          height: `${barHeight}px`
        }"
        @click.stop="emit('node-click', node)"
      >
        <div 
          class="h-full rounded-full flex items-center px-1.5 shadow-sm border border-white/50 relative overflow-hidden group-hover:shadow-md transition-shadow cursor-pointer"
          :style="{ backgroundColor: node.color }"
          :class="{ 'opacity-80 border-dashed': node.is_estimated }"
        >
          <!-- Photo -->
          <div class="w-7 h-7 rounded-full bg-white/20 border border-white/40 flex-shrink-0 overflow-hidden flex items-center justify-center">
            <img v-if="node.photo_url" :src="node.photo_url" class="w-full h-full object-cover" />
            <User v-else class="w-4 h-4 text-white/60" />
          </div>

          <!-- Name & Info -->
          <div class="ml-2 flex-1 min-w-0 pr-2">
            <p class="text-[10px] font-black text-white truncate leading-tight uppercase tracking-tight">
              {{ node.full_name }}
            </p>
            <p class="text-[8px] font-bold text-white/80 leading-tight">
              {{ node.birthYear }} - {{ node.is_alive ? 'Sekarang' : node.deathYear }}
            </p>
          </div>

          <!-- Age Badge -->
          <div v-if="getAge(node)" class="flex-shrink-0 mr-1 bg-black/10 px-1.5 py-0.5 rounded-full">
             <span class="text-[9px] font-black text-white">{{ getAge(node) }}</span>
          </div>

          <!-- Indicator for estimated data -->
          <div v-if="node.is_estimated" class="absolute top-0 right-2">
             <Calendar class="w-2 h-2 text-white/40" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cursor-grab { cursor: grab; }
.cursor-grabbing { cursor: grabbing; }
</style>
