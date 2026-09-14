<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-25 disabled:pointer-events-none transition-all ease-out duration-150']) }}>
    {{ $slot }}
</button>
