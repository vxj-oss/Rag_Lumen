<?php

namespace App\RAG\ContextBuilders;

use App\Models\Project;
use App\Models\Task;

class PromptContextBuilder implements ContextBuilderInterface
{
    public function build(string $query, array $retrieved, ?Project $project = null, ?Task $task = null): string
    {
        $sections = [
            $this->instructionsSection(),
        ];

        if ($project !== null) {
            $sections[] = $this->projectSection($project);
        }

        if ($task !== null) {
            $sections[] = $this->taskSection($task);
        }

        $sections[] = $this->documentsSection($retrieved);
        $sections[] = "## Pregunta del usuario\n{$query}";

        return implode("\n\n", array_filter($sections));
    }

    private function instructionsSection(): string
    {
        return 'Eres un asistente de gestion de proyectos. Responde la pregunta del usuario '
            .'basandote EXCLUSIVAMENTE en la informacion proporcionada en las secciones siguientes. '
            .'Si la respuesta no se encuentra en el contexto, indica que no dispones de esa informacion '
            .'en lugar de inventarla. Cuando uses un documento, cita su referencia entre corchetes, por ejemplo [1].';
    }

    private function projectSection(Project $project): string
    {
        $lines = [
            '## Contexto del proyecto',
            "- Nombre: {$project->nombre} ({$project->codigo})",
            '- Estado: '.$project->estado->label(),
            '- Prioridad: '.$project->prioridad->label(),
            '- Responsable: '.($project->responsibleEmployee?->fullName() ?? 'sin asignar'),
            '- Fecha inicio: '.($project->fecha_inicio?->toDateString() ?? 'sin definir'),
            '- Fecha estimada de fin: '.($project->fecha_fin_estimada?->toDateString() ?? 'sin definir'),
        ];

        if (filled($project->descripcion)) {
            $lines[] = "- Descripcion: {$project->descripcion}";
        }

        return implode("\n", $lines);
    }

    private function taskSection(Task $task): string
    {
        $lines = [
            '## Contexto de la tarea',
            "- Titulo: {$task->titulo}",
            '- Estado: '.$task->estado->label(),
            '- Prioridad: '.$task->prioridad->label(),
            '- Asignado a: '.($task->assignee?->fullName() ?? 'sin asignar'),
            '- Fecha limite: '.($task->fecha_vencimiento?->toDateString() ?? 'sin definir'),
            '- Progreso: '.$task->porcentaje_progreso.'%',
        ];

        if (filled($task->descripcion)) {
            $lines[] = "- Descripcion: {$task->descripcion}";
        }

        if (filled($task->motivo_bloqueo)) {
            $lines[] = "- Motivo de bloqueo: {$task->motivo_bloqueo}";
        }

        return implode("\n", $lines);
    }

    
    private function documentsSection(array $retrieved): string
    {
        if ($retrieved === []) {
            return "## Documentos relevantes\nNo se encontraron documentos relevantes para esta consulta.";
        }

        $maxChars = (int) config('rag.context_max_chars');
        $usedChars = 0;
        $entries = [];

        foreach ($retrieved as $index => $result) {
            $chunk = $result['chunk'];
            $entryText = $chunk->contenido;
            $entryLength = mb_strlen($entryText);

            if ($usedChars > 0 && $usedChars + $entryLength > $maxChars) {
                break;
            }

            $usedChars += $entryLength;
            $title = $chunk->document?->titulo ?? 'documento desconocido';
            $reference = $index + 1;

            $entries[] = "[{$reference}] (Fuente: \"{$title}\", fragmento {$chunk->indice_fragmento})\n{$entryText}";
        }

        return "## Documentos relevantes\n".implode("\n\n", $entries);
    }
}
