@component('mail::message')
# 📦 ¡Nuevo Pedido!

Un nuevo pedido ha sido registrado.

**Cliente:** {{ $usuario->name }}  
**Correo:** {{ $usuario->email }}  
**Descripción:** {{ $pedido->descripcion }}  
**Costo:** ${{ number_format($pedido->costo, 2) }}

@component('mail::button', ['url' => url('/pedidos/')])
Ver Pedido
@endcomponent

Gracias,  
{{ config('app.name') }}
@endcomponent
