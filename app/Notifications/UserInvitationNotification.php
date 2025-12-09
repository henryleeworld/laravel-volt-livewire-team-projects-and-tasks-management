<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $organizationName = $this->invitation->organization?->name;

        return (new MailMessage)
            ->subject(__('You have been invited to join :app_name', ['app_name' => config('app.name')]))
            ->greeting(__('Hello :name!', ['name' => ($this->invitation->name ? ' '.$this->invitation->name : '')]))
            ->line(__('You have been invited to join :organization_name.', ['organization_name' => ($organizationName ?? __('our team'))]))
            ->action(__('Accept Invitation'), URL::signedRoute('invitations.accept', $this->invitation, absolute: true))
            ->line(__('If you were not expecting this invitation, you can safely ignore this email.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->invitation->token,
            'email' => $this->invitation->email,
            'organization_id' => $this->invitation->organization_id,
        ];
    }
}
