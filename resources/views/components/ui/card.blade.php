@props(['padding' => 'p-6', 'interactive' => false])

<div {{ $attributes->merge(['class' => "workspace-card bg-white {$padding} rounded-2xl border border-slate-200/70 shadow-soft ".($interactive ? 'lift' : '')]) }}>
    {{ $slot }}
</div>
