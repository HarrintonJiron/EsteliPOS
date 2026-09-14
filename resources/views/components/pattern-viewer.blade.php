@props(['pattern' => ''])

@php
    $viewerId = 'pattern-viewer-' . uniqid();
@endphp

<div id="{{ $viewerId }}" class="pattern-viewer" data-pattern="{{ $pattern }}">
    <div class="pattern-viewer-box">
        <div class="pattern-grid" aria-label="Patrón de desbloqueo">
            @for($i = 1; $i <= 9; $i++)
                <div class="pattern-cell" data-point="{{ $i }}"><span>{{ $i }}</span></div>
            @endfor
            <svg class="pattern-svg" viewBox="0 0 220 220" preserveAspectRatio="none"></svg>
        </div>
    </div>
    <div class="pattern-sequence">
        <span class="pattern-label">Secuencia:</span>
        <span class="pattern-sequence-text"></span>
    </div>
</div>

@once
<style>
.pattern-viewer {
    width: min(100%, 260px);
    margin: 0 auto;
    font-family: Inter, system-ui, sans-serif;
}
.pattern-viewer-box {
    position: relative;
    border-radius: 24px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}
.pattern-grid {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-rows: repeat(3, minmax(0, 1fr));
    gap: 14px;
    padding: 16px;
}
.pattern-cell {
    position: relative;
    display: grid;
    place-items: center;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #d1d5db;
    color: #334155;
    font-size: 1rem;
    font-weight: 700;
    transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
}
.pattern-cell.selected {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #1d4ed8;
    transform: translateY(-1px);
}
.pattern-cell.start {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}
.pattern-cell.end {
    background: #fee2e2;
    border-color: #ef4444;
    color: #b91c1c;
}
.pattern-cell span {
    position: relative;
    z-index: 2;
}
.pattern-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
}
.pattern-sequence {
    margin-top: 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    color: #475569;
    font-size: 0.9rem;
}
.pattern-label {
    font-weight: 600;
    color: #334155;
}
.pattern-sequence-text {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 4px;
}
.pattern-sequence-text::before {
    content: '';
}
.pattern-sequence-text span {
    display: inline-flex;
}
</style>
@endonce

@once
<script>
(function () {
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const positions = {
        1: { x: 35, y: 35 },
        2: { x: 110, y: 35 },
        3: { x: 185, y: 35 },
        4: { x: 35, y: 110 },
        5: { x: 110, y: 110 },
        6: { x: 185, y: 110 },
        7: { x: 35, y: 185 },
        8: { x: 110, y: 185 },
        9: { x: 185, y: 185 },
    };

    function parsePattern(pattern) {
        if (!pattern) return [];
        return String(pattern)
            .split(/[^1-9]+/)
            .filter(Boolean)
            .map(Number)
            .filter((n) => n >= 1 && n <= 9);
    }

    function buildArrowMarker(svg) {
        const defs = document.createElementNS(SVG_NS, 'defs');
        const marker = document.createElementNS(SVG_NS, 'marker');
        marker.setAttribute('id', 'pattern-arrowhead');
        marker.setAttribute('markerWidth', '12');
        marker.setAttribute('markerHeight', '12');
        marker.setAttribute('refX', '0');
        marker.setAttribute('refY', '5');
        marker.setAttribute('orient', 'auto');
        marker.setAttribute('markerUnits', 'strokeWidth');

        const path = document.createElementNS(SVG_NS, 'path');
        path.setAttribute('d', 'M0,0 L12,5 L0,10 Z');
        path.setAttribute('fill', '#f59e0b');

        marker.appendChild(path);
        defs.appendChild(marker);
        svg.appendChild(defs);
    }

    function renderLines(svg, points) {
        if (points.length < 2) return;
        buildArrowMarker(svg);

        for (let i = 0; i < points.length - 1; i += 1) {
            const from = positions[points[i]];
            const to = positions[points[i + 1]];
            if (!from || !to) continue;

            const line = document.createElementNS(SVG_NS, 'line');
            line.setAttribute('x1', from.x);
            line.setAttribute('y1', from.y);
            line.setAttribute('x2', to.x);
            line.setAttribute('y2', to.y);
            line.setAttribute('stroke', '#3b82f6');
            line.setAttribute('stroke-width', '3');
            line.setAttribute('stroke-linecap', 'round');
            line.setAttribute('marker-end', 'url(#pattern-arrowhead)');
            svg.appendChild(line);
        }
    }

    function renderPatternInto(container, pattern) {
        const points = parsePattern(pattern);
        const cells = Array.from(container.querySelectorAll('.pattern-cell'));
        const svg = container.querySelector('.pattern-svg');
        const sequenceText = container.querySelector('.pattern-sequence-text');

        cells.forEach((cell) => {
            cell.classList.remove('selected', 'start', 'end');
        });

        svg.innerHTML = '';
        if (points.length >= 2) {
            renderLines(svg, points);
        }

        points.forEach((point, index) => {
            const cell = container.querySelector(`.pattern-cell[data-point="${point}"]`);
            if (!cell) return;
            cell.classList.add('selected');
            if (index === 0) {
                cell.classList.add('start');
            }
            if (index === points.length - 1) {
                cell.classList.add('end');
            }
        });

        sequenceText.innerHTML = points.length
            ? points.map((p) => `<span>${p}</span>`).join(' <span class="text-slate-400">→</span> ')
            : '<span>Sin patrón</span>';
    }

    function initializeViewer(container) {
        const pattern = container.dataset.pattern || '';
        renderPatternInto(container, pattern);
    }

    window.drawPattern = window.drawPattern || function (pattern) {
        const viewer = document.querySelector('.pattern-viewer');
        if (!viewer) return;
        renderPatternInto(viewer, pattern);
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pattern-viewer').forEach(initializeViewer);
    });
})();
</script>
@endonce
