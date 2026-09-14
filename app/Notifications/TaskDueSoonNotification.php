<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(private Task $task, private int $daysRemaining)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->notify_by_email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tarea próxima a vencer: {$this->task->title}")
            ->greeting("Hola {$notifiable->name},")
            ->line("La tarea \"{$this->task->title}\" vence en {$this->daysRemaining} día(s) y tiene solamente {$this->task->progress_percentage}% de avance.")
            ->action('Ver tarea', route('tasks.show', $this->task))
            ->line('Registra tu avance para mantener el estado actualizado.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_due_soon',
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'message' => "La tarea \"{$this->task->title}\" vence en {$this->daysRemaining} día(s) y tiene solamente {$this->task->progress_percentage}% de avance.",
            'progress_percentage' => $this->task->progress_percentage,
        ];
    }
}
