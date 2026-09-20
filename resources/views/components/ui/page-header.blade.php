@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-[11px] font-bold tracking-[0.2em] text-emerald-600 uppercase">{{ $eyebrow }}</p>
        @endif
        <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
