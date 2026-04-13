<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import TreeNode from '@/components/TreeNode.vue'
import NodePanel from '@/components/NodePanel.vue'
import MemberModal from '@/components/MemberModal.vue'

const props = defineProps({
  tree: {
    type: Object,
    required: true
  },
  rootUser: {
    type: Object,
    required: true
  },
  master: {
    type: Object,
    required: true
  }
})

const selectedNode = ref(null)
const memberModal = ref({ 
  open: false, 
  mode: 'create', 
  type: 'child', 
  data: null 
})
const treeContainer = ref(null)

// Zoom Logic
const scale = ref(1)
const minScale = 0.2
const maxScale = 3

function zoom(delta) {
  const newScale = scale.value + delta
  if (newScale >= minScale && newScale <= maxScale) {
    scale.value = parseFloat(newScale.toFixed(2))
  }
}

function resetZoom() {
  scale.value = 1
}

function handleWheel(e) {
  // Use Ctrl + Wheel for zooming (standard behavior)
  if (e.ctrlKey) {
    e.preventDefault()
    const delta = e.deltaY > 0 ? -0.1 : 0.1
    zoom(delta)
  }
}

// Panning Logic (Scale Aware)
const isPanning = ref(false)
const startX = ref(0)
const startY = ref(0)
const scrollLeft = ref(0)
const scrollTop = ref(0)

function handleMouseDown(e) {
  if (e.button !== 2) return
  isPanning.value = true
  startX.value = e.pageX - treeContainer.value.offsetLeft
  startY.value = e.pageY - treeContainer.value.offsetTop
  scrollLeft.value = treeContainer.value.scrollLeft
  scrollTop.value = treeContainer.value.scrollTop
  treeContainer.value.style.cursor = 'grabbing'
}

function handleMouseMove(e) {
  if (!isPanning.value) return
  e.preventDefault()
  const x = e.pageX - treeContainer.value.offsetLeft
  const y = e.pageY - treeContainer.value.offsetTop
  
  // Movement adjusted by scale
  const walkX = (x - startX.value) * (1.5 / scale.value)
  const walkY = (y - startY.value) * (1.5 / scale.value)
  
  treeContainer.value.scrollLeft = scrollLeft.value - walkX
  treeContainer.value.scrollTop = scrollTop.value - walkY
}

function handleMouseUp() {
  isPanning.value = false
  if (treeContainer.value) {
    treeContainer.value.style.cursor = 'auto'
  }
}

function handleNodeClick(node) {
  selectedNode.value = node
}

function openCreateModal(type) {
  memberModal.value = { open: true, mode: 'create', type, data: selectedNode.value }
}

function openEditModal(details) {
  memberModal.value = { open: true, mode: 'edit', type: null, data: details }
}

onMounted(() => {
  window.addEventListener('mouseup', handleMouseUp)
  // Register wheel with { passive: false } to allow preventDefault
  treeContainer.value?.addEventListener('wheel', handleWheel, { passive: false })
})

onUnmounted(() => {
  window.removeEventListener('mouseup', handleMouseUp)
  treeContainer.value?.removeEventListener('wheel', handleWheel)
})
</script>

<template>
  <Head title="Family Tree" />

  <div class="flex h-screen bg-white overflow-hidden select-none">
    <!-- Main Tree Area -->
    <div 
      ref="treeContainer"
      class="flex-1 overflow-auto relative bg-[#fcfcfc] pattern-grid"
      @mousedown="handleMouseDown"
      @mousemove="handleMouseMove"
      @contextmenu.prevent
    >
      <!-- Scale Wrapper -->
      <div 
        class="p-80 min-w-max min-h-max flex justify-center items-start transition-transform duration-200 ease-out origin-top"
        :style="{ transform: `scale(${scale})` }"
      >
        <TreeNode 
          :node="tree" 
          :active-id="selectedNode?.id"
          @node-click="handleNodeClick" 
        />
      </div>

      <!-- Floating Controls -->
      <div class="absolute bottom-8 left-8 flex flex-col gap-4 pointer-events-none">
        
        <!-- Zoom Controls -->
        <div class="flex flex-col bg-white/90 backdrop-blur shadow-2xl rounded-2xl border border-gray-100 p-1 pointer-events-auto">
          <button @click="zoom(0.2)" class="p-3 text-gray-600 hover:bg-gray-50 hover:text-blue-600 rounded-xl transition-all" title="Zoom In">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
          </button>
          <div class="h-px bg-gray-100 mx-2"></div>
          <button @click="resetZoom" class="py-2 text-[10px] font-black text-gray-400 hover:text-gray-900 transition-all">
            {{ Math.round(scale * 100) }}%
          </button>
          <div class="h-px bg-gray-100 mx-2"></div>
          <button @click="zoom(-0.2)" class="p-3 text-gray-600 hover:bg-gray-50 hover:text-blue-600 rounded-xl transition-all" title="Zoom Out">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
            </svg>
          </button>
        </div>

        <!-- Help Hints -->
        <div class="p-4 bg-white/90 backdrop-blur shadow-xl rounded-2xl border border-gray-100 text-[10px] text-gray-500 space-y-2">
          <div class="flex items-center gap-2">
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded border border-gray-300 font-bold">Ctrl + Wheel</kbd>
            <span>Zoom silsilah</span>
          </div>
          <div class="flex items-center gap-2">
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded border border-gray-300 font-bold">Klik Kanan</kbd>
            <span>Geser diagram</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Detail Panel -->
    <NodePanel 
      v-if="selectedNode"
      :node="selectedNode"
      :root-id="rootUser.id"
      @add-relation="openCreateModal"
      @edit-profile="openEditModal"
      @close="selectedNode = null"
    />

    <!-- Unified Member Modal (Create/Edit) -->
    <MemberModal 
      v-if="memberModal.open"
      :mode="memberModal.mode"
      :member="memberModal.data"
      :type="memberModal.type"
      :master="master"
      @close="memberModal.open = false"
    />
  </div>
</template>

<style scoped>
.pattern-grid {
  background-image: radial-gradient(#cbd5e1 1.5px, transparent 1px);
  background-size: 50px 50px;
}
.overflow-auto {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.overflow-auto::-webkit-scrollbar {
  display: none;
}
</style>
