@props(['value' => 0, 'barWidth' => 'w-32'])

@php
    $value = max(0, min(100, (int) $value));
    $color = $value >= 100 ? 'from-emerald-400 to-emerald-600' : ($value >= 50 ? 'from-sky-400 to-blue-500' : 'from-amber-300 to-amber-500');
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <div class="{{ $barWidth }} bg-slate-100 rounded-full h-2 shrink-0 overflow-hidden">
        <div class="bg-gradient-to-r {{ $color }} h-2 rounded-full transition-all duration-700 ease-out" @style(["width: {$value}%"])></div>
    </div>
    <span class="text-xs font-semibold text-slate-500 tabular-nums shrink-0">{{ $value }}%</span>
</div>
