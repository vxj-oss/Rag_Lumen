<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 min-h-[44px] bg-emerald-600 border border-transparent rounded-xl shadow-sm shadow-emerald-900/10 font-semibold text-sm text-white hover:bg-emerald-500 hover:shadow-glow hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 active:translate-y-0 active:scale-[0.97] disabled:opacity-40 disabled:pointer-events-none transition-all ease-out duration-150']) }}>
    {{ $slot }}
</button>
