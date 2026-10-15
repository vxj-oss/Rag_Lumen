@php $esAdmin = auth()->user()->isAdmin(); @endphp
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Normativa')" :subtitle="__('TDR por área y manual de faltas y sanciones')">
            @if ($esAdmin)
                <x-slot name="actions">
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-falta')" class="min-h-[44px]">
                        {{ __('Nueva falta') }}
                    </x-ui.primary-button>
                </x-slot>
            @endif
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Un apartado por área --}}
            <div class="flex flex-wrap gap-2" role="tablist">
                @foreach ($areas as $clave => $etiqueta)
                    @php $temaTab = \App\Support\WorkspaceAccess::tema($clave); @endphp
                    <a href="{{ route('normativa.index', ['area' => $clave]) }}" role="tab" aria-selected="{{ $clave === $area ? 'true' : 'false' }}"
                       class="tab-pill inline-flex items-center gap-2 px-4 py-2 min-h-[44px] rounded-xl text-sm font-semibold active:scale-95
                              {{ $clave === $area ? $temaTab['tab'] : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                        <span class="w-2 h-2 rounded-full {{ $clave === $area ? 'bg-white' : $temaTab['dot'] }}"></span>
                        {{ $etiqueta }}
                    </a>
                @endforeach
            </div>

            <x-ui.card padding="p-6 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">{{ __('TDR · Área :area · versión :v', ['area' => $tdr->areaLabel(), 'v' => $tdr->version]) }}</p>
                        <h3 class="font-semibold text-slate-900 mt-1">{{ $tdr->nombre }}</h3>
                    </div>
                    @if ($esAdmin)
                        <button x-data @click="$dispatch('open-modal', 'edit-tdr')" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">{{ __('Editar TDR') }}</button>
                    @endif
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-12">{{ __('N.°') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-1/4">{{ __('Sección') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Contenido') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($tdr->secciones() as $seccion)
                                <tr>
                                    <td class="px-4 py-3 align-top font-semibold text-emerald-700 bg-emerald-50/50">{{ $seccion['numero'] ?? $loop->iteration }}</td>
                                    <td class="px-4 py-3 align-top font-medium text-slate-900">{{ $seccion['titulo'] }}</td>
                                    <td class="px-4 py-3 align-top text-slate-700 space-y-1">
                                        @php $enLista = false; @endphp
                                        @foreach ($seccion['lineas'] as $linea)
                                            @if ($linea['tipo'] === 'li')
                                                <p class="pl-4 relative"><span class="absolute left-0 text-emerald-600">•</span>{{ $linea['texto'] }}</p>
                                            @else
                                                <p>{{ $linea['texto'] }}</p>
                                            @endif
                                        @endforeach

                                        @if (str_starts_with(mb_strtoupper($seccion['titulo']), 'ENTREGABLES') && ! empty($tdr->entregables))
                                            <table class="mt-2 w-full border border-slate-200 text-xs">
                                                <thead class="bg-slate-50">
                                                    <tr>
                                                        <th class="px-3 py-1.5 text-left font-semibold text-slate-500 uppercase w-10">{{ __('N.°') }}</th>
                                                        <th class="px-3 py-1.5 text-left font-semibold text-slate-500 uppercase">{{ __('Entregable o producto') }}</th>
                                                        <th class="px-3 py-1.5 text-left font-semibold text-slate-500 uppercase w-1/3">{{ __('Plazo de presentación') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach ($tdr->entregables as $entregable)
                                                        <tr>
                                                            <td class="px-3 py-1.5 font-semibold text-emerald-700">{{ $loop->iteration }}</td>
                                                            <td class="px-3 py-1.5">{{ $entregable['producto'] }}</td>
                                                            <td class="px-3 py-1.5 text-slate-600">{{ $entregable['plazo'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-400">{{ __('Al editar este TDR o sus faltas, la versión sube y los colaboradores del área deben volver a aceptarlo y firmarlo.') }}</p>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800">{{ __('Manual de faltas y sanciones · :area', ['area' => $tdr->areaLabel()]) }}</h3>
                    <p class="text-xs text-slate-500">{{ __('Incluye las faltas generales y las propias del área.') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Código') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Falta') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Aplica a') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Gravedad') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Sanción') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Monto') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($faltas as $falta)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-mono text-slate-700">{{ $falta->codigo }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <p class="font-medium text-slate-900">{{ $falta->titulo }}</p>
                                        <p class="text-slate-500">{{ $falta->descripcion }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $falta->areaLabel() }}</td>
                                    <td class="px-6 py-4"><x-ui.badge :color="$falta->gravedadColor()">{{ $falta->gravedadLabel() }}</x-ui.badge></td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $falta->sancion ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ $falta->monto !== null ? 'S/ '.number_format($falta->monto, 2) : '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        @if ($esAdmin)
                                            <div class="flex items-center justify-end gap-3">
                                                <button x-data @click="$dispatch('open-modal', 'edit-falta-{{ $falta->id }}')" class="text-emerald-600 hover:text-emerald-800 font-medium">{{ __('Editar') }}</button>
                                                <form action="{{ route('normativa.faltas.destroy', $falta) }}" method="POST" @submit.prevent="if (confirm(@js(__('¿Eliminar esta falta?')))) $el.submit()">
                                                    @csrf @method('DELETE')
                                                    <input type="hidden" name="_area" value="{{ $area }}">
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">{{ __('Eliminar') }}</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>

                                @if ($esAdmin)
                                    <x-ui.form-modal :name="'edit-falta-'.$falta->id" :title="__('Editar falta')" :subtitle="$falta->codigo"
                                        :action="route('normativa.faltas.update', $falta)" method="PUT">
                                        <x-slot name="hidden">
                                            <input type="hidden" name="_edit_id" value="{{ $falta->id }}">
                                            <input type="hidden" name="_area" value="{{ $area }}">
                                        </x-slot>
                                        @include('normativa.html.partials.falta-form')
                                    </x-ui.form-modal>
                                @endif
                            @empty
                                <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">{{ __('Todavía no hay faltas registradas para esta área.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>

    @if ($esAdmin)
        <x-ui.form-modal name="edit-tdr" :title="__('Editar TDR de :area', ['area' => $tdr->areaLabel()])" :subtitle="__('Subirá la versión del documento')"
            :action="route('normativa.tdr.update', $tdr)" method="PUT" max-width="4xl">
            <div>
                <x-forms.input-label :value="__('Denominación de la contratación')" />
                <x-forms.text-input name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $tdr->nombre)" required />
                <x-forms.input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>
            <div>
                <x-forms.input-label :value="__('Secciones')" />
                <p class="text-xs text-slate-400">{{ __('Separa cada sección con una línea en blanco. La primera línea es el título (ej. "3. ALCANCES DEL SERVICIO"); las líneas que empiezan con "- " son viñetas. La sección "ENTREGABLES" muestra la tabla de abajo.') }}</p>
                <textarea name="contenido" rows="16" required
                    class="mt-1 block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm font-mono">{{ old('contenido', $tdr->contenido) }}</textarea>
                <x-forms.input-error :messages="$errors->get('contenido')" class="mt-2" />
            </div>
            <div x-data="{ filas: @js(old('entregables', $tdr->entregables ?? [])) }" class="space-y-2">
                <div class="flex items-center justify-between">
                    <x-forms.input-label :value="__('Entregables')" />
                    <button type="button" @click="filas.push({ producto: '', plazo: '' })" class="text-xs font-medium text-emerald-600 hover:underline">{{ __('+ Agregar entregable') }}</button>
                </div>
                <template x-for="(fila, i) in filas" :key="i">
                    <div class="flex items-start gap-2 bg-slate-50 rounded-xl p-2">
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" :name="'entregables[' + i + '][producto]'" x-model="fila.producto" required placeholder="{{ __('Entregable o producto') }}"
                                class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                            <input type="text" :name="'entregables[' + i + '][plazo]'" x-model="fila.plazo" required placeholder="{{ __('Plazo de presentación') }}"
                                class="block w-full border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 transition rounded-xl shadow-sm text-sm">
                        </div>
                        <button type="button" @click="filas.splice(i, 1)" class="text-slate-400 hover:text-red-600 shrink-0 p-2">{{ __('Quitar') }}</button>
                    </div>
                </template>
                <x-forms.input-error :messages="array_merge($errors->get('entregables'), $errors->get('entregables.*'))" class="mt-2" />
            </div>
        </x-ui.form-modal>

        <x-ui.form-modal name="create-falta" :title="__('Nueva falta')" :subtitle="__('Se añade al manual y queda disponible al generar actas')"
            :action="route('normativa.faltas.store')" submit-label="{{ __('Registrar falta') }}">
            <x-slot name="hidden">
                <input type="hidden" name="_form" value="create">
                <input type="hidden" name="_area" value="{{ $area }}">
            </x-slot>
            @php $falta = null; @endphp
            @include('normativa.html.partials.falta-form')
        </x-ui.form-modal>

        @if ($errors->any())
            @php $modalToOpen = ($errors->hasAny(['nombre', 'contenido']) || $errors->has('entregables.*') || $errors->has('entregables')) ? 'edit-tdr' : (old('_edit_id') ? 'edit-falta-'.old('_edit_id') : (old('_form') === 'create' ? 'create-falta' : null)); @endphp
            @if ($modalToOpen)
                <div x-data x-init="$dispatch('open-modal', @js($modalToOpen))"></div>
            @endif
        @endif
    @endif
</x-app-layout>
