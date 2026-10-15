<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 min-h-[44px] bg-white border border-slate-200 rounded-xl font-semibold text-sm text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:opacity-40 disabled:pointer-events-none transition-all ease-out duration-150']) }}>
    {{ $slot }}
</button>
