<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
         x-data="{
             themeMode: '{{ old('theme_mode', $user->theme_mode ?? 'system') }}',
             themeColor: '{{ old('theme_color', $user->theme_color ?? ($appSettings->primary_color ?? '#6366f1')) }}',
             hasCustomColor: {{ !empty($user->theme_color) ? 'true' : 'false' }},
             defaultColor: '{{ $appSettings->primary_color ?? '#6366f1' }}',
             setColor(c) {
                 this.themeColor = c;
                 this.hasCustomColor = true;
             },
             useDefaultColor() {
                 this.themeColor = this.defaultColor;
                 this.hasCustomColor = false;
             }
         }">

        <!-- Encabezado Principal -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-2">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white leading-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800/60 text-indigo-600 dark:text-indigo-400 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </span>
                    <span>Mi Apariencia y Tema</span>
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                    Personaliza tus colores y modo de lectura sin alterar la marca institucional.
                </p>
            </div>

            <!-- Botón Restablecer a Tema Institucional -->
            <form action="{{ route('theme.reset') }}" method="POST"
                  onsubmit="return confirm('¿Deseas restablecer tu tema visual a los valores institucionales del negocio?');">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold transition-all shadow-xs hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500/30 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Restablecer Valores Institucionales</span>
                </button>
            </form>
        </div>

        <!-- Alerta de Notificación -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 text-sm font-medium flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 text-sm font-medium">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Grid Principal: Formulario (7 Cols) + Mockup Sticky (5 Cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Columna Izquierda: Formulario -->
            <form action="{{ route('theme.update') }}" method="POST" class="lg:col-span-7 space-y-6">
                @csrf

                <!-- SECCIÓN 1: Modo de Visualización -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs p-6 space-y-5">
                    <div class="border-b border-slate-100 dark:border-slate-700/80 pb-4">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Modo de Visualización</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Elige cómo deseas visualizar los fondos, textos y componentes de tu panel.</p>
                    </div>

                    <!-- 3 Tarjetas Interactivas de Selección -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Opción Claro -->
                        <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 text-center items-center justify-between gap-3"
                               :class="themeMode === 'light' ? 'border-indigo-600 bg-indigo-50/40 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-200 shadow-xs' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'">
                            <input type="radio" name="theme_mode" value="light" class="sr-only" x-model="themeMode">
                            <span class="text-3xl">☀️</span>
                            <div>
                                <span class="block text-sm font-bold">Modo Claro</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Fondo blanco nítido y alto contraste diurno.</span>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all"
                                  :class="themeMode === 'light' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 dark:border-slate-600'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="themeMode === 'light'"></span>
                            </span>
                        </label>

                        <!-- Opción Oscuro -->
                        <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 text-center items-center justify-between gap-3"
                               :class="themeMode === 'dark' ? 'border-indigo-600 bg-indigo-50/40 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-200 shadow-xs' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'">
                            <input type="radio" name="theme_mode" value="dark" class="sr-only" x-model="themeMode">
                            <span class="text-3xl">🌙</span>
                            <div>
                                <span class="block text-sm font-bold">Modo Oscuro</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Fondo oscuro profundo para confort visual.</span>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all"
                                  :class="themeMode === 'dark' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 dark:border-slate-600'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="themeMode === 'dark'"></span>
                            </span>
                        </label>

                        <!-- Opción Automático (Sistema) -->
                        <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 text-center items-center justify-between gap-3"
                               :class="themeMode === 'system' ? 'border-indigo-600 bg-indigo-50/40 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-200 shadow-xs' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'">
                            <input type="radio" name="theme_mode" value="system" class="sr-only" x-model="themeMode">
                            <span class="text-3xl">💻</span>
                            <div>
                                <span class="block text-sm font-bold">Automático</span>
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sigue la preferencia de tu dispositivo móvil o PC.</span>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all"
                                  :class="themeMode === 'system' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 dark:border-slate-600'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="themeMode === 'system'"></span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- SECCIÓN 2: Color de Acento Personal -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs p-6 space-y-6">
                    <div class="border-b border-slate-100 dark:border-slate-700/80 pb-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Color de Acento Personal</span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Este color resaltará tus botones principales, enlaces activos y badges en toda la interfaz.
                            </p>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full w-fit"
                              :class="hasCustomColor ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                            <span x-text="hasCustomColor ? 'Color Personalizado' : 'Tema Institucional'"></span>
                        </span>
                    </div>

                    <!-- Paletas Rápidas en 1 Clic -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">
                            Paletas Recomendadas en 1 Clic
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <!-- Violeta El Bajón -->
                            <button type="button" @click="setColor('#6366f1')"
                                    class="p-3 rounded-xl border flex flex-col items-center gap-2 transition-all cursor-pointer text-center"
                                    :class="themeColor.toLowerCase() === '#6366f1' ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/30 dark:bg-indigo-950/30' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                <span class="w-8 h-8 rounded-full shadow-xs flex items-center justify-center text-white" style="background-color: #6366f1;">
                                    <svg x-show="themeColor.toLowerCase() === '#6366f1'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Violeta</span>
                            </button>

                            <!-- Azul Océano -->
                            <button type="button" @click="setColor('#2563eb')"
                                    class="p-3 rounded-xl border flex flex-col items-center gap-2 transition-all cursor-pointer text-center"
                                    :class="themeColor.toLowerCase() === '#2563eb' ? 'border-blue-600 ring-2 ring-blue-500/20 bg-blue-50/30 dark:bg-blue-950/30' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                <span class="w-8 h-8 rounded-full shadow-xs flex items-center justify-center text-white" style="background-color: #2563eb;">
                                    <svg x-show="themeColor.toLowerCase() === '#2563eb'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Azul</span>
                            </button>

                            <!-- Verde Esmeralda -->
                            <button type="button" @click="setColor('#10b981')"
                                    class="p-3 rounded-xl border flex flex-col items-center gap-2 transition-all cursor-pointer text-center"
                                    :class="themeColor.toLowerCase() === '#10b981' ? 'border-emerald-600 ring-2 ring-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-950/30' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                <span class="w-8 h-8 rounded-full shadow-xs flex items-center justify-center text-white" style="background-color: #10b981;">
                                    <svg x-show="themeColor.toLowerCase() === '#10b981'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Esmeralda</span>
                            </button>

                            <!-- Ámbar Cálido -->
                            <button type="button" @click="setColor('#f59e0b')"
                                    class="p-3 rounded-xl border flex flex-col items-center gap-2 transition-all cursor-pointer text-center"
                                    :class="themeColor.toLowerCase() === '#f59e0b' ? 'border-amber-600 ring-2 ring-amber-500/20 bg-amber-50/30 dark:bg-amber-950/30' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                <span class="w-8 h-8 rounded-full shadow-xs flex items-center justify-center text-white" style="background-color: #f59e0b;">
                                    <svg x-show="themeColor.toLowerCase() === '#f59e0b'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Ámbar</span>
                            </button>

                            <!-- Rosa Neón -->
                            <button type="button" @click="setColor('#ec4899')"
                                    class="p-3 rounded-xl border flex flex-col items-center gap-2 transition-all cursor-pointer text-center col-span-2 sm:col-span-1"
                                    :class="themeColor.toLowerCase() === '#ec4899' ? 'border-pink-600 ring-2 ring-pink-500/20 bg-pink-50/30 dark:bg-pink-950/30' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                <span class="w-8 h-8 rounded-full shadow-xs flex items-center justify-center text-white" style="background-color: #ec4899;">
                                    <svg x-show="themeColor.toLowerCase() === '#ec4899'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Rosa</span>
                            </button>
                        </div>
                    </div>

                    <!-- Selector Libre de Color y Hexadecimal -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <input type="color" x-model="themeColor" @input="hasCustomColor = true"
                                   class="w-12 h-12 rounded-xl border-0 cursor-pointer bg-transparent p-0">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">Selector Libre de Color</label>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">Haz clic en el recuadro para elegir cualquier tono.</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <input type="text" name="theme_color" x-model="themeColor" @input="hasCustomColor = true"
                                   placeholder="#6366f1"
                                   class="w-28 px-3 py-2 text-xs font-mono font-bold text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white uppercase focus:ring-2 focus:ring-indigo-500/20">
                            
                            <button type="button" @click="useDefaultColor()"
                                    class="px-3 py-2 rounded-lg text-xs font-semibold border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors"
                                    title="Volver a sincronizar con el color institucional">
                                Usar Institucional
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl font-bold text-sm text-white shadow-md hover:shadow-lg transition-all cursor-pointer focus:outline-none"
                            :style="'background-color: ' + themeColor + ';'">
                        Guardar Preferencias
                    </button>
                </div>
            </form>

            <!-- Columna Derecha: Mockup Interactivo en Tiempo Real -->
            <div class="lg:col-span-5 sticky top-6 h-fit space-y-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/80 pb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" :style="'background-color: ' + themeColor + ';'"></span>
                            Vista Previa en Vivo
                        </span>
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full"
                              :class="themeMode === 'dark' ? 'bg-slate-900 text-slate-200 border border-slate-700' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                              x-text="themeMode.toUpperCase()">
                        </span>
                    </div>

                    <!-- Simulación de Contenedor de la App -->
                    <div class="rounded-xl p-4 transition-colors duration-300 border space-y-4"
                         :class="themeMode === 'dark' ? 'bg-slate-950 border-slate-800 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'">
                        
                        <!-- Mini Barra Superior Simulada -->
                        <div class="flex items-center justify-between pb-3 border-b"
                             :class="themeMode === 'dark' ? 'border-slate-800' : 'border-slate-200'">
                            <div class="flex items-center gap-2">
                                <img src="{{ $appSettings->logo_url }}" class="w-6 h-6 object-contain" alt="Logo">
                                <span class="text-xs font-black">{{ $appSettings->app_name ?? 'El Bajón' }}</span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white"
                                  :style="'background-color: ' + themeColor + ';'">
                                Activo
                            </span>
                        </div>

                        <!-- Mini Botón Activo del Sidebar -->
                        <div class="space-y-1.5">
                            <div class="px-3 py-2 rounded-lg text-xs font-bold text-white flex items-center gap-2 shadow-xs"
                                 :style="'background-color: ' + themeColor + ';'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                <span>Menú Seleccionado</span>
                            </div>
                            <div class="px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2 opacity-70"
                                 :class="themeMode === 'dark' ? 'hover:bg-slate-900 text-slate-300' : 'hover:bg-slate-200 text-slate-600'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <span>Menú Inactivo</span>
                            </div>
                        </div>

                        <!-- Mini Tarjeta de Contenido -->
                        <div class="p-3 rounded-lg border text-xs space-y-2"
                             :class="themeMode === 'dark' ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
                            <div class="flex justify-between items-center">
                                <span class="font-bold">Saldo o Transacción</span>
                                <span class="font-black text-emerald-500">$1,250.00</span>
                            </div>
                            <button type="button" class="w-full py-1.5 rounded-md text-[11px] font-bold text-white text-center shadow-xs"
                                    :style="'background-color: ' + themeColor + ';'">
                                Botón de Acción Principal
                            </button>
                        </div>
                    </div>

                    <!-- Nota de Inmutabilidad de Marca -->
                    <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>El logotipo, favicon y nombre comercial son administrados institucionalmente por la dirección.</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
