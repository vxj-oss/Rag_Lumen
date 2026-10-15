@props(['padding' => 'p-6', 'interactive' => false])

<div {{ $attributes->merge(['class' => "bg-white {$padding} rounded-2xl border border-slate-200/70 shadow-soft transition-shadow duration-300 hover:shadow-md ".($interactive ? 'lift' : '')]) }}>
    {{ $slot }}
</div>
