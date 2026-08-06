<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Asegúrate de que esta línea esté aquí

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=2')->daily();
Schedule::command('db:backup')->dailyAt('00:00');
Schedule::command('articulos:reset-disponibilidad')->dailyAt('14:00');

// Enviar recordatorios masivos los días 15 y el último día del mes a las 6:00 PM
Schedule::command('whatsapp:recordatorios')->monthlyOn(15, '18:00');
Schedule::command('whatsapp:recordatorios')->lastDayOfMonth('18:00');
