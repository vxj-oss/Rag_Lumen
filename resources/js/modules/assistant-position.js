export function clampPosition(position, viewport, size = 56, gap = 12) {
    const maxX = Math.max(gap, viewport.width - size - gap), maxY = Math.max(gap, viewport.height - size - gap);
    return { x: Math.min(maxX, Math.max(gap, Number.isFinite(position?.x) ? position.x : maxX)), y: Math.min(maxY, Math.max(gap, Number.isFinite(position?.y) ? position.y : maxY)) };
}

export function panelBounds(position, viewport) {
    const gap = 12, width = Math.max(1, Math.min(416, viewport.width - 2 * gap));
    const above = position.y - gap, below = viewport.height - position.y - 56 - gap;
    const height = Math.max(1, Math.min(600, Math.max(above, below) - gap));
    const x = Math.max(gap, Math.min(position.x + 56 - width, viewport.width - width - gap));
    let y = above >= below ? position.y - height - gap : position.y + 56 + gap;
    y = Math.max(gap, Math.min(y, viewport.height - height - gap));
    return { x, y, width, height };
}
