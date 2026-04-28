/**
 * LAYOUTENGINE.JS - Slot-Based Centering Logic
 * Ensures each branch stays within its allocated width to prevent overlaps.
 */

export function computeLayout(rootNode, options = {}) {
  const nodeWidth = options.nodeWidth || 160
  const nodeHeight = options.nodeHeight || 100
  const gapX = options.gapX || 60
  const gapY = options.gapY || 180
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

    // Save Bio Position
    const totalSpouseLayers = node.spouse?.length || 0
    const activeIdx = activeSpouseIndices[node.id] || 0

    positions[node.id] = { x: bioX, y, depth }
    flattenedNodes.push({ 
      ...node, 
      x: bioX, 
      y, 
      depth, 
      type: 'bio', 
      totalSpouseLayers, 
      activeSpouseIndex: activeIdx 
    })

    // Position the single active spouse to the right
    if (node._activeSpouse) {
      const s = node._activeSpouse
      const sX = bioX + nodeWidth + spouseGap
      positions[s.id] = { x: sX, y, depth }
      flattenedNodes.push({ ...s, x: sX, y, depth, type: 'spouse', mainId: node.id, spousePos: 'right' })
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

  // Canvas calculations
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity
  Object.values(positions).forEach(p => {
    minX = Math.min(minX, p.x)
    maxX = Math.max(maxX, p.x + nodeWidth)
    minY = Math.min(minY, p.y)
    maxY = Math.max(maxY, p.y + nodeHeight)
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
