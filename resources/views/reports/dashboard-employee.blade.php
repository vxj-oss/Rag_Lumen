<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mi día</title>
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
    <h1>{{ config('app.name') }} &mdash; Mi día</h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <h2>Resumen</h2>
    <table class="summary-table">
        <tr>
            <td><span class="summary-value">{{ $summary['pending'] }}</span><span class="summary-label">Pendientes</span></td>
            <td><span class="summary-value">{{ $summary['in_progress'] }}</span><span class="summary-label">En progreso</span></td>
            <td><span class="summary-value">{{ $summary['due_soon'] }}</span><span class="summary-label">Por vencer</span></td>
            <td><span class="summary-value">{{ $summary['blocked'] }}</span><span class="summary-label">Bloqueadas</span></td>
            <td><span class="summary-value">{{ $summary['hours_recorded'] }}</span><span class="summary-label">Horas registradas</span></td>
        </tr>
    </table>

    @foreach ($byState as $stateName => $tasks)
        <h2>{{ $stateName }} ({{ $tasks->count() }})</h2>
        <table>
            <thead><tr><th>Código</th><th>Tarea</th><th>Proyecto</th><th>Vence</th><th>Avance</th></tr></thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr>
                        <td>{{ $task->code }}</td>
                        <td>{{ $task->title }}</td>
                        <td>{{ $task->project?->name ?? 'Proyecto archivado' }}</td>
                        <td>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $task->progress_percentage }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <p class="footer">Reporte generado automáticamente por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
