<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ __('Configuración del Sistema & WhatsApp Motor') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6" x-data="waStatusComponent()">

        <!-- Módulo Principal: Vinculación de WhatsApp QR & Estado en Tiempo Real -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80 p-6 sm:p-8">
            <div class="mb-6 pb-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        Estado del Motor de WhatsApp
                    </h3>
                    <p class="text-sm text-slate-500 mt-0.5">Diagnóstico y estado del motor automático de notificaciones.</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="fetchStatus()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Comprobar Estado
                    </button>

                    <!-- Botón para eliminar sesión y generar nuevo QR -->
                    <form id="wa-reset-form" action="{{ route('admin.configuracion.wa-reset') }}" method="POST" class="inline">
                        @csrf
                        <button type="button" @click="confirmResetSession()"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition-colors cursor-pointer shadow-xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Cerrar Sesión &amp; Nuevo QR
                        </button>
                    </form>
                </div>
            </div>

            <!-- ESTADO 1: CARGANDO / INICIANDO MOTOR -->
            <template x-if="status === 'cargando'">
                <div class="flex flex-col md:flex-row items-center gap-6 bg-blue-50/70 p-6 sm:p-8 rounded-2xl border border-blue-200/80">
                    <div class="w-20 h-20 rounded-2xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-blue-600/30">
                        <svg class="w-10 h-10 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div class="space-y-2 text-center md:text-left">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100 text-blue-800 border border-blue-300 text-xs font-bold shadow-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-ping"></span>
                            Estado: Conectando a WhatsApp Web...
                        </div>
                        <h4 class="text-lg font-bold text-slate-800">Iniciando el motor en el servidor</h4>
                        <p class="text-xs sm:text-sm text-slate-600 max-w-xl" x-text="message || 'Iniciando navegador Chromium y cargando la plataforma de WhatsApp Web...'"></p>
                        <p class="text-xs text-blue-600 font-medium">⏳ Haz clic en "Comprobar Estado" cuando desees actualizar la información.</p>
                    </div>
                </div>
            </template>

            <!-- ESTADO 2: CÓDIGO QR PENDIENTE DE ESCANEO -->
            <template x-if="status === 'qr_pendiente'">
                <div class="flex flex-col md:flex-row items-center gap-8 bg-amber-50/60 p-6 rounded-2xl border border-amber-200/80">
                    <div class="w-64 h-64 bg-white p-3 rounded-2xl shadow-md border border-slate-200 flex items-center justify-center relative overflow-hidden flex-shrink-0">
                        <img :src="qrUrl" x-on:error="qrError = true" x-on:load="qrError = false" x-show="!qrError" alt="Código QR WhatsApp" class="max-w-full max-h-full object-contain rounded-lg">
                        <div x-show="qrError" class="flex flex-col items-center justify-center text-center p-4 space-y-2">
                            <svg class="w-8 h-8 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-semibold text-slate-600">Generando imagen QR...</span>
                            <button type="button" @click="fetchStatus()" class="text-[11px] text-indigo-600 font-bold underline cursor-pointer">Actualizar</button>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold shadow-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                            Estado: Pendiente de Escaneo QR
                        </div>
                        <h4 class="text-base font-bold text-slate-800">Instrucciones para iniciar sesión en WhatsApp:</h4>
                        <ol class="space-y-2 text-xs font-medium text-slate-600 list-decimal list-inside">
                            <li>Abre la aplicación de <strong>WhatsApp</strong> en tu teléfono celular.</li>
                            <li>Toca el menú (3 puntos arriba a la derecha en Android o Ajustes en iPhone).</li>
                            <li>Selecciona <strong>Dispositivos vinculados</strong> &gt; <strong>Vincular un dispositivo</strong>.</li>
                            <li>Apunta la cámara de tu teléfono hacia el código QR de la izquierda.</li>
                        </ol>
                        <p class="text-xs text-slate-500 bg-white p-3 rounded-xl border border-slate-200/80">
                            💡 <strong>Nota:</strong> Haz clic en "Comprobar Estado" tras escanear para verificar la vinculación.
                        </p>
                    </div>
                </div>
            </template>

            <!-- ESTADO 3: DISPOSITIVO VINCULADO & ACTIVO -->
            <template x-if="status === 'conectado'">
                <div class="flex flex-col md:flex-row items-center gap-6 bg-emerald-50/70 p-6 sm:p-8 rounded-2xl border border-emerald-200/80">
                    <div class="w-20 h-20 rounded-2xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-emerald-600/30">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="space-y-2 text-center md:text-left">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold shadow-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Estado: Dispositivo Vinculado &amp; Conectado
                        </div>
                        <h4 class="text-lg font-bold text-slate-800">¡WhatsApp está autenticado y en línea!</h4>
                        <p class="text-xs sm:text-sm text-slate-600 max-w-xl" x-text="message || 'La sesión está activa en el servidor. El código QR ha sido retirado automáticamente.'"></p>
                        <p class="text-xs text-emerald-700 font-medium">✅ Notificaciones masivas y alertas de saldo listas para enviarse.</p>
                    </div>
                </div>
            </template>

            <!-- ESTADO 4: DIAGNÓSTICO DETALLADO DE ERRORES FUERA DE ALCANCE -->
            <template x-if="status === 'error' || status === 'desconectado'">
                <div class="space-y-4 bg-rose-50/80 p-6 sm:p-8 rounded-2xl border border-rose-200/90 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-rose-200/60">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-rose-600/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-200 text-rose-900 font-bold text-xs uppercase tracking-wider">
                                    🔴 Error Detectado: <span x-text="error_type || 'General'"></span>
                                </div>
                                <h4 class="text-base font-bold text-slate-800 mt-1" x-text="message || 'Falló la conexión del motor de WhatsApp'"></h4>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="copyDiagnostics()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 transition-colors shadow-2xs">
                                📋 Copiar Diagnóstico
                            </button>
                            <button type="button" @click="confirmResetSession()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-colors shadow-md">
                                🔄 Reiniciar Motor &amp; Nuevo QR
                            </button>
                        </div>
                    </div>

                    <!-- Sugerencia de Solución -->
                    <template x-if="solution_hint">
                        <div class="p-4 rounded-xl bg-white border border-amber-200/80 shadow-2xs space-y-1">
                            <h5 class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                                💡 Solución Recomendada:
                            </h5>
                            <p class="text-xs font-medium text-slate-700" x-text="solution_hint"></p>
                        </div>
                    </template>

                    <!-- Detalle Técnico / Stack Trace (Desplegable) -->
                    <template x-if="detail">
                        <div x-data="{ openDetail: false }" class="space-y-2">
                            <button type="button" @click="openDetail = !openDetail"
                                class="text-xs font-bold text-rose-700 hover:text-rose-900 underline flex items-center gap-1">
                                <span x-text="openDetail ? '▼ Ocultar detalle técnico' : '▶ Ver detalle técnico del error (Logs)'"></span>
                            </button>

                            <div x-show="openDetail" x-transition
                                class="p-3 rounded-xl bg-slate-900 text-rose-300 text-xs font-mono overflow-x-auto max-h-48 whitespace-pre-wrap border border-slate-800 shadow-inner">
                                <span x-text="detail"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- DETECCIÓN DINÁMICA DE LA BASE DE DATOS ACTIVA -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21 3.582 4 8 4s8-1.79 8-4" />
                    </svg>
                    <span class="text-xs font-semibold text-slate-500">Base de Datos <span class="font-bold text-slate-700" x-text="db_driver || 'PostgreSQL'"></span> Activa:</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold text-xs border border-indigo-200/80 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span x-text="db_name"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- TABLA DE MENSAJES PENDIENTES & COLA DE ENVÍO DE WHATSAPP -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80 p-6 sm:p-8 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        Cola de Mensajes de WhatsApp
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Mensajes registrados en la tabla <code class="bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600 font-mono text-[11px]">whatsapp_pending_messages</code>.</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                        📊 Total: <span class="text-indigo-600" x-text="messages.length"></span>
                    </span>
                </div>
            </div>

            <!-- TABLA REACTIVA EN TIEMPO REAL -->
            <div class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-2xs">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <th class="py-3 px-4"># ID</th>
                            <th class="py-3 px-4">Número Destino</th>
                            <th class="py-3 px-4">Contenido del Mensaje</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4">Fecha y Hora</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-if="messages.length === 0">
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-1">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <span>No hay mensajes registrados en la cola actualmente.</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="msg in messages" :key="msg.id">
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-700" x-text="'#' + msg.id"></td>
                                <td class="py-3 px-4 font-bold text-slate-800" x-text="msg.numero"></td>
                                <td class="py-3 px-4 max-w-xs text-slate-600">
                                    <p class="truncate" x-text="msg.mensaje" :title="msg.mensaje"></p>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <template x-if="msg.status === 'pendiente'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pendiente
                                        </span>
                                    </template>
                                    <template x-if="msg.status === 'enviado'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Enviado
                                        </span>
                                    </template>
                                    <template x-if="msg.status === 'fallido'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[11px]" :title="msg.error_message || ''">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Fallido
                                        </span>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]" x-text="msg.created_at ? msg.created_at.replace('T', ' ').substring(0, 19) : ''"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Personalización de Logotipo -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80 p-6 sm:p-8">
            <div class="mb-6 pb-6 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-800">Personalización de Logotipo</h3>
                <p class="text-sm text-slate-500">Sube una nueva imagen para cambiar la identidad visual del negocio en el menú lateral y la plataforma.</p>
            </div>

            <!-- Previsualización Actual -->
            <div class="mb-8 flex items-center gap-6 p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                <div class="w-20 h-20 rounded-2xl bg-white shadow-md p-2 flex items-center justify-center border border-amber-200/80">
                    <img src="{{ file_exists(public_path('img/Logo.svg')) ? asset('img/Logo.svg') : asset('img/Logo.png') }}?v={{ time() }}" class="max-h-full max-w-full object-contain" alt="Logo Actual">
                </div>
                <div>
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Logotipo Actual</span>
                    <h4 class="text-base font-bold text-slate-800">Imagen Activa en el Sistema</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Formatos recomendados: PNG, SVG o JPG transparente. Tamaño máx: 4MB.</p>
                </div>
            </div>

            <!-- Formulario de Carga -->
            <form action="{{ route('admin.configuracion.logo') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Seleccionar nueva imagen de logo</label>
                    <div x-data="{ fileName: '' }" class="relative">
                        <label class="flex items-center justify-center gap-3 w-full px-5 py-4 rounded-xl border-2 border-dashed border-indigo-300 hover:border-indigo-500 bg-indigo-50/50 hover:bg-indigo-50 text-indigo-700 font-semibold text-sm cursor-pointer transition-all duration-200 shadow-sm">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span x-text="fileName ? fileName : 'Haga clic para seleccionar imagen de logo o arrastre aquí'"></span>
                            <input type="file" name="logo" accept="image/*" required class="hidden"
                                   @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                        </label>
                    </div>
                    @error('logo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="submit" 
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Cambios de Logotipo
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Script de Alpine.js para la consulta bajo demanda del estado de WhatsApp -->
    <script>
        function waStatusComponent() {
            return {
                status: '{{ file_exists(public_path('img/qr.png')) ? 'qr_pendiente' : 'conectado' }}',
                message: 'Consultando estado...',
                error_type: null,
                detail: null,
                solution_hint: null,
                db_driver: '{{ DB::connection()->getDriverName() === "mysql" ? "MySQL" : "PostgreSQL" }}',
                db_name: '{{ DB::connection()->getDatabaseName() }}',
                messages: @json($pendingMessages ?? []),
                qr_exists: {{ file_exists(public_path('img/qr.png')) ? 'true' : 'false' }},
                qrUrl: '{{ asset('img/qr.png') }}?v=' + new Date().getTime(),
                qrError: false,
                isLoading: false,

                init() {
                    this.fetchStatus();
                },

                fetchStatus() {
                    if (this.isLoading) return;
                    this.isLoading = true;
                    this.qrError = false;

                    fetch('{{ route('admin.configuracion.wa-status') }}?_t=' + new Date().getTime(), {
                        headers: {
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        }
                    })
                        .then(res => {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        })
                        .then(data => {
                            this.status = data.status || 'desconectado';
                            this.message = data.message || '';
                            this.error_type = data.error_type || null;
                            this.detail = data.detail || null;
                            this.solution_hint = data.solution_hint || null;
                            if (data.db_driver) {
                                this.db_driver = data.db_driver;
                            }
                            if (data.db_name) {
                                this.db_name = data.db_name;
                            } else if (data.db_info && data.db_info.database) {
                                this.db_name = data.db_info.database;
                            }
                            if (data.messages && Array.isArray(data.messages)) {
                                this.messages = data.messages;
                            }
                            this.qr_exists = data.qr_exists;
                            this.qrUrl = '{{ asset('img/qr.png') }}?v=' + new Date().getTime();
                        })
                        .catch(err => {
                            console.log('Error verificando estado de WhatsApp:', err);
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },

                copyDiagnostics() {
                    const textToCopy = `[DIAGNÓSTICO WHATSAPP]\nTipo: ${this.error_type || 'Desconocido'}\nMensaje: ${this.message}\nSolución: ${this.solution_hint || 'N/A'}\n\n[DETALLE TÉCNICO]\n${this.detail || 'Sin log disponible'}`;
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Diagnóstico copiado',
                                text: 'El informe de error ha sido copiado al portapapeles.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert('Diagnóstico copiado al portapapeles.');
                        }
                    });
                },

                confirmResetSession() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Cerrar sesión y generar nuevo QR?',
                            text: 'Se eliminarán los datos de autenticación actuales y el servidor generará un nuevo código QR para escanear.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e11d48',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Sí, borrar sesión y generar QR',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('wa-reset-form').submit();
                            }
                        });
                    } else {
                        if (confirm('¿Deseas borrar la sesión de WhatsApp y generar un nuevo código QR?')) {
                            document.getElementById('wa-reset-form').submit();
                        }
                    }
                }
            }
        }
    </script>
</x-app-layout>
