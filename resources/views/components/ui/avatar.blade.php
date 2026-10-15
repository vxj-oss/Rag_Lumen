@props(['name', 'size' => 'w-10 h-10 text-sm', 'tone' => 'emerald'])

@php
    $tones = [
        'emerald' => 'from-emerald-400 to-teal-600',
        'blue' => 'from-sky-400 to-blue-600',
        'violet' => 'from-violet-400 to-indigo-600',
        'orange' => 'from-amber-400 to-orange-600',
    ];
    $iniciales = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-full bg-gradient-to-br {$size} ".($tones[$tone] ?? $tones['emerald'])." font-bold text-white shadow-sm shrink-0"]) }}>{{ $iniciales }}</span>
