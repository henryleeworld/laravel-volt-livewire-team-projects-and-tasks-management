<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email_notifications) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New Task Assigned: ') . $this->task->name)
            ->line(__('You have been assigned a new task.'))
            ->line(__('Task: ') . $this->task->name)
            ->when($this->task->description, fn (MailMessage $mail) => $mail->line(__('Description: ') . $this->task->description))
            ->action(__('View Task'), route('tasks.show', $this->task))
            ->line(__('Thank you for using our application!'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_description' => $this->task->description,
            'assigner_name' => $this->task->user->name,
            'action_url' => route('tasks.show', $this->task),
        ];
    }
}
