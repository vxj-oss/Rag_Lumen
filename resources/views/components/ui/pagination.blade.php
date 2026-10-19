@if($paginator->hasPages())
<nav role="navigation" aria-label="Paginación de registros" class="flex flex-col items-center justify-between gap-3 py-3 sm:flex-row">
    <p class="text-xs text-slate-600">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} · {{ $paginator->total() }} registros</p>
    <div class="flex flex-wrap items-center justify-center gap-1">
        @if($paginator->onFirstPage())<span aria-disabled="true" class="px-3 py-2 text-slate-400">‹</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior" class="workspace-button">‹</a>@endif
        @php $start=max(1,min($paginator->currentPage()-2,$paginator->lastPage()-4)); $end=min($paginator->lastPage(),$start+4); @endphp
        @for($i=$start;$i<=$end;$i++)
            @if($i===$paginator->currentPage())<span aria-current="page" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">{{ $i }}</span>@else<a href="{{ $paginator->url($i) }}" aria-label="Ir a página {{ $i }}" class="workspace-button">{{ $i }}</a>@endif
        @endfor
        @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente" class="workspace-button">›</a>@else<span aria-disabled="true" class="px-3 py-2 text-slate-400">›</span>@endif
    </div>
</nav>
@endif
