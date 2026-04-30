<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class FlutterNotification extends Notification
{
    use Queueable;

    public function __construct(public $titulo, public $mensaje) {}

    public function via($notifiable): array
    {
        // Enviamos a ntfy manualmente
        $this->sendToNtfy();
        return [];
    }

    protected function sendToNtfy()
    {
        // "mi_canal_unico_123" debe ser el mismo en Flutter
        Http::withHeaders([
            'Title' => $this->titulo,
            'Priority' => 'high',
            'Tags' => 'warning,message'
        ])->post("https://ntfy.sh/mi_canal_unico_123", $this->mensaje);
    }
}