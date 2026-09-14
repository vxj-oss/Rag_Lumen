<?php

namespace App\Notifications;

use App\Models\Project;
use App\Support\Enums\RiskLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectHighRiskNotification extends Notification
{
    use Queueable;

    public function __construct(private Project $project)
    {
    }

    public function via(object $notifiable): array
    {
        return $notifiable->notify_by_email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $riskLabel = mb_strtolower(RiskLevel::from($this->project->risk_level)->label());

        return (new MailMessage)
            ->subject("Riesgo {$riskLabel}: {$this->project->name}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El proyecto \"{$this->project->name}\" presenta riesgo {$riskLabel} (puntaje: {$this->project->risk_score}).")
            ->action('Ver proyecto', route('projects.show', $this->project))
            ->line('Se recomienda revisar la situación cuanto antes.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_high_risk',
            'project_id' => $this->project->id,
            'message' => "El proyecto \"{$this->project->name}\" presenta riesgo ".mb_strtolower(RiskLevel::from($this->project->risk_level)->label()).'.',
            'risk_score' => $this->project->risk_score,
        ];
    }
}
