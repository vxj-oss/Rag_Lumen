@props(['options' => [], 'selected' => null])

<select {{ $attributes->merge(['class' => 'border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm']) }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
