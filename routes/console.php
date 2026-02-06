<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Asegúrate de que esta línea esté aquí

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- AÑADE ESTA LÍNEA ---
Schedule::command('sanctum:prune-expired --hours=2')->daily();
