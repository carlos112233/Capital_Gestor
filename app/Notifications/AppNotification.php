<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class AppNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $actionUrl;
    public $iconUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $actionUrl = '/', $iconUrl = '/img/icon-192.png')
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->iconUrl = $iconUrl;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the array representation of the notification for the database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'icon_url' => $this->iconUrl,
        ];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon($this->iconUrl)
            ->body($this->message)
            ->action('Abrir app', 'open_app')
            ->data(['url' => $this->actionUrl]);
    }
}
