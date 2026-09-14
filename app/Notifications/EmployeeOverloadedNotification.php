<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeOverloadedNotification extends Notification
{
    use Queueable;

    public function __construct(private Employee $employee, private int $activeTasks)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->notify_by_email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Empleado sobrecargado: {$this->employee->fullName()}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El empleado \"{$this->employee->fullName()}\" tiene una carga de trabajo superior al límite establecido ({$this->activeTasks} tareas activas).")
            ->action('Ver empleado', route('employees.show', $this->employee))
            ->line('Considera redistribuir tareas para equilibrar la carga.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'employee_overloaded',
            'employee_id' => $this->employee->id,
            'message' => "El empleado \"{$this->employee->fullName()}\" tiene una carga de trabajo superior al límite establecido ({$this->activeTasks} tareas activas).",
            'active_tasks' => $this->activeTasks,
        ];
    }
}
