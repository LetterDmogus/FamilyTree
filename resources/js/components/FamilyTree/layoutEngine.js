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

  const positions = {}
  const flattenedNodes = []

  // Step 1: Calculate total width needed for each subtree (Post-order)
  function calculateSubtreeWidth(node) {
    const children = node.children || []
    
    // Width of the parent row: [Bio] -- [Spouse1] -- [Spouse2]
    const spouseCount = node.spouse?.length || 0
    const selfRowWidth = nodeWidth + spouseCount * (spouseGap + nodeWidth)

    if (children.length === 0) {
      node._subtreeWidth = selfRowWidth
      return selfRowWidth
    }

    // Width of the children row: Sum of subtrees + gaps
    let childrenRowWidth = 0
    children.forEach((child, index) => {
      childrenRowWidth += calculateSubtreeWidth(child)
      if (index < children.length - 1) {
        childrenRowWidth += gapX
      }
    })

    // The slot width is the maximum of the two levels
    node._subtreeWidth = Math.max(selfRowWidth, childrenRowWidth)
    node._selfRowWidth = selfRowWidth
    node._childrenRowWidth = childrenRowWidth
    
    return node._subtreeWidth
  }

  // Step 2: Assign absolute coordinates (Pre-order)
  function assignPositions(node, startX, depth) {
    const children = node.children || []
    const y = depth * gapY
    
    const slotWidth = node._subtreeWidth
    const selfRowWidth = node._selfRowWidth || nodeWidth
    const childrenRowWidth = node._childrenRowWidth || 0

    let x, childrenStartX

    // Center the narrower row relative to the wider row within the slot
    if (selfRowWidth >= childrenRowWidth) {
      // Parent row is wider: it takes the full slot
      x = startX
      childrenStartX = startX + (selfRowWidth - childrenRowWidth) / 2
    } else {
      // Children row is wider: parents are centered above it
      x = startX + (childrenRowWidth - selfRowWidth) / 2
      childrenStartX = startX
    }

    // Save positions
    positions[node.id] = { x, y, depth }
    flattenedNodes.push({ ...node, x, y, depth, type: 'bio' })

    // Position spouses to the right of Bio Node
    if (node.spouse && node.spouse.length > 0) {
      node.spouse.forEach((s, idx) => {
        const sX = x + (nodeWidth + spouseGap) * (idx + 1)
        positions[s.id] = { x: sX, y, depth }
        flattenedNodes.push({ ...s, x: sX, y, depth, type: 'spouse', mainId: node.id })
      })
    }

    // Recurse for children
    if (children.length > 0) {
      let currentChildX = childrenStartX
      children.forEach(child => {
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
