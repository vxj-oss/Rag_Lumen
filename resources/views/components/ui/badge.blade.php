@props(['color' => 'gray'])

@php
    $colors = [
        'gray' => 'bg-gray-500 text-white shadow-gray-900/10',
        'blue' => 'bg-blue-600 text-white shadow-blue-900/20',
        'yellow' => 'bg-yellow-500 text-white shadow-yellow-900/20',
        'red' => 'bg-red-600 text-white shadow-red-900/20',
        'green' => 'bg-emerald-600 text-white shadow-emerald-900/20',
        'indigo' => 'bg-indigo-600 text-white shadow-indigo-900/20',
    ];
    $dots = [
        'gray' => 'bg-gray-300',
        'blue' => 'bg-blue-200',
        'yellow' => 'bg-yellow-100',
        'red' => 'bg-red-200',
        'green' => 'bg-emerald-200',
        'indigo' => 'bg-indigo-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm whitespace-nowrap transition-colors duration-200 ' . ($colors[$color] ?? $colors['gray'])]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dots[$color] ?? $dots['gray'] }}"></span>
    {{ $slot }}
</span>
