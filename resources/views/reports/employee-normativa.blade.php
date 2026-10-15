<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Términos de referencia y manual de faltas</title>
    <style>
        @page { margin: 60px 50px 60px 50px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: left; }

        .titulo-doc { text-align: center; margin-bottom: 14px; }
        .titulo-doc .empresa { font-size: 11px; font-weight: bold; color: #047857; letter-spacing: 1px; }
        .titulo-doc h1 { font-size: 15px; margin: 4px 0 2px; }
        .titulo-doc .sub { font-size: 9px; color: #6b7280; }

        .seccion { background: #065f46; color: #ffffff; font-size: 11px; font-weight: bold; padding: 6px 10px; margin: 18px 0 8px; }

        table.datos td, table.datos th { border: 1px solid #d1d5db; padding: 5px 8px; font-size: 9.5px; }
        table.datos th { background: #ecfdf5; width: 24%; color: #065f46; }

        table.tdr th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 8px; font-size: 9px; text-transform: uppercase; color: #374151; }
        table.tdr td { border: 1px solid #d1d5db; padding: 6px 8px; }
        table.tdr td.num { width: 28px; text-align: center; font-weight: bold; color: #065f46; background: #ecfdf5; }
        table.tdr td.tit { width: 22%; font-weight: bold; }
        table.tdr p { margin: 0 0 4px; text-align: left; }
        table.tdr p.li { padding-left: 12px; text-indent: -9px; }
        table.entregables { margin-top: 6px; }
        table.entregables th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 3px 5px; font-size: 8.5px; text-transform: uppercase; }
        table.entregables td { border: 1px solid #d1d5db; padding: 3px 5px; font-size: 9px; }
        table.tdr tr { page-break-inside: avoid; }

        table.faltas th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 6px; font-size: 9px; text-transform: uppercase; color: #374151; }
        table.faltas td { border: 1px solid #d1d5db; padding: 5px 6px; font-size: 9.3px; }
        table.faltas tr { page-break-inside: avoid; }
        .grupo td { background: #f9fafb; font-weight: bold; font-size: 9.5px; }
        .g-leve { color: #4b5563; }
        .g-grave { color: #b45309; }
        .g-muy_grave { color: #b91c1c; }
        .cod { font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; white-space: nowrap; }
        .monto { white-space: nowrap; text-align: right; }

        .declaracion { border: 1px solid #a7f3d0; background: #ecfdf5; padding: 8px 10px; margin-top: 16px; text-align: left; }
        .firmas { margin-top: 34px; page-break-inside: avoid; }
        .firmas td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 14px; }
        .firma-caja { height: 74px; text-align: center; }
        .firma-img { height: 68px; }
        .firma-linea { border-top: 1px solid #1f2937; margin-top: 0; padding-top: 4px; font-size: 9.5px; }
        .footer { margin-top: 18px; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="titulo-doc">
        <div class="empresa">LUMEN STRATYX E.I.R.L.</div>
        <h1>TÉRMINOS DE REFERENCIA · ÁREA {{ mb_strtoupper($tdr->areaLabel()) }}</h1>
        <div class="sub">Términos de referencia para la contratación de locadores de servicio y manual de faltas y sanciones · Anexo del Convenio de Colaboración Profesional y Confidencialidad</div>
    </div>

    <table class="datos">
        <tr><th>Empresa</th><td>LUMEN STRATYX E.I.R.L. · RUC N.° 20615988902</td></tr>
        <tr><th>Área del servicio</th><td>{{ $tdr->areaLabel() }}</td></tr>
        <tr><th>Denominación de la contratación</th><td>{{ $tdr->nombre }}</td></tr>
        <tr><th>Colaborador</th><td>{{ $employee->fullName() }}</td></tr>
        <tr><th>DNI</th><td>{{ $employee->documento_identidad ?? '___________________' }}</td></tr>
        <tr><th>Cargo</th><td>{{ $employee->cargo ?? '—' }}{{ $employee->especialidad ? ' · '.$employee->especialidad->label() : '' }}</td></tr>
        <tr><th>Versión del documento</th><td>{{ $tdr->version }}</td></tr>
        <tr><th>Fecha</th><td>{{ $generatedAt->translatedFormat('d \d\e F \d\e Y') }}</td></tr>
    </table>

    <div class="seccion">PARTE I · TÉRMINOS DE REFERENCIA (TDR)</div>

    <table class="tdr">
        <thead>
            <tr><th>N.°</th><th>Sección</th><th>Contenido</th></tr>
        </thead>
        <tbody>
            @foreach ($tdr->secciones() as $seccion)
                <tr>
                    <td class="num">{{ $seccion['numero'] ?? $loop->iteration }}</td>
                    <td class="tit">{{ $seccion['titulo'] }}</td>
                    <td>
                        @foreach ($seccion['lineas'] as $linea)
                            @if ($linea['tipo'] === 'li')
                                <p class="li">• {{ $linea['texto'] }}</p>
                            @else
                                <p>{{ $linea['texto'] }}</p>
                            @endif
                        @endforeach

                        @if (str_starts_with(mb_strtoupper($seccion['titulo']), 'ENTREGABLES') && ! empty($tdr->entregables))
                            <table class="entregables">
                                <thead>
                                    <tr><th style="width:8%">N.°</th><th>Entregable o producto</th><th style="width:34%">Plazo de presentación</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($tdr->entregables as $entregable)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $entregable['producto'] }}</td>
                                            <td>{{ $entregable['plazo'] }}</td>
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

    <div class="seccion">PARTE II · MANUAL DE FALTAS Y SANCIONES</div>

    @if ($faltas->isEmpty())
        <p>No hay faltas vigentes registradas.</p>
    @else
        <table class="faltas">
            <thead>
                <tr>
                    <th style="width:9%">Código</th>
                    <th style="width:32%">Falta</th>
                    <th style="width:28%">Descripción</th>
                    <th style="width:21%">Sanción</th>
                    <th style="width:10%">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\App\Models\Falta::GRAVEDADES as $clave => $etiqueta)
                    @php $grupo = $faltas->where('gravedad', $clave); @endphp
                    @continue($grupo->isEmpty())
                    <tr class="grupo"><td colspan="5" class="g-{{ $clave }}">FALTAS {{ mb_strtoupper($etiqueta) }}S ({{ $grupo->count() }})</td></tr>
                    @foreach ($grupo as $falta)
                        <tr>
                            <td class="cod">{{ $falta->codigo }}</td>
                            <td><strong>{{ $falta->titulo }}</strong></td>
                            <td>{{ $falta->descripcion }}</td>
                            <td>{{ $falta->sancion ?? '—' }}</td>
                            <td class="monto">{{ $falta->monto !== null ? 'S/ '.number_format($falta->monto, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="seccion">PARTE III · ACEPTACIÓN</div>

    <div class="declaracion">
        EL COLABORADOR declara haber leído, comprendido y aceptado los presentes términos de referencia y el manual de
        faltas y sanciones en su versión {{ $tdr->version }}, los cuales forman parte integrante del convenio suscrito con LA EMPRESA.
    </div>

    <div class="firmas">
        <table>
            <tr>
                <td>
                    <div class="firma-caja">@if ($empresaFirma)<img src="{{ $empresaFirma }}" alt="Firma LA EMPRESA" class="firma-img">@endif</div>
                    <div class="firma-linea">LUMEN STRATYX E.I.R.L.<br>Gerente General</div>
                </td>
                <td>
                    <div class="firma-caja">@if ($employee->firmaBase64())<img src="{{ $employee->firmaBase64() }}" alt="Firma colaborador" class="firma-img">@endif</div>
                    <div class="firma-linea">{{ $employee->fullName() }}<br>COLABORADOR(A) · DNI {{ $employee->documento_identidad ?? '__________' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <p style="text-align:center; font-style: italic; margin-top: 16px;">
        @if ($employee->aceptoTdr($tdr) && $employee->normativa_aceptada_en)
            Aceptado el {{ $employee->normativa_aceptada_en->translatedFormat('d \d\e F \d\e Y \a \l\a\s H:i') }} (versión {{ $tdr->version }}).
        @else
            Firmado el {{ $generatedAt->translatedFormat('d \d\e F \d\e Y') }}.
        @endif
    </p>

    <p class="footer">Documento generado automáticamente por {{ config('app.name') }} el {{ $generatedAt->format('d/m/Y H:i') }}.</p>
</body>
</html>
