@props(['value' => 0, 'size' => 56, 'stroke' => 6, 'color' => 'text-emerald-500', 'label' => true])

@php
    $value = max(0, min(100, (int) $value));
    $r = ($size - $stroke) / 2;
    $c = 2 * M_PI * $r;
    $offset = $c * (1 - $value / 100);
@endphp

{{-- Anillo de progreso: el trazo parte vacío y se llena al cargar. --}}
<div class="relative inline-flex items-center justify-center shrink-0" style="width: {{ $size }}px; height: {{ $size }}px"
     x-data="{ on: false }" x-init="requestAnimationFrame(() => on = true)">
    <svg class="ring-progress -rotate-90" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" class="text-slate-100"/>
        <circle class="ring-value {{ $color }}" cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}"
                stroke-linecap="round" stroke-dasharray="{{ round($c, 2) }}" :stroke-dashoffset="on ? {{ round($offset, 2) }} : {{ round($c, 2) }}"/>
    </svg>
    @if ($label)
        <span class="absolute text-xs font-bold text-slate-700 tabular-nums">{{ $value }}%</span>
    @endif
</div>
