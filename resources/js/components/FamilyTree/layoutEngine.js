/**
 * LAYOUTENGINE.JS - Slot-Based Centering Logic
 * Ensures each branch stays within its allocated width to prevent overlaps.
 */

export function computeLayout(rootNode, options = {}) {
  const direction = options.direction || 'ttb' // 'ttb', 'btt', 'ltr'
  const isHorizontal = direction === 'ltr'

  // Swap logic for horizontal mode internally
  const nodeWidth = isHorizontal ? (options.nodeHeight || 110) : (options.nodeWidth || 160)
  const nodeHeight = isHorizontal ? (options.nodeWidth || 160) : (options.nodeHeight || 110)
  const gapX = isHorizontal ? 60 : (options.gapX || 60)
  const gapY = isHorizontal ? 320 : (options.gapY || 200)
  const spouseGap = options.spouseGap || 40
  const activeSpouseIndices = options.activeSpouseIndices || {}

  const positions = {}
  const flattenedNodes = []

  // Step 1: Calculate total width needed for each subtree (Post-order)
  function calculateSubtreeWidth(node) {
    const activeIdx = activeSpouseIndices[node.id] || 0
    const spouses = node.spouse || []
    
    // Only one spouse is visible at a time (Layering)
    const activeSpouse = spouses[activeIdx] || null
    node._activeSpouse = activeSpouse

    // Collect children from the main node AND the active spouse
    const directChildren = node.children || []
    const spouseChildren = activeSpouse ? (activeSpouse.children || []) : []
    const allChildren = [...directChildren, ...spouseChildren]
    node._allChildren = allChildren
    
    // Width of the parent row: [Bio] -- [Active Spouse]
    const visibleSpouseCount = activeSpouse ? 1 : 0
    const selfRowWidth = visibleSpouseCount === 1 
      ? nodeWidth * 2 + spouseGap 
      : nodeWidth

    if (allChildren.length === 0) {
      node._subtreeWidth = selfRowWidth

      return selfRowWidth
    }

    // Width of the children row
    let childrenRowWidth = 0
    allChildren.forEach((child, index) => {
      childrenRowWidth += calculateSubtreeWidth(child)

      if (index < allChildren.length - 1) {
        childrenRowWidth += gapX
      }
    })

    node._subtreeWidth = Math.max(selfRowWidth, childrenRowWidth)
    node._selfRowWidth = selfRowWidth
    node._childrenRowWidth = childrenRowWidth
    
    return node._subtreeWidth
  }

  // Step 2: Assign absolute coordinates (Pre-order)
  function assignPositions(node, startX, depth) {
    const allChildren = node._allChildren || []
    const y = depth * gapY
    
    const selfRowWidth = node._selfRowWidth || nodeWidth
    const childrenRowWidth = node._childrenRowWidth || 0

    let bioX, childrenStartX

    if (selfRowWidth >= childrenRowWidth) {
      bioX = startX
      childrenStartX = startX + (selfRowWidth - childrenRowWidth) / 2
    } else {
      bioX = startX + (childrenRowWidth - selfRowWidth) / 2
      childrenStartX = startX
    }

    // Temporary positions (Standard top-down)
    node._tempX = bioX
    node._tempY = y
    node._depth = depth

    // Position the single active spouse to the right
    if (node._activeSpouse) {
      node._activeSpouse._tempX = bioX + nodeWidth + spouseGap
      node._activeSpouse._tempY = y
      node._activeSpouse._depth = depth
    }

    // Recurse for all aggregated children
    if (allChildren.length > 0) {
      let currentChildX = childrenStartX
      allChildren.forEach(child => {
        assignPositions(child, currentChildX, depth + 1)
        currentChildX += child._subtreeWidth + gapX
      })
    }
  }

  // Execute
  calculateSubtreeWidth(rootNode)
  assignPositions(rootNode, 0, 0)

  // Step 3: Global Bounds for transformation
  function collectTempPositions(node, list) {
    list.push(node)

    if (node._activeSpouse) {
list.push(node._activeSpouse)
}

    if (node._allChildren) {
node._allChildren.forEach(c => collectTempPositions(c, list))
}
  }
  const allNodeObjects = []
  collectTempPositions(rootNode, allNodeObjects)

  let minTX = Infinity, maxTX = -Infinity, minTY = Infinity, maxTY = -Infinity
  const calcNodeW = nodeWidth
  const calcNodeH = nodeHeight

  allNodeObjects.forEach(n => {
    minTX = Math.min(minTX, n._tempX)
    maxTX = Math.max(maxTX, n._tempX + calcNodeW)
    minTY = Math.min(minTY, n._tempY)
    maxTY = Math.max(maxTY, n._tempY + calcNodeH)
  })

  // Step 4: Map coordinates based on direction
  const finalNodeWidth = options.nodeWidth || 160
  const finalNodeHeight = options.nodeHeight || 100

  function mapNode(node, type, mainId = null) {
    let x = node._tempX
    let y = node._tempY

    if (direction === 'btt') {
      y = maxTY - (node._tempY - minTY) - calcNodeH
    } else if (direction === 'ltr') {
      // Swap X and Y
      x = node._tempY
      y = node._tempX
    }

    positions[node.id] = { x, y, depth: node._depth }
    
    const nodeData = {
      ...node,
      x,
      y,
      depth: node._depth,
      type
    }

    if (type === 'bio') {
      nodeData.totalSpouseLayers = node.spouse?.length || 0
      nodeData.activeSpouseIndex = activeSpouseIndices[node.id] || 0
    } else if (type === 'spouse') {
      nodeData.mainId = mainId
      nodeData.spousePos = direction === 'ltr' ? 'bottom' : 'right'
    }

    flattenedNodes.push(nodeData)
  }

  function traverseAndMap(node) {
    mapNode(node, 'bio')

    if (node._activeSpouse) {
      mapNode(node._activeSpouse, 'spouse', node.id)
    }

    if (node._allChildren) {
      node._allChildren.forEach(c => traverseAndMap(c))
    }
  }

  traverseAndMap(rootNode)

  // Re-calculate dimensions after mapping
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity
  Object.values(positions).forEach(p => {
    minX = Math.min(minX, p.x)
    maxX = Math.max(maxX, p.x + finalNodeWidth)
    minY = Math.min(minY, p.y)
    maxY = Math.max(maxY, p.y + finalNodeHeight)
  })

  return { 
    positions, 
    flattenedNodes,
    dimensions: {
      width: maxX - minX + 400,
      height: maxY - minY + 400,
      offsetX: -minX + 200,
      offsetY: -minY + 200
    }
  }
}
