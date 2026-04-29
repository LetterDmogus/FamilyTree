/**
 * TIMELINEENGINE.JS
 * Calculates X (Year) and Y (Row) positions for family members chronologically.
 */

export function computeTimelineLayout(rootNode, options = {}) {
  const pixelsPerYear = options.pixelsPerYear || 15
  const rowHeight = options.rowHeight || 60
  const barHeight = options.barHeight || 36
  const currentYear = new Date().getFullYear()

  let minYear = currentYear
  let maxYear = currentYear

  // Find overall min/max years
  function findBounds(node) {
    const years = []

    if (node.birth_date) {
years.push(new Date(node.birth_date).getFullYear())
}

    if (node.death_date) {
years.push(new Date(node.death_date).getFullYear())
}
    
    years.forEach(y => {
      minYear = Math.min(minYear, y)
      maxYear = Math.max(maxYear, y)
    })

    const spouseList = node.spouse || []
    spouseList.forEach(s => findBounds(s))

    const childList = node.children || []
    childList.forEach(c => findBounds(c))
  }

  findBounds(rootNode)

  // Round min/max to decades for padding
  minYear = Math.floor(minYear / 10) * 10 - 10
  maxYear = Math.ceil(maxYear / 10) * 10 + 10

  const nodes = []
  const links = []
  let currentRow = 0

  // Colors based on generation (MyHeritage style)
  const colors = [
    '#22d3ee', // Cyan
    '#a3e635', // Lime
    '#fbbf24', // Amber
    '#f87171', // Red
    '#f472b6', // Pink
    '#818cf8', // Indigo
    '#fb923c', // Orange
  ]

  function processNode(node, parentRow, depth) {
    const nodeRow = currentRow++
    const birthYear = node.birth_date ? new Date(node.birth_date).getFullYear() : (minYear + 20)
    const deathYear = node.death_date ? new Date(node.death_date).getFullYear() : (node.is_alive ? currentYear : birthYear + 70)
    
    const x = (birthYear - minYear) * pixelsPerYear
    const width = (deathYear - birthYear) * pixelsPerYear
    // Add 40px padding at the top to clear the decade labels
    const y = nodeRow * rowHeight + (rowHeight - barHeight) / 2 + 40

    const person = {
      ...node,
      x,
      y,
      width,
      birthYear,
      deathYear,
      row: nodeRow,
      color: colors[depth % colors.length],
      is_estimated: !node.birth_date
    }

    nodes.push(person)

    // Add link from parent to child
    if (parentRow !== null) {
      links.push({
        parentRow,
        childRow: nodeRow,
        birthYear: birthYear,
        x: (birthYear - minYear) * pixelsPerYear
      })
    }

    // Process spouses (adjacent rows)
    const spouseList = node.spouse || []
    spouseList.forEach(s => {
      const sRow = currentRow++
      const sBirthYear = s.birth_date ? new Date(s.birth_date).getFullYear() : (birthYear)
      const sDeathYear = s.death_date ? new Date(s.death_date).getFullYear() : (s.is_alive ? currentYear : sBirthYear + 70)
      
      const sx = (sBirthYear - minYear) * pixelsPerYear
      const swidth = (sDeathYear - sBirthYear) * pixelsPerYear
      const sy = sRow * rowHeight + (rowHeight - barHeight) / 2 + 40

      nodes.push({
        ...s,
        x: sx,
        y: sy,
        width: swidth,
        birthYear: sBirthYear,
        deathYear: sDeathYear,
        row: sRow,
        color: colors[depth % colors.length],
        is_spouse: true,
        mainId: node.id,
        is_estimated: !s.birth_date
      })

      // IMPORTANT: Process children of this spouse!
      const sChildList = s.children || []
      sChildList.forEach(c => processNode(c, sRow, depth + 1))
    })

    // Process direct children
    const childList = node.children || []
    childList.forEach(c => processNode(c, nodeRow, depth + 1))
  }

  processNode(rootNode, null, 0)

  // Build decade markers
  const decades = []

  for (let y = minYear; y <= maxYear; y += 10) {
    decades.push({
      year: y,
      x: (y - minYear) * pixelsPerYear
    })
  }

  return {
    nodes,
    links,
    decades,
    minYear,
    maxYear,
    dimensions: {
      width: (maxYear - minYear) * pixelsPerYear + 200,
      height: currentRow * rowHeight + 100
    }
  }
}
