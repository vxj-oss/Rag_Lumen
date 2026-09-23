<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de proyecto</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        .subtitle { color: #6b7280; font-size: 10px; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        th { background: #f3f4f6; color: #374151; }
        .kv-table td { width: 50%; padding: 3px 6px; }
        .kv-label { color: #6b7280; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9px; color: #fff; margin-right: 4px; }
        .badge-red { background: #dc2626; }
        .badge-yellow { background: #d97706; }
        .badge-green { background: #059669; }
        .badge-blue { background: #2563eb; }
        .badge-gray { background: #6b7280; }
        ul { margin: 4px 0; padding-left: 16px; }
        li { margin-bottom: 3px; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>{{ $project->nombre }} <span style="color:#9ca3af;font-weight:normal;">({{ $project->codigo }})</span></h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <div>
        <span class="badge badge-{{ $project->estado->color() }}">{{ $project->estado->label() }}</span>
        <span class="badge badge-{{ $project->prioridad->color() }}">Prioridad: {{ $project->prioridad->label() }}</span>
        <span class="badge badge-{{ $risk['level']->color() }}">Riesgo: {{ $risk['level']->label() }} ({{ $risk['score'] }})</span>
    </div>

    <h2>Datos generales</h2>
    <table class="kv-table">
        <tr>
            <td><span class="kv-label">Responsable:</span> {{ $project->responsibleEmployee?->fullName() ?? 'sin asignar' }}</td>
            <td><span class="kv-label">Tipo:</span> {{ $project->tipo->label() }}</td>
        </tr>
        <tr>
            <td><span class="kv-label">Fecha inicio:</span> {{ $project->fecha_inicio?->toDateString() ?? '—' }}</td>
            <td><span class="kv-label">Fecha estimada de fin:</span> {{ $project->fecha_fin_estimada?->toDateString() ?? '—' }}</td>
        </tr>
    </table>
    @if ($project->descripcion)
        <p>{{ $project->descripcion }}</p>
    @endif

    <h2>Métricas de avance</h2>
    <table class="kv-table">
        <tr>
            <td><span class="kv-label">Avance real:</span> {{ $metrics['real_progress'] }}%</td>
            <td><span class="kv-label">Avance esperado:</span> {{ $metrics['expected_progress'] }}%</td>
        </tr>
        <tr>
            <td><span class="kv-label">Brecha de avance:</span> {{ $metrics['progress_gap'] }} pts</td>
            <td><span class="kv-label">Cumplimiento a tiempo:</span> {{ $metrics['on_time_completion_rate'] !== null ? $metrics['on_time_completion_rate'].'%' : 'sin datos' }}</td>
        </tr>
        <tr>
            <td><span class="kv-label">Tareas totales:</span> {{ $metrics['total_tasks'] }}</td>
            <td><span class="kv-label">Completadas:</span> {{ $metrics['completed_tasks'] }}</td>
        </tr>
        <tr>
            <td><span class="kv-label">Atrasadas:</span> {{ $metrics['overdue_tasks'] }}</td>
            <td><span class="kv-label">Bloqueadas:</span> {{ $metrics['blocked_tasks'] }}</td>
        </tr>
    </table>

    <h2>Diagnóstico y recomendaciones</h2>
    <p><strong>Señales detectadas:</strong>
        @foreach ($decision['signals'] as $signal)
            <span class="badge badge-{{ $signal->color() }}">{{ $signal->label() }}</span>
        @endforeach
    </p>
    <p><strong>Motivos:</strong></p>
    <ul>
        @foreach ($decision['reasons'] as $reason)
            <li>{{ $reason }}</li>
        @endforeach
    </ul>
    @if (! empty($decision['recommended_actions']))
        <p><strong>Acciones recomendadas:</strong></p>
        <ul>
            @foreach ($decision['recommended_actions'] as $action)
                <li>{{ $action }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Tareas del proyecto</h2>
    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Asignado</th>
                <th>Estado</th>
                <th>Avance</th>
                <th>Vence</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($project->tasks as $task)
                <tr>
                    <td>{{ $task->titulo }}</td>
                    <td>{{ $task->assignee?->fullName() ?? '—' }}</td>
                    <td>{{ $task->estado->label() }}</td>
                    <td>{{ $task->porcentaje_progreso }}%</td>
                    <td>{{ $task->fecha_vencimiento?->toDateString() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin tareas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Reporte generado automáticamente por {{ config('app.name') }}. Uso interno.</p>
</body>
</html>
