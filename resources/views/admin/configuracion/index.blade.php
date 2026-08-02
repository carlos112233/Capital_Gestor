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

    <div class="py-6 max-w-4xl mx-auto space-y-6">

        <!-- Módulo Principal: Vinculación de WhatsApp QR (Solo Admin) -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80 p-6 sm:p-8">
            <div class="mb-6 pb-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        Vinculación de WhatsApp (Código QR)
                    </h3>
                    <p class="text-sm text-slate-500 mt-0.5">Escanea este código con tu teléfono celular para conectar el motor automático de notificaciones.</p>
                </div>
                <button type="button" onclick="document.getElementById('qr-img').src = '{{ asset('img/qr.png') }}?v=' + new Date().getTime();"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 font-bold text-xs transition-colors cursor-pointer self-start sm:self-auto shadow-xs">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Actualizar Imagen QR
                </button>
            </div>

            <div class="flex flex-col md:flex-row items-center gap-8 bg-slate-50 p-6 rounded-2xl border border-slate-200/60">
                <div class="w-64 h-64 bg-white p-3 rounded-2xl shadow-md border border-slate-200 flex items-center justify-center relative overflow-hidden flex-shrink-0">
                    <img id="qr-img" src="{{ file_exists(public_path('img/qr.png')) ? asset('img/qr.png') : (file_exists(public_path('qr.png')) ? asset('qr.png') : asset('img/Logo.png')) }}?v={{ time() }}" 
                         alt="Código QR WhatsApp" class="max-w-full max-h-full object-contain rounded-lg">
                </div>
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold shadow-xs">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                        Estado: Motor de WhatsApp Activo
                    </div>
                    <h4 class="text-base font-bold text-slate-800">Instrucciones de vinculación:</h4>
                    <ol class="space-y-2 text-xs font-medium text-slate-600 list-decimal list-inside">
                        <li>Abre la aplicación de <strong>WhatsApp</strong> en tu teléfono.</li>
                        <li>Entra al menú principal (3 puntos arriba a la derecha en Android, o Ajustes en iPhone).</li>
                        <li>Toca en <strong>Dispositivos vinculados</strong> &gt; <strong>Vincular un dispositivo</strong>.</li>
                        <li>Escanea el código QR que ves en esta pantalla.</li>
                    </ol>
                    <p class="text-xs text-slate-500 bg-white p-3 rounded-xl border border-slate-200/80">
                        💡 <strong>Nota:</strong> Al escanear el QR, las notificaciones y recordatorios de saldo se enviarán automáticamente desde ese número de WhatsApp.
                    </p>
                </div>
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
</x-app-layout>
