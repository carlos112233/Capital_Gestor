<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Asegúrate de que esta línea esté aquí

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=2')->daily();
Schedule::command('db:backup')->dailyAt('00:00');
Schedule::command('articulos:reset-disponibilidad')->dailyAt('19:00')->timezone('America/Mexico_City');

// Enviar recordatorios masivos los días 15 y el último día del mes a las 6:00 PM
Schedule::command('whatsapp:recordatorios')->monthlyOn(15, '18:00');
Schedule::command('whatsapp:recordatorios')->lastDayOfMonth('18:00');

// Limpieza automática cada hora de PDFs temporales antiguos
Schedule::call(function () {
    \App\Services\PdfReceiptService::cleanupOldTempPdfs();
})->hourly();

// Recordatorios Push de entregas matutinas (8:30 AM, 9:15 AM, 9:55 AM) para pedidos "En preparación"
Schedule::call(function () {
    \App\Services\PushNotificationService::sendScheduledDeliveryReminders();
})->dailyAt('08:30')->timezone('America/Mexico_City');

Schedule::call(function () {
    \App\Services\PushNotificationService::sendScheduledDeliveryReminders();
})->dailyAt('09:15')->timezone('America/Mexico_City');

Schedule::call(function () {
    \App\Services\PushNotificationService::sendScheduledDeliveryReminders();
})->dailyAt('09:55')->timezone('America/Mexico_City');

Artisan::command('push:reminders-matutinos', function () {
    $this->info("Ejecutando alertas Push matutinas de entregas...");
    $count = \App\Services\PushNotificationService::sendScheduledDeliveryReminders();
    $this->info("Proceso completado. Notificaciones enviadas: {$count}");
})->purpose('Enviar las alertas Push de pedidos en preparación pendientes de entrega');
