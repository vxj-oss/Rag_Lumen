@props(['label', 'value', 'color' => 'emerald', 'icon' => null, 'hint' => null])

@php
    $tiles = [
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'red' => 'bg-rose-50 text-rose-600',
        'yellow' => 'bg-amber-50 text-amber-600',
        'gray' => 'bg-slate-100 text-slate-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'orange' => 'bg-orange-50 text-orange-600',
    ];
    $bars = [
        'emerald' => 'bg-emerald-500',
        'red' => 'bg-rose-500',
        'yellow' => 'bg-amber-500',
        'gray' => 'bg-slate-400',
        'blue' => 'bg-blue-500',
        'violet' => 'bg-violet-500',
        'orange' => 'bg-orange-500',
    ];
@endphp

<div class="workspace-stat group relative bg-white rounded-2xl border border-slate-200/70 shadow-soft p-5 flex items-center gap-4 overflow-hidden">
    <span class="absolute left-0 inset-y-4 w-1 rounded-r-full {{ $bars[$color] ?? $bars['emerald'] }} opacity-70 group-hover:opacity-100 transition-opacity"></span>
    @if ($icon)
        <div {{ $attributes->class(['w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-300 ease-out group-hover:scale-110', $tiles[$color] ?? $tiles['emerald']]) }}>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icon !!}</svg>
        </div>
    @endif
    <div class="min-w-0">
        <p class="text-3xl font-extrabold text-slate-900 leading-tight tracking-tight tabular-nums">{{ $value }}</p>
        <p class="text-sm leading-snug text-slate-600">{{ $label }}</p>
        @if ($hint)
            <p class="text-xs text-slate-500 truncate">{{ $hint }}</p>
        @endif
    </div>
</div>
