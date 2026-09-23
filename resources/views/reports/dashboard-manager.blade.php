<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de gerencia</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        .subtitle { color: #6b7280; font-size: 10px; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        th { background: #f3f4f6; color: #374151; }
        .summary-table td { width: 20%; padding: 8px 6px; }
        .summary-value { font-size: 16px; font-weight: bold; color: #047857; display: block; }
        .summary-label { color: #6b7280; font-size: 9px; }
        .footer { margin-top: 20px; font-size: 9px; color: #9aa1ad; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} &mdash; Reporte de gerencia</h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }} &middot; Alcance del gerente</p>

    <h2>Resumen</h2>
    <table class="summary-table">
        <tr>
            <td><span class="summary-value">{{ $summary['scope_projects'] }}</span><span class="summary-label">Proyectos</span></td>
            <td><span class="summary-value">{{ $summary['at_risk_projects'] }}</span><span class="summary-label">En riesgo</span></td>
            <td><span class="summary-value">{{ $summary['delayed_projects'] }}</span><span class="summary-label">Atrasados</span></td>
            <td><span class="summary-value">{{ $summary['overdue_tasks'] }}</span><span class="summary-label">Tareas atrasadas</span></td>
            <td><span class="summary-value">{{ number_format($summary['total_budget'], 0) }}</span><span class="summary-label">Presupuesto total</span></td>
        </tr>
    </table>

    <h2>Proyectos</h2>
    <table>
        <thead><tr><th>Proyecto</th><th>Cliente</th><th>Riesgo</th><th>Avance</th><th>Presupuesto</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['project']->nombre }} ({{ $row['project']->codigo }})</td>
                    <td>{{ $row['project']->client?->nombre ?? '—' }}</td>
                    <td>{{ $row['decision']['risk_score'] }}/100 ({{ $row['decision']['risk_level']->label() }})</td>
                    <td>{{ $row['metrics']['real_progress'] }}% / {{ $row['metrics']['expected_progress'] }}%</td>
                    <td>{{ $row['project']->presupuesto ? number_format((float) $row['project']->presupuesto, 0) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin proyectos en alcance.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Rendimiento por área</h2>
    <table>
        <thead><tr><th>Área</th><th>Tareas</th><th>Completadas</th><th>Activas</th><th>Atrasadas</th></tr></thead>
        <tbody>
            @forelse ($areaPerformance as $row)
                <tr>
                    <td>{{ $row['area']->nombre }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['completed'] }}</td>
                    <td>{{ $row['active'] }}</td>
                    <td>{{ $row['overdue'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Reporte generado automáticamente por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
