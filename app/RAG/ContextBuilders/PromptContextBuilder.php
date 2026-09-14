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
            "- Nombre: {$project->name} ({$project->code})",
            '- Estado: '.$project->status->label(),
            '- Prioridad: '.$project->priority->label(),
            '- Responsable: '.($project->responsibleEmployee?->fullName() ?? 'sin asignar'),
            '- Fecha inicio: '.($project->start_date?->toDateString() ?? 'sin definir'),
            '- Fecha estimada de fin: '.($project->estimated_end_date?->toDateString() ?? 'sin definir'),
        ];

        if (filled($project->description)) {
            $lines[] = "- Descripcion: {$project->description}";
        }

        return implode("\n", $lines);
    }

    private function taskSection(Task $task): string
    {
        $lines = [
            '## Contexto de la tarea',
            "- Titulo: {$task->title}",
            '- Estado: '.$task->status->label(),
            '- Prioridad: '.$task->priority->label(),
            '- Asignado a: '.($task->assignee?->fullName() ?? 'sin asignar'),
            '- Fecha limite: '.($task->due_date?->toDateString() ?? 'sin definir'),
            '- Progreso: '.$task->progress_percentage.'%',
        ];

        if (filled($task->description)) {
            $lines[] = "- Descripcion: {$task->description}";
        }

        if (filled($task->blocked_reason)) {
            $lines[] = "- Motivo de bloqueo: {$task->blocked_reason}";
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
            $entryText = $chunk->content;
            $entryLength = mb_strlen($entryText);

            if ($usedChars > 0 && $usedChars + $entryLength > $maxChars) {
                break;
            }

            $usedChars += $entryLength;
            $title = $chunk->document?->title ?? 'documento desconocido';
            $reference = $index + 1;

            $entries[] = "[{$reference}] (Fuente: \"{$title}\", fragmento {$chunk->chunk_index})\n{$entryText}";
        }

        return "## Documentos relevantes\n".implode("\n\n", $entries);
    }
}
