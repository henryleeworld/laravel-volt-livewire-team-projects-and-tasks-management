<?php

namespace App\Livewire\Notifications;

use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Computed]
    public function notifications()
    {
        return auth()->user()
            ->unreadNotifications()
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
