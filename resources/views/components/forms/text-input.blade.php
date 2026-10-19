@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 rounded-xl shadow-sm transition placeholder:text-slate-500']) }}>
