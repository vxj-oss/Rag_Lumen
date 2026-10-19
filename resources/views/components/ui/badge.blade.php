@props(['color' => 'gray'])

@php
    // Estilo "suave": fondo tintado + texto del mismo tono, sin bloques de color saturado.
    $colors = [
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'yellow' => 'bg-amber-50 text-amber-700 ring-amber-600/25',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
    ];
    $dots = [
        'gray' => 'bg-slate-400',
        'blue' => 'bg-blue-500',
        'yellow' => 'bg-amber-500',
        'red' => 'bg-rose-500',
        'green' => 'bg-emerald-500',
        'indigo' => 'bg-indigo-500',
        'violet' => 'bg-violet-500',
        'orange' => 'bg-orange-500',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'workspace-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset whitespace-nowrap transition-colors duration-200 ' . ($colors[$color] ?? $colors['gray'])]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dots[$color] ?? $dots['gray'] }}"></span>
    {{ $slot }}
</span>
