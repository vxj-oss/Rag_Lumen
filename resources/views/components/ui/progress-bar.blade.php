@props(['value' => 0, 'barWidth' => 'w-32'])

@php
    $value = max(0, min(100, (int) $value));
    $color = $value >= 100 ? 'bg-green-500' : ($value >= 50 ? 'bg-blue-500' : 'bg-yellow-500');
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <div class="{{ $barWidth }} bg-gray-200 rounded-full h-2 shrink-0">
        <div class="{{ $color }} h-2 rounded-full transition-all duration-500" @style(["width: {$value}%"])></div>
    </div>
    <span class="text-xs text-gray-500 tabular-nums shrink-0">{{ $value }}%</span>
</div>