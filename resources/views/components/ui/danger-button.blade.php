<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-red-600 border border-transparent rounded-lg shadow-sm font-semibold text-sm text-white hover:bg-red-500 hover:shadow-md hover:-translate-y-0.5 active:bg-red-700 active:translate-y-0 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-40 disabled:pointer-events-none transition-all ease-out duration-150']) }}>
    {{ $slot }}
</button>
