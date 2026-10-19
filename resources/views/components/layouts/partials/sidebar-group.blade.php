{{-- Apartado plegable del menú lateral. Se incluye desde sidebar.blade.php y comparte su scope de Alpine. --}}
<div>
    <button type="button" x-show="!collapsed" @click="alternar('{{ $clave }}', {{ $porDefecto }})"
            class="w-full flex items-center justify-between px-3 pt-3 pb-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-500 hover:text-slate-300 transition">
        {{ __($etiqueta) }}
        <svg class="w-3 h-3 transition-transform duration-300" :class="abierto('{{ $clave }}', {{ $porDefecto }}) ? 'rotate-0' : '-rotate-90'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
    </button>
    <div x-show="collapsed" x-cloak class="mx-3 my-2 border-t border-white/10"></div>

    <div x-show="collapsed || abierto('{{ $clave }}', {{ $porDefecto }})" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-0.5">
        @foreach ($items as $item)
            @php $activo = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}" @if($activo) aria-current="page" @endif @mouseenter="mostrarTip($event, @js(__($item['label'])))" @mouseleave="tip.show = false"
               class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-semibold transition-all duration-200 {{ $claseItem($activo) }}"
               :class="collapsed && 'lg:justify-center lg:px-0'">
                @if ($activo)<span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,.8)]"></span>@endif
                <svg class="w-[18px] h-[18px] shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $activo ? 'text-emerald-400' : '' }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">{!! $icons[$item['icon']] !!}</svg>
                <span x-show="!collapsed" class="truncate">{{ __($item['label']) }}</span>
            </a>
        @endforeach
    </div>
</div>
