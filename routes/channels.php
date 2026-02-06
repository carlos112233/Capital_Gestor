<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('pedidos', function ($user) {
    // Validamos que solo admin pueda escuchar
    return $user->hasRole('admin');
});
