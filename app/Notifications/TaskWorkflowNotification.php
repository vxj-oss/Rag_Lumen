<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskWorkflowNotification extends Notification
{
    public function __construct(private Task $task, private string $event, private string $message, private string $key) {}

    public function via(object $notifiable): array
    {
        return $notifiable->notificationChannels();
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'task_event', 'event' => $this->event, 'task_id' => $this->task->tarea_padre_id ?? $this->task->id, 'project_id' => $this->task->proyecto_id, 'message' => $this->message, 'delivery_key' => $this->key];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Actualización de actividad')->line($this->message)->action('Abrir actividad', route('tasks.show', $this->task->tarea_padre_id ?? $this->task->id));
    }
}
