<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('pedidos', function ($user) {
    // Validamos que solo admin pueda escuchar
    return $user->hasRole('admin');
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
