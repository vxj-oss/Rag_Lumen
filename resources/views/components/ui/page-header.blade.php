@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-bold tracking-[0.2em] text-emerald-600 uppercase">{{ $eyebrow }}</p>
        @endif
        <h2 class="font-extrabold text-2xl text-slate-900 leading-tight tracking-tight break-words">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-slate-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
