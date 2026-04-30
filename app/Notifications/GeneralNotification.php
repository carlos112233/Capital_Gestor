<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Http;

class GeneralNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public string $titulo, public string $mensaje) {}

    public function via($notifiable): array
    {
        // 1. Enviamos a Reverb (broadcast)
        // 2. Ejecutamos la lógica de ntfy manualmente
        $this->sendToNtfy();
        
        return ['broadcast'];
    }

    // Parte 1: Notificación Push vía ntfy.sh
    protected function sendToNtfy()
    {
        Http::withHeaders([
            'Title' => $this->titulo,
            'Priority' => 'high',
        ])->post("https://ntfy.sh/tu_canal_unico_flutter", $this->mensaje);
    }

    // Parte 2: Datos para el WebSocket (Reverb)
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
        ]);
    }
}
