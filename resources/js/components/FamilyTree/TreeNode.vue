<script setup>
/**
 * TREENODE.VUE
 * Improved with Local Initials Avatar and Toggleable Badges.
 * Using latest Lucide Icons (Mars, Venus, etc.)
 */
import { computed } from 'vue'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { 
  Mars, 
  Venus, 
  Heart, 
  Skull, 
  Briefcase,
  ChevronLeft,
  ChevronRight,
  Layers,
  MoreHorizontal,
  Mail
} from 'lucide-vue-next'

const props = defineProps({
  node: { type: Object, required: true },
  x: { type: Number, required: true },
  y: { type: Number, required: true },
  isActive: { type: Boolean, default: false },
  isExportMode: { type: Boolean, default: false },
  showFullDetails: { type: Boolean, default: true },
  showBadges: { type: Boolean, default: false },
  config: {
    type: Object,
    default: () => ({
      width: 160,
      height: 110,
      borderRadius: 'rounded-xl',
      borderThickness: 'border-2',
      maleActiveColor: 'border-blue-500',
      femaleActiveColor: 'border-pink-500',
    })
  }
})

const emit = defineEmits(['click', 'switch-spouse', 'expand'])

function handleExpand(e) {
  e.stopPropagation()
  emit('expand', props.node.id)
}

function nextSpouse() {
  const nextIdx = (props.node.activeSpouseIndex + 1) % props.node.totalSpouseLayers
  emit('switch-spouse', { nodeId: props.node.id, index: nextIdx })
}

function prevSpouse() {
  const prevIdx = (props.node.activeSpouseIndex - 1 + props.node.totalSpouseLayers) % props.node.totalSpouseLayers
  emit('switch-spouse', { nodeId: props.node.id, index: prevIdx })
}

// Local Avatar Logic
const initials = computed(() => {
  if (!props.node.panggilan) return '?'
  return props.node.panggilan.substring(0, 2).toUpperCase()
})

const avatarStyle = computed(() => {
  const colors = [
    { bg: '#dbeafe', text: '#2563eb' },
    { bg: '#fce7f3', text: '#db2777' },
    { bg: '#d1fae5', text: '#059669' },
    { bg: '#e0e7ff', text: '#4f46e5' },
    { bg: '#fef3c7', text: '#d97706' },
  ]
  return colors[props.node.id % colors.length]
})

const ageInfo = computed(() => {
  if (!props.node.birth_date) return null
  const birth = new Date(props.node.birth_date)
  const end = props.node.is_alive ? new Date() : (props.node.death_date ? new Date(props.node.death_date) : new Date())
  
  let age = end.getFullYear() - birth.getFullYear()
  const m = end.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && end.getDate() < birth.getDate())) {
    age--
  }

  const birthYear = birth.getFullYear()
  const deathYear = props.node.is_alive ? '' : (props.node.death_date ? new Date(props.node.death_date).getFullYear() : '?')
  const years = props.node.is_alive ? `${birthYear}` : `${birthYear} - ${deathYear}`
  
  return { years, age }
})

function getClasses() {
  const isFemale = props.node.gender === 'F'
  const isDeceased = props.node.is_alive === false
  
  return [
    `group absolute p-3 ${props.config.borderRadius} ${props.config.borderThickness} flex flex-col items-center justify-center transition-all duration-300 text-center shadow-sm cursor-pointer overflow-visible`,
    isDeceased ? 'bg-slate-50 grayscale-[0.8] opacity-80' : 'bg-white',
    (props.isActive && !props.isExportMode)
      ? (isFemale ? `${props.config.femaleActiveColor} z-20 scale-110 shadow-xl` : `${props.config.maleActiveColor} z-20 scale-110 shadow-xl`)
      : 'border-slate-800 hover:-translate-y-1 hover:shadow-xl hover:border-slate-900 z-10'
  ]
}

const style = computed(() => ({
  left: `${props.x}px`,
  top: `${props.y}px`,
  width: `${props.config.width}px`,
  height: `${props.config.height}px`
}))
</script>

<template>
  <div 
    :style="style" 
    :class="getClasses()"
    @click="$emit('click', node)"
  >
    <!-- Status Icons Bar -->
    <div v-if="!isExportMode && showBadges" class="absolute -top-2 -left-4 flex flex-col gap-1.5 z-30 pointer-events-auto">
      <TooltipProvider>
        <!-- Gender Icon -->
        <Tooltip :delay-duration="0">
          <TooltipTrigger as-child>
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center border-2 border-white shadow-md', node.gender === 'F' ? 'bg-pink-500 text-white' : 'bg-blue-500 text-white']">
              <Venus v-if="node.gender === 'F'" class="w-4 h-4 stroke-[3]" />
              <Mars v-else class="w-4 h-4 stroke-[3]" />
            </div>
          </TooltipTrigger>
          <TooltipContent side="left">
            <p class="text-[10px] font-bold">{{ node.gender === 'F' ? 'Perempuan' : 'Laki-laki' }}</p>
          </TooltipContent>
        </Tooltip>

        <!-- Life Status Icon -->
        <Tooltip :delay-duration="0">
          <TooltipTrigger as-child>
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center border-2 border-white shadow-md', node.is_alive ? 'bg-emerald-500 text-white' : 'bg-slate-700 text-white']">
              <Heart v-if="node.is_alive" class="w-4 h-4 fill-current stroke-[3]" />
              <Skull v-else class="w-4 h-4 stroke-[2.5]" />
            </div>
          </TooltipTrigger>
          <TooltipContent side="left">
            <p class="text-[10px] font-bold">{{ node.is_alive ? 'Hidup' : 'Meninggal' }}</p>
          </TooltipContent>
        </Tooltip>

        <!-- Work Status Icon -->
        <Tooltip v-if="node.pekerjaan" :delay-duration="0">
          <TooltipTrigger as-child>
            <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 border-white shadow-md bg-amber-500 text-white">
              <Briefcase class="w-3.5 h-3.5 stroke-[3]" />
            </div>
          </TooltipTrigger>
          <TooltipContent side="left">
            <p class="text-[10px] font-bold">{{ node.pekerjaan }}</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>

    <div class="flex flex-col items-center gap-1">
      <img
        v-if="node.photo_url"
        :src="node.photo_url"
        class="w-10 h-10 rounded-full border border-slate-100 shadow-sm object-cover"
        crossorigin="anonymous"
      />
      <div 
        v-else 
        class="w-10 h-10 rounded-full flex items-center justify-center text-[10px] font-black border border-white shadow-sm"
        :style="{ backgroundColor: avatarStyle.bg, color: avatarStyle.text }"
      >
        {{ initials }}
      </div>

      <div class="flex flex-col overflow-hidden w-full px-1">
        <div class="font-bold text-black leading-tight text-xs truncate">{{ node.panggilan }}</div>
        
        <div v-if="ageInfo && (!isExportMode || showFullDetails)" class="flex flex-col mt-0.5">
           <div class="text-[7px] font-black text-slate-500 uppercase tracking-tighter">{{ ageInfo.years }}</div>
           <div class="text-[9px] font-black text-emerald-700 uppercase">{{ ageInfo.age }} TAHUN</div>
        </div>

        <div v-if="!isExportMode || showFullDetails" class="text-[8px] text-slate-500 font-bold opacity-80 uppercase tracking-tighter truncate mt-0.5">{{ node.full_name }}</div>
      </div>
    </div>
    
    <!-- "ME" Badge (TOP RIGHT) -->
    <div v-if="node.panggilan === 'saya' && !isExportMode" class="absolute -top-2 -right-2 w-6 h-6 bg-emerald-600 text-white text-[9px] font-black flex items-center justify-center rounded-full border-2 border-white shadow-lg z-30">
      ME
    </div>

    <!-- Note Indicator (BOTTOM RIGHT) -->
    <div v-if="node.has_note_for_me && !isExportMode" class="absolute -bottom-2 -right-2 w-6 h-6 bg-amber-500 text-white rounded-full border-2 border-white shadow-lg flex items-center justify-center z-30 animate-bounce">
      <Mail class="w-3 h-3 fill-current" />
    </div>

    <!-- More Indicator (BOTTOM) -->
    <div 
      v-if="node.has_more && !isExportMode" 
      @click="handleExpand"
      class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-8 h-4 bg-emerald-600 text-white rounded-full border border-white shadow-md flex items-center justify-center z-20 cursor-pointer hover:scale-110 transition-transform"
    >
      <MoreHorizontal class="w-3 h-3" />
    </div>

    <!-- Spouse Layer Navigation (BOTTOM) -->
    <div v-if="node.type === 'bio' && node.totalSpouseLayers > 1 && !isExportMode" class="absolute -bottom-8 left-1/2 -translate-x-1/2 flex items-center gap-1 px-2 py-1 bg-white border border-slate-200 shadow-lg rounded-full z-30 pointer-events-auto whitespace-nowrap" @click.stop>
      <button @click="prevSpouse" class="p-1 hover:bg-slate-100 rounded-full transition-colors">
        <ChevronLeft class="w-3.5 h-3.5 text-slate-600" />
      </button>
      
      <div class="flex items-center gap-1 px-1">
        <Layers class="w-2.5 h-2.5 text-emerald-600" />
        <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter">{{ node.activeSpouseIndex + 1 }} / {{ node.totalSpouseLayers }}</span>
      </div>

      <button @click="nextSpouse" class="p-0.5 hover:bg-slate-100 rounded-full transition-colors">
        <ChevronRight class="w-3 h-3 text-slate-600" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.group {
  user-select: none;
}
</style>
