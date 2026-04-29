<script setup>
/**
 * TREECANVAS.VUE
 * Renders all connecting lines using SVG based on global coordinates.
 * Updated to support direction switching (Vertical, Inverted, Horizontal).
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
  direction: {
    type: String,
    default: 'ttb'
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

  if (!currentPos) {
return paths
}

  const nodeWidth = props.config.nodeWidth
  const nodeHeight = props.config.nodeHeight
  const direction = props.direction

  // --- 1. HANDLE DIRECT CHILDREN ---
  if (node.children && node.children.length > 0) {
    let junctionX, junctionY
    
    if (direction === 'ltr') {
      junctionX = currentPos.x + nodeWidth
      junctionY = currentPos.y + nodeHeight / 2
    } else {
      junctionX = currentPos.x + nodeWidth / 2
      junctionY = currentPos.y + (direction === 'btt' ? 0 : nodeHeight)
    }

    drawDescent(junctionX, junctionY, node.children, paths)
    node.children.forEach(child => collectPaths(child, paths))
  }

  // --- 2. HANDLE SPOUSES AND THEIR CHILDREN ---
  if (node.spouse && node.spouse.length > 0) {
    node.spouse.forEach(spouse => {
      const spousePos = props.positions[spouse.id]

      if (!spousePos) {
return
}

      // Marriage line
      let x1, y1, x2, y2, marriageJunctionX, marriageJunctionY
      
      if (direction === 'ltr') {
        // Spouse is below bio in LTR mode internal layout
        x1 = currentPos.x + nodeWidth / 2
        y1 = currentPos.y + nodeHeight
        x2 = spousePos.x + nodeWidth / 2
        y2 = spousePos.y
        marriageJunctionX = x1
        marriageJunctionY = (y1 + y2) / 2
      } else {
        // Spouse is to the right
        x1 = currentPos.x + nodeWidth
        y1 = currentPos.y + nodeHeight / 2
        x2 = spousePos.x
        y2 = spousePos.y + nodeHeight / 2
        marriageJunctionX = (x1 + x2) / 2
        marriageJunctionY = y1
      }

      paths.push({
        type: 'marriage',
        x1, y1, x2, y2
      })

      if (spouse.children && spouse.children.length > 0) {
        // Split children by is_blood
        const bloodChildren = spouse.children.filter(c => c.is_blood !== false)
        const stepChildren = spouse.children.filter(c => c.is_blood === false)

        if (bloodChildren.length > 0) {
          drawDescent(marriageJunctionX, marriageJunctionY, bloodChildren, paths)
        }
        
        if (stepChildren.length > 0) {
          let sJuncX, sJuncY

          if (direction === 'ltr') {
            sJuncX = spousePos.x + nodeWidth
            sJuncY = spousePos.y + nodeHeight / 2
          } else {
            sJuncX = spousePos.x + nodeWidth / 2
            sJuncY = spousePos.y + (direction === 'btt' ? 0 : nodeHeight)
          }

          drawDescent(sJuncX, sJuncY, stepChildren, paths)
        }
        
        spouse.children.forEach(child => collectPaths(child, paths))
      }
    })
  }

  return paths
}

function drawDescent(junctionX, junctionY, children, paths) {
  const nodeWidth = props.config.nodeWidth
  const nodeHeight = props.config.nodeHeight
  const direction = props.direction
  const firstChildPos = props.positions[children[0].id]
  
  if (!firstChildPos) {
return
}

  let stemEndX, stemEndY, bridgeX1, bridgeY1, bridgeX2, bridgeY2

  if (direction === 'ltr') {
    // Flows horizontally to the right
    stemEndX = junctionX + (firstChildPos.x - junctionX) / 2
    stemEndY = junctionY
    
    paths.push({
      type: 'stem',
      x1: junctionX, y1: junctionY,
      x2: stemEndX, y2: stemEndY
    })

    if (children.length > 1) {
      const sorted = [...children].sort((a, b) => props.positions[a.id].y - props.positions[b.id].y)
      bridgeX1 = stemEndX
      bridgeY1 = props.positions[sorted[0].id].y + nodeHeight / 2
      bridgeX2 = stemEndX
      bridgeY2 = props.positions[sorted[sorted.length - 1].id].y + nodeHeight / 2
      
      paths.push({ type: 'bridge', x1: bridgeX1, y1: bridgeY1, x2: bridgeX2, y2: bridgeY2 })
    }

    children.forEach(child => {
      const cp = props.positions[child.id]

      if (cp) {
        paths.push({
          type: 'child-stem',
          x1: stemEndX, y1: cp.y + nodeHeight / 2,
          x2: cp.x, y2: cp.y + nodeHeight / 2,
          is_blood: child.is_blood
        })
      }
    })

  } else {
    // Vertical flows (ttb or btt)
    const isBtt = direction === 'btt'
    stemEndX = junctionX
    stemEndY = junctionY + (firstChildPos.y + (isBtt ? nodeHeight : 0) - junctionY) / 2
    
    paths.push({
      type: 'stem',
      x1: junctionX, y1: junctionY,
      x2: stemEndX, y2: stemEndY
    })

    if (children.length > 1) {
      const sorted = [...children].sort((a, b) => props.positions[a.id].x - props.positions[b.id].x)
      bridgeX1 = props.positions[sorted[0].id].x + nodeWidth / 2
      bridgeY1 = stemEndY
      bridgeX2 = props.positions[sorted[sorted.length - 1].id].x + nodeWidth / 2
      bridgeY2 = stemEndY
      
      paths.push({ type: 'bridge', x1: bridgeX1, y1: bridgeY1, x2: bridgeX2, y2: bridgeY2 })
    }

    children.forEach(child => {
      const cp = props.positions[child.id]

      if (cp) {
        paths.push({
          type: 'child-stem',
          x1: cp.x + nodeWidth / 2, y1: stemEndY,
          x2: cp.x + nodeWidth / 2, y2: cp.y + (isBtt ? nodeHeight : 0),
          is_blood: child.is_blood
        })
      }
    })
  }
}

const allPaths = computed(() => {
  return collectPaths(props.rootNode)
})
</script>

<template>
  <svg 
    class="absolute inset-0 pointer-events-none"
    :width="dimensions.width" 
    :height="dimensions.height"
  >
    <g :style="{ transform: `translate(${dimensions.offsetX}px, ${dimensions.offsetY}px)` }">
      <template v-for="(path, index) in allPaths" :key="index">
        <!-- Lines -->
        <line 
          :x1="path.x1" :y1="path.y1" 
          :x2="path.x2" :y2="path.y2"
          :stroke="config.lineColor"
          :stroke-width="config.lineWidth"
          :stroke-dasharray="path.is_blood === false ? '4,4' : 'none'"
          stroke-linecap="round"
        />
        
        <!-- Marriage Junction Dot -->
        <circle 
          v-if="path.type === 'marriage'"
          :cx="(path.x1 + path.x2) / 2" 
          :cy="(path.y1 + path.y2) / 2" 
          :r="config.junctionRadius"
          :fill="config.lineColor"
        />

        <!-- Branch Junction Dot -->
        <circle 
          v-if="path.type === 'stem' && direction !== 'ltr'"
          :cx="path.x2" 
          :cy="path.y2" 
          :r="config.junctionRadius"
          :fill="config.lineColor"
        />
        <circle 
          v-if="path.type === 'stem' && direction === 'ltr'"
          :cx="path.x2" 
          :cy="path.y2" 
          :r="config.junctionRadius"
          :fill="config.lineColor"
        />
      </template>
    </g>
  </svg>
</template>
