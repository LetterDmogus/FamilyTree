<script setup>
/**
 * TREECANVAS.VUE
 * Renders all connecting lines using SVG based on global coordinates.
 */
import { computed } from 'vue'

const props = defineProps({
  rootNode: {
    type: Object,
    required: true
  },
  positions: {
    type: Object,
    required: true
  },
  dimensions: {
    type: Object,
    required: true
  },
  config: {
    type: Object,
    default: () => ({
      nodeWidth: 160,
      nodeHeight: 100,
      spouseGap: 40,
      lineColor: '#1e293b',
      lineWidth: 2,
      junctionRadius: 5
    })
  }
})

// Recursive function to collect all connection paths
function collectPaths(node, paths = []) {
  const currentPos = props.positions[node.id]
  if (!currentPos) return paths

  const nodeWidth = props.config.nodeWidth
  const nodeHeight = props.config.nodeHeight
  const spouseGap = props.config.spouseGap

  // 1. Marriage Line & Junction
  let junctionX = currentPos.x + nodeWidth / 2
  
  if (node.spouse && node.spouse.length > 0) {
    const firstSpousePos = props.positions[node.spouse[0].id]
    if (firstSpousePos) {
      // Line between Bio and Spouse
      paths.push({
        type: 'marriage',
        x1: currentPos.x + nodeWidth,
        y1: currentPos.y + nodeHeight / 2,
        x2: firstSpousePos.x,
        y2: firstSpousePos.y + nodeHeight / 2
      })
      // Update junction to be in the middle of the marriage bridge
      junctionX = (currentPos.x + nodeWidth + firstSpousePos.x) / 2
    }
  }

  // 2. Descent Stem (Vertical from Junction)
  if (node.children && node.children.length > 0) {
    const firstChildY = props.positions[node.children[0].id].y
    const stemBottomY = firstChildY - (firstChildY - currentPos.y - nodeHeight) / 2
    
    // Line down from parent/junction
    paths.push({
      type: 'stem-down',
      x1: junctionX,
      y1: currentPos.y + nodeHeight / 2,
      x2: junctionX,
      y2: stemBottomY
    })

    // 3. Sibling Bridge (Horizontal)
    if (node.children.length > 1) {
      const firstChildX = props.positions[node.children[0].id].x + nodeWidth / 2
      const lastChildX = props.positions[node.children[node.children.length - 1].id].x + nodeWidth / 2
      
      paths.push({
        type: 'sibling-bridge',
        x1: firstChildX,
        y1: stemBottomY,
        x2: lastChildX,
        y2: stemBottomY
      })
    }

    // 4. Individual Child Stems (Up to bridge)
    node.children.forEach(child => {
      const childPos = props.positions[child.id]
      if (childPos) {
        paths.push({
          type: 'child-stem',
          x1: childPos.x + nodeWidth / 2,
          y1: stemBottomY,
          x2: childPos.x + nodeWidth / 2,
          y2: childPos.y
        })
        // Recurse
        collectPaths(child, paths)
      }
    })
  }

  return paths
}

const allPaths = computed(() => collectPaths(props.rootNode))

const junctionPoints = computed(() => {
  return allPaths.value
    .filter(p => p.type === 'marriage' || (p.type === 'stem-down' && !allPaths.value.find(ap => ap.type === 'marriage' && ap.x1 === p.x1)))
    .map(p => {
       if (p.type === 'marriage') return { x: (p.x1 + p.x2) / 2, y: p.y1 }
       return null
    }).filter(p => p !== null)
})
</script>

<template>
  <svg 
    :width="dimensions.width" 
    :height="dimensions.height" 
    class="absolute top-0 left-0 pointer-events-none overflow-visible"
    :style="{ transform: `translate(${dimensions.offsetX}px, ${dimensions.offsetY}px)` }"
  >
    <g>
      <!-- Draw Lines -->
      <line 
        v-for="(path, idx) in allPaths" 
        :key="'path-' + idx"
        :x1="path.x1" :y1="path.y1"
        :x2="path.x2" :y2="path.y2"
        :stroke="config.lineColor"
        :stroke-width="config.lineWidth"
        stroke-linecap="round"
      />

      <!-- Draw Junction Dots -->
      <circle 
        v-for="(dot, idx) in junctionPoints" 
        :key="'dot-' + idx"
        :cx="dot.x" :cy="dot.y"
        :r="config.junctionRadius"
        :fill="'white'"
        :stroke="config.lineColor"
        :stroke-width="config.lineWidth"
      />
    </g>
  </svg>
</template>
