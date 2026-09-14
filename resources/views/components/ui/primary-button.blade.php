<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 border border-transparent rounded-lg shadow-sm shadow-emerald-900/10 font-semibold text-sm text-white hover:from-emerald-500 hover:to-teal-500 hover:shadow-lg hover:shadow-emerald-900/20 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:translate-y-0 active:scale-[0.97] disabled:opacity-40 disabled:pointer-events-none transition-all ease-out duration-150']) }}>
    {{ $slot }}
</button>
