<script setup>
/**
 * TREECANVAS.VUE
 * Renders all connecting lines using SVG based on global coordinates.
 * Updated to support children per spouse (multi-spouse system) and is_blood styling.
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

  // --- 1. HANDLE DIRECT CHILDREN (No specific spouse) ---
  if (node.children && node.children.length > 0) {
    const junctionX = currentPos.x + nodeWidth / 2
    drawDescent(junctionX, currentPos.y, node.children, paths)
    
    // Recurse for each direct child
    node.children.forEach(child => collectPaths(child, paths))
  }

  // --- 2. HANDLE SPOUSES AND THEIR CHILDREN ---
  if (node.spouse && node.spouse.length > 0) {
    node.spouse.forEach(spouse => {
      const spousePos = props.positions[spouse.id]
      if (!spousePos) return

      // Line between Bio and this Spouse
      paths.push({
        type: 'marriage',
        x1: currentPos.x + nodeWidth,
        y1: currentPos.y + nodeHeight / 2,
        x2: spousePos.x,
        y2: spousePos.y + nodeHeight / 2
      })

      // If this marriage has children
      if (spouse.children && spouse.children.length > 0) {
        const marriageJunctionX = (currentPos.x + nodeWidth + spousePos.x) / 2
        drawDescent(marriageJunctionX, currentPos.y, spouse.children, paths)
        
        // Recurse for each child of this spouse
        spouse.children.forEach(child => collectPaths(child, paths))
      }
    })
  }

  return paths
}

/**
 * Helper to draw descent lines from a junction point to a set of children
 */
function drawDescent(junctionX, parentY, children, paths) {
  const nodeWidth = props.config.nodeWidth
  const nodeHeight = props.config.nodeHeight
  const firstChildPos = props.positions[children[0].id]
  
  if (!firstChildPos) return

  // Vertical Stem Down from Junction
  const stemBottomY = firstChildPos.y - (firstChildPos.y - parentY - nodeHeight) / 2
  
  paths.push({
    type: 'stem-down',
    x1: junctionX,
    y1: parentY + nodeHeight / 2,
    x2: junctionX,
    y2: stemBottomY
  })

  // Horizontal Sibling Bridge
  if (children.length > 1) {
    const firstChildX = props.positions[children[0].id].x + nodeWidth / 2
    const lastChildX = props.positions[children[children.length - 1].id].x + nodeWidth / 2
    
    paths.push({
      type: 'sibling-bridge',
      x1: firstChildX,
      y1: stemBottomY,
      x2: lastChildX,
      y2: stemBottomY
    })
  }

  // Individual Child Stems (Up from child to bridge)
  children.forEach(child => {
    const childPos = props.positions[child.id]
    if (childPos) {
      paths.push({
        type: 'child-stem',
        x1: childPos.x + nodeWidth / 2,
        y1: stemBottomY,
        x2: childPos.x + nodeWidth / 2,
        y2: childPos.y,
        isBlood: child.is_blood !== false // Default to true if not specified
      })
    }
  })
}

const allPaths = computed(() => collectPaths(props.rootNode))

const junctionPoints = computed(() => {
  return allPaths.value
    .filter(p => p.type === 'marriage')
    .map(p => ({ x: (p.x1 + p.x2) / 2, y: p.y1 }))
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
        :stroke-dasharray="path.isBlood === false ? '5,5' : 'none'"
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
