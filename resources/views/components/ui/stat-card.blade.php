@props(['label', 'value', 'color' => 'emerald', 'icon' => null])

@php
    $colors = [
        'emerald' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-emerald-500/30',
        'red' => 'bg-gradient-to-br from-red-400 to-red-600 text-white shadow-red-500/30',
        'yellow' => 'bg-gradient-to-br from-yellow-400 to-yellow-500 text-white shadow-yellow-500/30',
        'gray' => 'bg-gradient-to-br from-gray-400 to-gray-600 text-white shadow-gray-500/30',
        'blue' => 'bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-blue-500/30',
    ];
    $topBar = [
        'emerald' => 'from-emerald-400 to-emerald-600',
        'red' => 'from-red-400 to-red-600',
        'yellow' => 'from-yellow-400 to-yellow-500',
        'gray' => 'from-gray-400 to-gray-600',
        'blue' => 'from-blue-400 to-blue-600',
    ];
@endphp

<div class="group relative bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-5 flex items-center gap-4 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $topBar[$color] ?? $topBar['emerald'] }}"></div>
    @if ($icon)
        <div {{ $attributes->class(['w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-lg transition-transform duration-300 ease-out group-hover:scale-110 group-hover:rotate-3', $colors[$color] ?? $colors['emerald']]) }}>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icon !!}</svg>
        </div>
    @endif
    <div class="min-w-0">
        <p class="text-3xl font-extrabold text-gray-900 leading-tight tracking-tight tabular-nums">{{ $value }}</p>
        <p class="text-sm text-gray-500 truncate">{{ $label }}</p>
    </div>
</div>
