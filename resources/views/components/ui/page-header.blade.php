@props(['title', 'subtitle' => null, 'eyebrow' => null])

<h1 class="sr-only">{{ $title }}</h1>

@if(request()->routeIs('projects.show','projects.statuses.index','tasks.show','employees.show','employees.access','employees.record','clients.show','users.edit','rag-documents.source'))
    @push('workspace-record-context')
        <span class="max-w-[14rem] truncate text-sm text-slate-600" title="{{ $title }}">{{ $title }}</span>
    @endpush
@endif

@if (isset($actions) && $actions->isNotEmpty())
    @push('workspace-page-actions')
        {{ $actions }}
    @endpush
@endif
