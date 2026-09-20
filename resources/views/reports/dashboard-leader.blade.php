<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de líder</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        .subtitle { color: #6b7280; font-size: 10px; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        th { background: #f3f4f6; color: #374151; }
        .summary-table td { width: 25%; padding: 8px 6px; }
        .summary-value { font-size: 16px; font-weight: bold; color: #047857; display: block; }
        .summary-label { color: #6b7280; font-size: 9px; }
        .footer { margin-top: 20px; font-size: 9px; color: #9aa1ad; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} &mdash; Reporte de líder</h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <h2>Resumen</h2>
    <table class="summary-table">
        <tr>
            <td><span class="summary-value">{{ $summary['my_projects'] }}</span><span class="summary-label">Mis proyectos</span></td>
            <td><span class="summary-value">{{ $summary['overdue_tasks'] }}</span><span class="summary-label">Atrasadas</span></td>
            <td><span class="summary-value">{{ $summary['blocked_tasks'] }}</span><span class="summary-label">Bloqueadas</span></td>
            <td><span class="summary-value">{{ $summary['due_soon_tasks'] }}</span><span class="summary-label">Próximas a vencer</span></td>
        </tr>
    </table>

    <h2>Mis proyectos</h2>
    <table>
        <thead><tr><th>Proyecto</th><th>Riesgo</th><th>Avance</th><th>Atrasadas/Bloqueadas</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['project']->name }} ({{ $row['project']->code }})</td>
                    <td>{{ $row['decision']['risk_score'] }}/100 ({{ $row['decision']['risk_level']->label() }})</td>
                    <td>{{ $row['metrics']['real_progress'] }}% / {{ $row['metrics']['expected_progress'] }}%</td>
                    <td>{{ $row['metrics']['overdue_tasks'] }} / {{ $row['metrics']['blocked_tasks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin proyectos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Carga por empleado</h2>
    <table>
        <thead><tr><th>Empleado</th><th>Activas</th><th>Completadas</th><th>Atrasadas</th><th>Horas est./reales</th></tr></thead>
        <tbody>
            @forelse ($teamLoad as $row)
                <tr>
                    <td>{{ $row['employee']->fullName() }}</td>
                    <td>{{ $row['metrics']['active_tasks'] }}</td>
                    <td>{{ $row['metrics']['completed_tasks'] }}</td>
                    <td>{{ $row['metrics']['overdue_tasks'] }}</td>
                    <td>{{ $row['metrics']['estimated_hours'] }} / {{ $row['metrics']['actual_hours'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin equipo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Reporte generado automáticamente por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
