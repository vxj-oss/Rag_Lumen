<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte ejecutivo</title>
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
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; color: #fff; }
        .badge-red { background: #dc2626; }
        .badge-yellow { background: #d97706; }
        .badge-green { background: #059669; }
        .badge-gray { background: #6b7280; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} &mdash; Reporte ejecutivo</h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <h2>Resumen general</h2>
    <table class="summary-table">
        <tr>
            <td><span class="summary-value">{{ $summary['total_projects'] }}</span><span class="summary-label">Proyectos totales</span></td>
            <td><span class="summary-value">{{ $summary['active_projects'] }}</span><span class="summary-label">Proyectos activos</span></td>
            <td><span class="summary-value">{{ $summary['delayed_projects'] }}</span><span class="summary-label">Proyectos atrasados</span></td>
            <td><span class="summary-value">{{ $summary['critical_projects'] }}</span><span class="summary-label">Proyectos críticos</span></td>
        </tr>
        <tr>
            <td><span class="summary-value">{{ $summary['pending_tasks'] }}</span><span class="summary-label">Tareas pendientes</span></td>
            <td><span class="summary-value">{{ $summary['overdue_tasks'] }}</span><span class="summary-label">Tareas atrasadas</span></td>
            <td><span class="summary-value">{{ $summary['completed_tasks'] }}</span><span class="summary-label">Tareas completadas</span></td>
            <td><span class="summary-value">{{ $summary['overloaded_employees'] }}</span><span class="summary-label">Empleados sobrecargados</span></td>
        </tr>
    </table>

    <h2>Proyectos que requieren atención</h2>
    @if ($attentionList->isEmpty())
        <p>No hay proyectos que requieran atención en este momento.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Nivel de riesgo</th>
                    <th>Puntaje</th>
                    <th>Señales</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attentionList as $item)
                    <tr>
                        <td>{{ $item['project']->nombre }}</td>
                        <td>
                            <span class="badge badge-{{ $item['decision']['risk_level']->color() === 'red' ? 'red' : ($item['decision']['risk_level']->color() === 'yellow' ? 'yellow' : 'gray') }}">
                                {{ $item['decision']['risk_level']->label() }}
                            </span>
                        </td>
                        <td>{{ $item['decision']['risk_score'] }}</td>
                        <td>{{ collect($item['decision']['signals'])->map(fn ($s) => $s->label())->implode(', ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Proyectos por estado</h2>
    <table>
        <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
        <tbody>
            @foreach ($projectsByStatus as $status => $count)
                <tr><td>{{ $status }}</td><td>{{ $count }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Tareas por estado</h2>
    <table>
        <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
        <tbody>
            @foreach ($tasksByStatus as $status => $count)
                <tr><td>{{ $status }}</td><td>{{ $count }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Desempeño de empleados (tareas completadas)</h2>
    <table>
        <thead><tr><th>Empleado</th><th>Tareas completadas</th></tr></thead>
        <tbody>
            @forelse ($employeePerformance as $name => $count)
                <tr><td>{{ $name }}</td><td>{{ $count }}</td></tr>
            @empty
                <tr><td colspan="2">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Reporte generado automáticamente por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
