<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectDelayedNotification extends Notification
{
    use Queueable;

    public function __construct(private Project $project, private int $overdueTasks)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->notify_by_email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Proyecto atrasado: {$this->project->nombre}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El proyecto \"{$this->project->nombre}\" tiene {$this->overdueTasks} tarea(s) atrasada(s).")
            ->action('Ver proyecto', route('projects.show', $this->project))
            ->line('Revisa el estado de las tareas para destrabar el avance.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_delayed',
            'project_id' => $this->project->id,
            'message' => "El proyecto \"{$this->project->nombre}\" tiene {$this->overdueTasks} tarea(s) atrasada(s).",
            'overdue_tasks' => $this->overdueTasks,
        ];
    }
}
