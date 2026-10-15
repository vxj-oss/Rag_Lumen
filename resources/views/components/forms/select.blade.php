@props(['options' => [], 'selected' => null])

<select {{ $attributes->merge(['class' => 'border-slate-200 bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 rounded-xl shadow-sm transition']) }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
