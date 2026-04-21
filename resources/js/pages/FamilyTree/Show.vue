<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { toPng } from 'html-to-image'
import { 
  Share2, 
  Database, 
  Tags, 
  Monitor, 
  Download, 
  Focus, 
  Plus, 
  Minus,
  ImageIcon,
  Sparkles,
  BrainCircuit,
  ShieldCheck
} from 'lucide-vue-next'

// New FamilyTree Components
import { computeLayout } from '@/components/FamilyTree/layoutEngine'
import TreeCanvas from '@/components/FamilyTree/TreeCanvas.vue'
import FTNode from '@/components/FamilyTree/TreeNode.vue'
import ExportModal from '@/components/FamilyTree/ExportModal.vue'
import WiseMysticalTree from '@/components/FamilyTree/WiseMysticalTree.vue'

// UI Components
import NodePanel from '@/components/NodePanel.vue'
import MemberModal from '@/components/MemberModal.vue'
import { useSidePanels } from '@/composables/useSidePanels'

const props = defineProps({
  tree: { type: Object, required: true },
  rootUser: { type: Object, required: true },
  master: { type: Object, required: true }
})

const page = usePage()
const isViewerAdmin = computed(() => {
  const user = page.props.auth.user
  return user?.is_admin || user?.roles?.some(r => ['admin', 'superadmin'].includes(r.name))
})

const { isAiPanelOpen, toggleAiPanel, closeAiPanel } = useSidePanels()

// --- DATA & LAYOUT ---
const layout = ref(null)
function updateLayout() {
  layout.value = computeLayout(props.tree, {
    nodeWidth: 160,
    nodeHeight: 110,
    gapX: 60,
    gapY: 200,
    spouseGap: 40
  })
}
watch(() => props.tree, updateLayout, { immediate: true })

// --- INTERACTION STATE ---
const selectedNode = ref(null)
const showControls = ref(true)
const showBadges = ref(false)
const memberModal = ref({ open: false, mode: 'create', type: 'child', data: null })
const treeContainer = ref(null)

// Ensure AI panel and NodePanel don't overlap
watch(isAiPanelOpen, (val) => {
  if (val) selectedNode.value = null
})
watch(selectedNode, (val) => {
  if (val) closeAiPanel()
})

// --- EXPORT STATE ---
const exportModalOpen = ref(false)
const isExportMode = ref(false)
const showFullDetails = ref(true)
const exportTitle = ref('')
const exportProgress = ref(0)
const exportStatus = ref('')

// --- CONTEXT MENU STATE ---
const contextMenu = ref({ show: false, x: 0, y: 0 })
function closeContextMenu() { contextMenu.value.show = false }

// --- EXPORT LOGIC ---
async function handleStartExport(settings) {
  exportModalOpen.value = false
  isExportMode.value = true
  exportTitle.value = settings.title
  exportProgress.value = 10
  exportStatus.value = 'Mempersiapkan silsilah...'
  showFullDetails.value = settings.details === 'full'
  await nextTick()
  await new Promise(r => setTimeout(r, 500))
  exportProgress.value = 50
  exportStatus.value = `Merender gambar ${settings.quality}x...`
  try {
    const element = document.getElementById('tree-capture-area')
    const options = {
      pixelRatio: settings.quality,
      skipFonts: true,
      style: { transform: 'scale(1)', transformOrigin: 'top left', padding: settings.title ? '100px 50px 50px 50px' : '50px' }
    }
    if (settings.background === 'white') options.backgroundColor = '#ffffff'
    else if (settings.background === 'transparent') options.backgroundColor = null
    const dataUrl = await toPng(element, options)
    exportProgress.value = 90
    exportStatus.value = 'Menyelesaikan berkas...'
    const link = document.createElement('a')
    link.download = `silsilah-${settings.title || 'keluarga'}.png`
    link.href = dataUrl
    link.click()
    exportProgress.value = 100
    exportStatus.value = 'Silsilah berhasil diunduh!'
    await new Promise(r => setTimeout(r, 1000))
  } catch (err) {
    console.error('Export failed:', err)
    alert('Gagal mengekspor gambar.')
  } finally {
    isExportMode.value = false
    exportProgress.value = 0
    exportStatus.value = ''
  }
}

// Zoom & Pan Logic
const scale = ref(1)
function zoom(delta) {
  const newScale = scale.value + delta
  if (newScale >= 0.2 && newScale <= 3) scale.value = parseFloat(newScale.toFixed(2))
}
function resetZoom() { scale.value = 1 }
function handleWheel(e) { if (e.ctrlKey) { e.preventDefault(); zoom(e.deltaY > 0 ? -0.1 : 0.1) } }

const isPanning = ref(false)
const mouseDownPos = ref({ x: 0, y: 0 })
const mouseDownTime = ref(0)
const startX = ref(0)
const startY = ref(0)
const scrollLeft = ref(0)
const scrollTop = ref(0)

function handleMouseDown(e) {
  if (e.button !== 2) return
  mouseDownTime.value = Date.now()
  mouseDownPos.value = { x: e.pageX, y: e.pageY }
  startX.value = e.pageX - treeContainer.value.offsetLeft
  startY.value = e.pageY - treeContainer.value.offsetTop
  scrollLeft.value = treeContainer.value.scrollLeft
  scrollTop.value = treeContainer.value.scrollTop
  closeContextMenu()
}

function handleMouseMove(e) {
  if (mouseDownTime.value === 0) return
  const dx = Math.abs(e.pageX - mouseDownPos.value.x)
  const dy = Math.abs(e.pageY - mouseDownPos.value.y)
  if (!isPanning.value && (dx > 5 || dy > 5)) { isPanning.value = true; treeContainer.value.style.cursor = 'grabbing' }
  if (!isPanning.value) return
  const x = e.pageX - treeContainer.value.offsetLeft
  const y = e.pageY - treeContainer.value.offsetTop
  treeContainer.value.scrollLeft = scrollLeft.value - (x - startX.value) * (1.5 / scale.value)
  treeContainer.value.scrollTop = scrollTop.value - (y - startY.value) * (1.5 / scale.value)
}

function handleMouseUp(e) {
  if (e.button === 2) {
    const duration = Date.now() - mouseDownTime.value
    if (!isPanning.value && duration < 500 && isViewerAdmin.value) {
      contextMenu.value = { show: true, x: e.clientX, y: e.clientY }
    }
  }
  isPanning.value = false
  mouseDownTime.value = 0
  if (treeContainer.value) treeContainer.value.style.cursor = 'auto'
}

function handleNodeClick(node) { selectedNode.value = node; closeContextMenu() }
function openCreateModal(type) { memberModal.value = { open: true, mode: 'create', type, data: selectedNode.value } }
function openEditModal(details) { memberModal.value = { open: true, mode: 'edit', type: null, data: details } }

onMounted(() => {
  window.addEventListener('mouseup', handleMouseUp)
  window.addEventListener('click', closeContextMenu)
  treeContainer.value?.addEventListener('wheel', handleWheel, { passive: false })
})
onUnmounted(() => {
  window.removeEventListener('mouseup', handleMouseUp)
  window.removeEventListener('click', closeContextMenu)
})
</script>

<template>
  <div class="flex h-screen bg-white overflow-hidden select-none font-sans relative text-gray-900">
    <Head title="Family Tree" />
    
    <!-- Export Loading Overlay -->
    <transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-300 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="exportProgress > 0" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-md">
        <div class="w-80 space-y-8 text-center">
           <ImageIcon class="w-16 h-16 text-blue-400 mx-auto animate-bounce" />
           <div class="space-y-4">
              <div class="space-y-1.5">
                <p class="text-[10px] font-black text-white uppercase tracking-[0.3em]">{{ exportStatus }}</p>
                <p class="text-[8px] font-bold text-blue-400 uppercase tracking-widest">{{ exportProgress }}% SELESAI</p>
              </div>
              <div class="h-1.5 w-full bg-white/10 rounded-full overflow-hidden border border-white/5 p-[1px]">
                 <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-400 rounded-full transition-all duration-500 ease-out" :style="{ width: exportProgress + '%' }"></div>
              </div>
           </div>
        </div>
      </div>
    </transition>

    <div 
      ref="treeContainer"
      class="flex-1 overflow-auto relative bg-[#fcfcfc] pattern-grid"
      @mousedown="handleMouseDown"
      @mousemove="handleMouseMove"
      @contextmenu.prevent
    >
      <div 
        v-if="layout"
        id="tree-capture-area"
        class="relative min-w-max min-h-max transition-transform duration-200 ease-out origin-top-left flex flex-col items-center"
        :class="{ 'bg-[#fcfcfc] pattern-grid': !isExportMode || (isExportMode && showFullDetails) }"
        :style="{ 
          transform: isExportMode ? 'scale(1)' : `scale(${scale})`,
          width: `${layout.dimensions.width}px`,
          height: `${layout.dimensions.height + (isExportMode && exportTitle ? 200 : 0)}px`
        }"
      >
        <div v-if="isExportMode && exportTitle" class="w-full text-center pt-32 pb-12">
           <h1 class="text-5xl font-black text-gray-900 tracking-tighter">{{ exportTitle }}</h1>
           <div class="mt-4 h-1 w-48 bg-indigo-600 mx-auto rounded-full"></div>
           <p class="mt-3 text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em]">Dokumen Silsilah Keluarga Resmi</p>
        </div>

        <div class="relative w-full h-full">
          <TreeCanvas :root-node="tree" :positions="layout.positions" :dimensions="layout.dimensions" />
          <div class="absolute top-0 left-0" :style="{ transform: `translate(${layout.dimensions.offsetX}px, ${layout.dimensions.offsetY}px)` }">
            <FTNode 
              v-for="node in layout.flattenedNodes" 
              :key="node.id + '-' + node.type"
              :node="node" :x="node.x" :y="node.y"
              :is-active="selectedNode?.id === node.id"
              :is-export-mode="isExportMode"
              :show-full-details="showFullDetails"
              :show-badges="showBadges"
              @click="handleNodeClick"
            />
          </div>
        </div>
      </div>

      <!-- Compact Context Menu (Unique Icons) -->
      <div 
        v-if="contextMenu.show" 
        class="fixed z-50 bg-white border border-gray-100 shadow-2xl rounded-2xl py-1.5 w-56 animate-in zoom-in-95 duration-100 divide-y divide-gray-50"
        :style="{ left: `${contextMenu.x}px`, top: `${contextMenu.y}px` }"
      >
        <div class="py-1">
          <Link href="/admin/social-medias" class="w-full flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
            <Share2 class="w-3.5 h-3.5 opacity-60" />
            <span class="text-[9px] font-bold uppercase tracking-widest">Kelola Sosial Media</span>
          </Link>
          <Link href="/admin/data-details" class="w-full flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
            <Database class="w-3.5 h-3.5 opacity-60" />
            <span class="text-[9px] font-bold uppercase tracking-widest">Kelola Bidang Data</span>
          </Link>
        </div>
        
        <div class="py-1">
          <button @click="showBadges = !showBadges" class="w-full flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-black transition-all text-left">
            <Tags class="w-3.5 h-3.5 opacity-60" />
            <span class="text-[9px] font-bold uppercase tracking-widest">{{ showBadges ? 'Sembunyikan' : 'Tampilkan' }} Badge</span>
          </button>
          <button @click="showControls = !showControls" class="w-full flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 hover:text-black transition-all text-left">
            <Monitor class="w-3.5 h-3.5 opacity-60" />
            <span class="text-[9px] font-bold uppercase tracking-widest">{{ showControls ? 'Sembunyikan' : 'Tampilkan' }} UI</span>
          </button>
        </div>

        <div class="py-1">
          <button @click="exportModalOpen = true" class="w-full flex items-center gap-3 px-4 py-2.5 text-indigo-600 hover:bg-indigo-50 transition-all text-left font-black group">
            <Download class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" />
            <span class="text-[9px] font-black uppercase tracking-widest">Export Gambar</span>
          </button>
        </div>

        <div class="py-1">
          <button @click="resetZoom" class="w-full flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-all text-left">
            <Focus class="w-3.5 h-3.5 opacity-60" />
            <span class="text-[9px] font-bold uppercase tracking-widest">Reset Zoom (100%)</span>
          </button>
        </div>
      </div>

      <!-- Floating Controls (Emoji Shortcuts) -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="transform translate-y-0 opacity-100" leave-to-class="transform translate-y-4 opacity-0">
        <div v-if="showControls && !isExportMode" :class="['fixed bottom-8 right-8 flex items-end gap-4 z-30 transition-all duration-300', selectedNode || isAiPanelOpen || exportModalOpen ? 'mr-96' : '']">
          <div class="flex flex-col gap-4 items-end">
            <!-- Wise Mystical Tree Trigger -->
            <button @click="toggleAiPanel" class="p-4 bg-emerald-600 text-white rounded-[2rem] shadow-2xl hover:bg-emerald-700 hover:scale-105 transition-all group relative border-4 border-white">
                <Sparkles v-if="!isAiPanelOpen" class="w-6 h-6" />
                <BrainCircuit v-else class="w-6 h-6 animate-pulse" />
                <span class="absolute right-full mr-4 top-1/2 -translate-y-1/2 px-4 py-2 bg-emerald-900/90 backdrop-blur text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl opacity-0 group-hover:opacity-100 transition-all scale-90 group-hover:scale-100 whitespace-nowrap pointer-events-none shadow-xl border border-emerald-400/30">Wise Mystical Tree</span>
            </button>

            <div class="p-5 bg-white/90 backdrop-blur shadow-2xl rounded-[2rem] border border-gray-100 text-[10px] text-gray-500 space-y-3">
              <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-gray-100 rounded-lg border border-gray-300 font-black text-gray-900 min-w-[60px] text-center italic text-[9px]">Right Click</kbd>
                <span class="font-bold">Admin Menu / Pan</span>
              </div>
              <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-gray-100 rounded-lg border border-gray-300 font-black text-gray-900 min-w-[32px] text-center text-[9px]">Ctrl</kbd>
                <span class="font-bold">Zoom In/Out (Wheel)</span>
              </div>
              <div class="flex items-center gap-3 text-indigo-500">
                <ShieldCheck class="w-3 h-3" />
                <span class="font-bold uppercase tracking-widest text-[8px]">Administrator Active</span>
              </div>
            </div>
          </div>
          <div class="flex flex-col bg-white/90 backdrop-blur shadow-2xl rounded-2xl border border-gray-100 p-1">
            <button @click="zoom(0.2)" class="p-3 text-gray-600 hover:bg-gray-50 hover:text-blue-600 rounded-xl transition-all"><Plus class="w-4 h-4" /></button>
            <div class="h-px bg-gray-100 mx-2"></div>
            <button @click="resetZoom" class="py-2 text-[10px] font-black text-gray-400 hover:text-gray-900 transition-all">{{ Math.round(scale * 100) }}%</button>
            <div class="h-px bg-gray-100 mx-2"></div>
            <button @click="zoom(-0.2)" class="p-3 text-gray-600 hover:bg-gray-50 hover:text-blue-600 rounded-xl transition-all"><Minus class="w-4 h-4" /></button>
          </div>
        </div>
      </transition>
    </div>

    <ExportModal :open="exportModalOpen" @close="exportModalOpen = false" @start-export="handleStartExport" />
    <NodePanel v-if="selectedNode" :node="selectedNode" :root-id="rootUser.id" @add-relation="openCreateModal" @edit-profile="openEditModal" @close="selectedNode = null" />
    <WiseMysticalTree :tree-data="tree" />
    <MemberModal v-if="memberModal.open" :mode="memberModal.mode" :member="memberModal.data" :type="memberModal.type" :master="master" @close="memberModal.open = false" />
  </div>
</template>

<style scoped>
.pattern-grid { background-image: radial-gradient(#cbd5e1 1.5px, transparent 1px); background-size: 50px 50px; }
.overflow-auto { scrollbar-width: none; -ms-overflow-style: none; }
.overflow-auto::-webkit-scrollbar { display: none; }
</style>
