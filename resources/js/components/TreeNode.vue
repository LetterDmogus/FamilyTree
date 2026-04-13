<script setup>
/**
 * TREENODE.VUE - Biological Centering Logic
 *
 * Logic Overview:
 * 1. 3-Column Grid: [Spacer] [Bio Node] [Spouse]
 * 2. This ensures the Bio Node is ALWAYS the horizontal center of the component.
 * 3. All relationship lines (Stems & Bridge) align to this center.
 */
import { computed } from 'vue'

const props = defineProps({
  node: {
    type: Object,
    required: true
  },
  activeId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['node-click'])

// --- CUSTOMIZATION CONFIG ---
const config = {
  nodeWidth: 'w-[160px]', // Fixed width helps with precise math
  borderRadius: 'rounded-xl',
  borderThickness: 'border-2',
  lineColor: 'bg-slate-800',
  lineColorBorder: 'border-slate-800',
  maleActiveColor: 'border-blue-500',
  femaleActiveColor: 'border-pink-500',
}

function handleClick() {
  emit('node-click', props.node)
}

function handleSpouseClick(spouse) {
  emit('node-click', { ...spouse, depth: props.node.depth })
}

function getNodeClasses(personNode) {
  const isFemale = personNode.gender === 'F'
  const active = personNode.id === props.activeId

  return [
    `group relative p-3 ${config.borderRadius} ${config.borderThickness} ${config.nodeWidth}`,
    'cursor-pointer transition-all duration-300 text-center bg-white shadow-sm',
    active
      ? (isFemale ? `${config.femaleActiveColor} z-10 scale-105 shadow-xl` : `${config.maleActiveColor} z-10 scale-105 shadow-xl`)
      : 'border-slate-800 hover:-translate-y-1 hover:shadow-xl hover:border-slate-900'
  ]
}
</script>

<template>
  <div class="flex flex-col items-center text-gray-900">

    <!-- 1. PARENT & SPOUSE ROW -->
    <div class="grid grid-cols-[1fr_auto_1fr] items-start w-full">

      <!-- LEFT COLUMN: Dynamic Spacer -->
      <div :class="['flex justify-end pr-4 h-full', node.depth > 1 ? 'pt-8' : 'pt-0']">
        <div v-if="node.spouse && node.spouse.length > 0" class="invisible pointer-events-none flex items-center">
           <div class="w-8 h-[2px]"></div>
           <div :class="[config.nodeWidth, 'p-3 border-2 h-10']"></div>
        </div>
      </div>

      <!-- CENTER COLUMN: BIOLOGICAL ANCHOR -->
      <div class="flex flex-col items-center relative min-w-[160px]">
        <!-- UP STEM: Incoming from ancestors -->
        <div v-if="node.depth > 1" class="w-[2px] h-8 bg-slate-800 flex-shrink-0"></div>

        <!-- THE MAIN NODE -->
        <div @click="handleClick" :class="getNodeClasses(node)">
          <div class="flex flex-col items-center gap-2">
            <img
              :src="node.photo_url || 'https://ui-avatars.com/api/?name=' + node.panggilan + '&background=f8fafc&color=64748b'"
              class="w-12 h-12 rounded-full border border-slate-100 shadow-sm object-cover"
            />
            <div class="flex flex-col">
              <div class="font-bold text-slate-900 leading-tight text-sm">{{ node.panggilan }}</div>
              <div class="text-[9px] text-slate-400 font-bold opacity-80 uppercase tracking-tight">{{ node.full_name }}</div>
            </div>
          </div>
          <div v-if="node.panggilan === 'saya'" class="absolute -top-2 -right-2 w-5 h-5 bg-blue-600 text-white text-[8px] font-black flex items-center justify-center rounded-full border-2 border-white shadow-sm">
            ME
          </div>
        </div>

        <!-- SINGLE PARENT STEM: Only if NO spouse -->
        <div v-if="(!node.spouse || node.spouse.length === 0) && node.children && node.children.length > 0" class="w-[2px] h-8 bg-slate-800"></div>
        <div v-else class="w-[5px] h-8"></div>
      </div>

      <!-- RIGHT COLUMN: SPOUSES -->
      <div :class="['flex items-start pl-0 h-full', node.depth > 1 ? 'pt-8' : 'pt-0']">
        <template v-for="s in node.spouse" :key="s.id">
          <div class="flex items-center">
            <!-- Marriage Junction -->
            <div class="w-8 h-[2px] bg-slate-800 relative">
              <div class="absolute -top-[4px] left-1/2 -translate-x-1/2 w-[10px] h-[10px] rounded-full border-2 border-slate-800 bg-white z-10">
                <!-- STEM DOWN from Junction -->
                <div v-if="node.children && node.children.length > 0"
                     class="absolute top-[8px] left-1/2 -translate-x-1/2 w-[2px] h-21 bg-slate-800"></div>
              </div>
            </div>

            <div @click="handleSpouseClick(s)" :class="getNodeClasses(s)">
              <div class="flex flex-col items-center gap-2">
                <img
                  :src="s.photo_url || 'https://ui-avatars.com/api/?name=' + s.panggilan + '&background=f8fafc&color=64748b'"
                  class="w-12 h-12 rounded-full border border-slate-100 shadow-sm object-cover"
                />
                <div class="flex flex-col">
                  <div class="font-bold text-slate-900 leading-tight text-sm">{{ s.panggilan }}</div>
                  <div class="text-[9px] text-slate-400 font-bold opacity-80 uppercase tracking-tight">{{ s.full_name }}</div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- 2. CHILDREN ROW -->
    <div v-if="node.children && node.children.length > 0" class="flex">
      <div v-for="(child, index) in node.children" :key="child.id" class="relative flex flex-col items-center">
        <div v-if="node.children.length > 1" class="absolute top-0 left-0 right-0 h-[2px] flex">
          <div :class="['flex-1', index === 0 ? 'bg-transparent' : 'bg-slate-800']"></div>
          <div :class="['flex-1', index === node.children.length - 1 ? 'bg-transparent' : 'bg-slate-800']"></div>
        </div>
        <TreeNode :node="child" :active-id="activeId" @node-click="$emit('node-click', $event)" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.group:hover {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}
</style>
