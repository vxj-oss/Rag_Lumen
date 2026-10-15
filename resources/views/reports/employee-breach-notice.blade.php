<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta de incumplimiento</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 16px; }
        .empresa { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        p { margin: 0 0 10px; text-align: justify; }
        .clausula-titulo { font-weight: bold; margin: 14px 0 4px; }
        .datos-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .datos-table td { padding: 3px 0; font-size: 10.5px; }
        .datos-label { color: #6b7280; width: 180px; }
        .hechos-box { border: 1px solid #d1d5db; border-radius: 4px; padding: 10px; background: #f9fafb; white-space: pre-line; }
        .monto-box { border: 1px solid #fca5a5; border-radius: 4px; padding: 10px; background: #fef2f2; margin-top: 8px; }
        .firmas { margin-top: 40px; width: 100%; }
        .firmas table { width: 100%; border-collapse: collapse; }
        .firmas td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 10px; }
        .firma-caja { height: 74px; text-align: center; }
        .firma-img { height: 68px; }
        .firma-linea { border-top: 1px solid #1f2937; margin-top: 0; padding-top: 4px; }
        .footer { margin-top: 24px; font-size: 8.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <p class="empresa">LUMEN STRATYX E.I.R.L.</p>
    <h1>ACTA DE INCUMPLIMIENTO CONTRACTUAL Y COMPROMISO DE RESARCIMIENTO</h1>

    <p>
        Conste por el presente documento, suscrito el {{ $generatedAt->translatedFormat('d \d\e F \d\e Y') }}, de una parte,
        LUMEN STRATYX E.I.R.L., identificada con RUC N.° 20615988902, debidamente representada por su
        Gerente General, Martín Alonso Andrés Puertas Cuadros, identificado con DNI N.° 70621234, a quien en
        adelante se le denominará "LA EMPRESA"; y de la otra parte, {{ $employee->fullName() }},
        identificado(a) con DNI N.° {{ $employee->documento_identidad ?? '___________________' }}, a quien en adelante se le
        denominará "EL COLABORADOR"; en los términos siguientes:
    </p>

    <p class="clausula-titulo">PRIMERA: ANTECEDENTES</p>
    <p>
        Las partes se encuentran vinculadas mediante el Convenio de Colaboración Profesional y Confidencialidad
        suscrito entre LA EMPRESA y EL COLABORADOR, cuya Cláusula Cuarta (Desvinculación anticipada) y
        Cláusula Décima Cuarta (Incumplimientos) resultan aplicables a los hechos descritos en la presente acta.
    </p>

    <p class="clausula-titulo">SEGUNDA: HECHOS CONSTATADOS</p>
    <table class="datos-table">
        <tr>
            <td class="datos-label">Fecha de los hechos:</td>
            <td>{{ $fechaHechos->translatedFormat('d \d\e F \d\e Y') }}</td>
        </tr>
        @if ($clausulaInfringida)
        <tr>
            <td class="datos-label">Cláusula del convenio infringida:</td>
            <td>{{ $clausulaInfringida }}</td>
        </tr>
        @endif
    </table>
    <div class="hechos-box">{{ $hechos }}</div>

    <p class="clausula-titulo">TERCERA: RESOLUCIÓN DEL CONVENIO</p>
    <p>
        En virtud de los hechos descritos, y conforme a lo previsto en la Cláusula Cuarta del Convenio de
        Colaboración Profesional y Confidencialidad, LA EMPRESA da por concluida de manera inmediata la
        relación de colaboración con EL COLABORADOR, sin perjuicio de la obligación de confidencialidad y
        protección de datos personales, que permanece vigente conforme a lo pactado en dicho convenio.
    </p>

    @if ($monto)
    <p class="clausula-titulo">CUARTA: COMPROMISO DE RESARCIMIENTO</p>
    <p>
        EL COLABORADOR reconoce de manera voluntaria su responsabilidad respecto de los hechos descritos en
        la presente acta y se compromete a resarcir a LA EMPRESA por los daños y perjuicios ocasionados.
    </p>
    <div class="monto-box">
        <strong>Monto reconocido:</strong> S/ {{ number_format($monto, 2) }}
    </div>
    <p style="margin-top:8px;">
        La forma y el plazo de pago serán acordados directamente entre las partes. Este reconocimiento no
        excluye las acciones legales, administrativas o indemnizatorias adicionales que pudieran corresponder
        conforme a la legislación vigente.
    </p>
    @endif

    <p class="clausula-titulo">QUINTA: CONFORMIDAD</p>
    <p>
        Ambas partes declaran haber leído y comprendido el contenido íntegro del presente documento, y lo
        suscriben en señal de plena conformidad con lo aquí expuesto.
    </p>

    <div class="firmas">
        <table>
            <tr>
                <td>
                    <div class="firma-caja">@if ($empresaFirma)<img src="{{ $empresaFirma }}" alt="Firma LA EMPRESA" class="firma-img">@endif</div>
                    <div class="firma-linea">
                        LUMEN STRATYX E.I.R.L.<br>
                        Gerente General
                    </div>
                </td>
                <td>
                    <div class="firma-caja">@if ($employee->firmaBase64())<img src="{{ $employee->firmaBase64() }}" alt="Firma colaborador" class="firma-img">@endif</div>
                    <div class="firma-linea">
                        {{ $employee->fullName() }}<br>
                        COLABORADOR(A)
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <p class="footer">
        Documento generado automáticamente por {{ config('app.name') }} el {{ $generatedAt->format('d/m/Y H:i') }}.
        Este documento es una plantilla operativa; se recomienda su revisión por un asesor legal antes de su uso formal.
    </p>
</body>
</html>
