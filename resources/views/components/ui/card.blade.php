@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => "bg-white {$padding} shadow-sm ring-1 ring-gray-900/5 rounded-xl transition-shadow duration-300 hover:shadow-md"]) }}>
    {{ $slot }}
</div>
