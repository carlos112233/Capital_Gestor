<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
         x-data="{
             appName: '{{ old('app_name', $settings->app_name) }}',
             primaryColor: '{{ old('primary_color', $settings->primary_color) }}',
             secondaryColor: '{{ old('secondary_color', $settings->secondary_color) }}',
             sidebarColor: '{{ old('sidebar_color', $settings->sidebar_color) }}',
             fontFamily: '{{ old('font_family', $settings->font_family) }}',
             themeMode: '{{ old('theme_mode', $settings->theme_mode) }}',
             logoPreview: '{{ $settings->logo_url }}',
             faviconPreview: '{{ $settings->favicon_url }}',
             applyPreset(preset) {
                 this.primaryColor = preset.primary;
                 this.secondaryColor = preset.secondary;
                 this.sidebarColor = preset.sidebar;
                 this.fontFamily = preset.font;
             },
             previewFile(e, type) {
                 const file = e.target.files[0];
                 if (!file) return;
                 const reader = new FileReader();
                 reader.onload = (event) => {
                     if (type === 'logo') {
                         this.logoPreview = event.target.result;
                     } else if (type === 'favicon') {
                         this.faviconPreview = event.target.result;
                     }
                 };
                 reader.readAsDataURL(file);
             }
         }">

        <!-- Encabezado Principal de Alta Legibilidad -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-2">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white leading-tight flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800/60 text-indigo-600 dark:text-indigo-400 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4c.7 0 1.36.17 1.94.48L15 7.42a2.5 2.5 0 013.54 0l.04.04a2.5 2.5 0 010 3.54l-5.06 6.06c.31.58.48 1.24.48 1.94a4 4 0 01-4 4H7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-2 4.5-4.5 0-.85-.24-1.65-.65-2.33a2.5 2.5 0 012.15-3.67H19a2 2 0 002-2 9 9 0 00-9-9 9 9 0 00-9 9c0 4.97 4.03 9 9 9z" opacity="0.4" />
                        </svg>
                    </span>
                    <span>Personalización y Marca Blanca</span>
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">Configura la identidad corporativa, colores del sistema, tipografías y logotipos de tu plataforma.</p>
            </div>

            <!-- Botón Quirúrgico de Restablecimiento a Fábrica -->
            <form action="{{ route('admin.branding.reset') }}" method="POST"
                  onsubmit="return confirm('¿Estás seguro de restablecer el tema a los valores de fábrica de El Bajón?\n\nEsta acción eliminará los logotipos personalizados subidos y restaurará los colores y tipografía original.');">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/90 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 text-sm font-semibold transition-all shadow-xs hover:shadow focus:outline-none focus:ring-2 focus:ring-rose-500/30 cursor-pointer">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Restablecer a Valores de Fábrica</span>
                </button>
            </form>
        </div>

        <!-- Mensajes de Alerta y Validación -->
        @if ($errors->any())
            <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border-l-4 border-rose-500 rounded-xl text-rose-700 dark:text-rose-300 text-sm shadow-xs">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Por favor corrige los siguientes inconvenientes:
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- TARJETAS DE TEMAS PRECONFIGURADOS EN 1 CLIC (FULL WIDTH) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Temas Preconfigurados en 1 Clic
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Selecciona una paleta profesional prediseñada para aplicarla instantáneamente al formulario y al mockup.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($presets as $key => $preset)
                    <button type="button"
                            @click="applyPreset({{ json_encode($preset) }})"
                            class="text-left p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500 bg-slate-50/70 dark:bg-slate-900/60 hover:bg-indigo-50/30 dark:hover:bg-slate-700/50 transition-all group relative overflow-hidden focus:outline-none focus:ring-2 focus:ring-indigo-500/30 cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $preset['name'] }}</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-slate-200/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $preset['font'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 my-2.5">
                            <div class="w-6 h-6 rounded-lg shadow-2xs border border-white dark:border-slate-700" style="background-color: {{ $preset['primary'] }};" title="Primario: {{ $preset['primary'] }}"></div>
                            <div class="w-6 h-6 rounded-lg shadow-2xs border border-white dark:border-slate-700" style="background-color: {{ $preset['secondary'] }};" title="Secundario: {{ $preset['secondary'] }}"></div>
                            <div class="w-6 h-6 rounded-lg shadow-2xs border border-white dark:border-slate-700" style="background-color: {{ $preset['sidebar'] }};" title="Sidebar: {{ $preset['sidebar'] }}"></div>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">{{ $preset['description'] }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- LAYOUT DE 12 COLUMNAS: FORMULARIO (7) + MOCKUP STICKY (5) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- COLUMNA IZQUIERDA: FORMULARIO DE AJUSTES (lg:col-span-7) -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
                    <div class="pb-5 mb-6 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Parámetros de Marca y Estilo
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Personaliza el nombre, logotipos, colores y tipografía del sistema.</p>
                    </div>

                    <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- 1. IDENTIDAD INSTITUCIONAL -->
                        <div class="space-y-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 block">1. Identidad Institucional</span>
                            
                            <!-- Nombre del Sistema -->
                            <div>
                                <label for="app_name" class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Nombre del Sistema / Empresa</label>
                                <input type="text"
                                       id="app_name"
                                       name="app_name"
                                       x-model="appName"
                                       required
                                       maxlength="100"
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 py-2.5 px-3"
                                       placeholder="Ej. El bajón">
                            </div>

                            <!-- Carga de Logotipo y Favicon -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Logotipo -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/60 space-y-3">
                                    <label class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Logotipo Institucional</label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-16 h-16 rounded-xl bg-slate-900 border border-slate-700 p-2 flex items-center justify-center flex-shrink-0 overflow-hidden shadow-inner">
                                            <img :src="logoPreview" class="max-h-full max-w-full object-contain" alt="Logo preview">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <input type="file"
                                                   name="logo"
                                                   id="logo"
                                                   accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                                   @change="previewFile($event, 'logo')"
                                                   class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-950/60 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer">
                                            <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-1">PNG, SVG, JPG o WebP (máx. 2MB).</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Favicon -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/60 space-y-3">
                                    <label class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Icono Favicon</label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 p-2 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                            <img :src="faviconPreview" class="w-8 h-8 object-contain" alt="Favicon preview">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <input type="file"
                                                   name="favicon"
                                                   id="favicon"
                                                   accept=".ico,image/png"
                                                   @change="previewFile($event, 'favicon')"
                                                   class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-950/60 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer">
                                            <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-1">ICO o PNG cuadrado (máx. 1MB).</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. PALETA DE COLORES -->
                        <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 block">2. Paleta de Colores</span>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Color Primario -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/60 space-y-2">
                                    <label for="primary_color" class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Color Primario</label>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Botones, enlaces y elementos destacados.</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input type="color"
                                               x-model="primaryColor"
                                               class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0 bg-transparent flex-shrink-0">
                                        <input type="text"
                                               id="primary_color"
                                               name="primary_color"
                                               x-model="primaryColor"
                                               required
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               class="w-full font-mono text-xs uppercase rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white py-2 px-2.5 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <!-- Color Secundario -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/60 space-y-2">
                                    <label for="secondary_color" class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Color Secundario</label>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Badges de éxito, estados y acentos.</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input type="color"
                                               x-model="secondaryColor"
                                               class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0 bg-transparent flex-shrink-0">
                                        <input type="text"
                                               id="secondary_color"
                                               name="secondary_color"
                                               x-model="secondaryColor"
                                               required
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               class="w-full font-mono text-xs uppercase rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white py-2 px-2.5 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <!-- Color Menú Lateral -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/60 space-y-2">
                                    <label for="sidebar_color" class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Color Menú Lateral</label>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Fondo del menú de navegación lateral.</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input type="color"
                                               x-model="sidebarColor"
                                               class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0 bg-transparent flex-shrink-0">
                                        <input type="text"
                                               id="sidebar_color"
                                               name="sidebar_color"
                                               x-model="sidebarColor"
                                               required
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               class="w-full font-mono text-xs uppercase rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white py-2 px-2.5 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. TIPOGRAFÍA Y ESTILO -->
                        <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 block">3. Tipografía y Estilo</span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Fuente Google Fonts -->
                                <div class="w-full min-w-0">
                                    <label for="font_family" class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Familia Tipográfica</label>
                                    <select id="font_family"
                                            name="font_family"
                                            x-model="fontFamily"
                                            class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 py-2.5 px-3 truncate">
                                        @foreach ($fontOptions as $fontKey => $fontLabel)
                                            <option value="{{ $fontKey }}">{{ $fontLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Modo de Tema -->
                                <div class="w-full min-w-0">
                                    <label for="theme_mode" class="text-xs font-bold text-slate-700 dark:text-slate-200 block mb-1.5">Modo de Visualización</label>
                                    <select id="theme_mode"
                                            name="theme_mode"
                                            x-model="themeMode"
                                            class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 py-2.5 px-3">
                                        <option value="light">Claro (light)</option>
                                        <option value="dark">Oscuro (dark)</option>
                                        <option value="system">Automático (system)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- BOTÓN GUARDAR -->
                        <div class="pt-6 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end">
                            <button type="submit"
                                    style="background-color: var(--color-primary, #6366f1) !important;"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white font-bold text-sm shadow-md hover:opacity-95 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Guardar Personalización</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- COLUMNA DERECHA: MOCKUP STICKY (lg:col-span-5 sticky top-6 h-fit) -->
            <div class="lg:col-span-5 sticky top-6 h-fit space-y-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Previsualización en Tiempo Real
                        </h4>
                        <span class="text-[11px] font-mono text-slate-400 dark:text-slate-400">Mockup Interactivo</span>
                    </div>

                    <!-- Marco de Pantalla Simulada -->
                    <div class="rounded-xl border border-slate-300 dark:border-slate-700 shadow-md overflow-hidden bg-slate-100 dark:bg-slate-900 flex flex-col text-xs"
                         :style="'font-family: ' + fontFamily + ', sans-serif;'">
                        
                        <!-- Barra de Ventana Simulada -->
                        <div class="bg-slate-800 px-3 py-2 flex items-center gap-1.5 border-b border-slate-700">
                            <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                            <div class="ml-2 px-2 py-0.5 rounded bg-slate-900/60 text-[10px] text-slate-400 font-mono truncate max-w-[150px]">
                                app.elbajon.store
                            </div>
                        </div>

                        <!-- Interfaz Mockup (Sidebar + Contenido) -->
                        <div class="flex min-h-[320px]">
                            
                            <!-- Sidebar Miniatura Simulado -->
                            <div class="w-36 p-3 flex flex-col justify-between text-white transition-colors duration-200 flex-shrink-0"
                                 :style="'background-color: ' + sidebarColor + ';'">
                                <div class="space-y-4">
                                    <!-- Header Sidebar Mockup -->
                                    <div class="flex items-center gap-2 pb-2 border-b border-white/10">
                                        <div class="w-7 h-7 rounded-lg bg-black/40 p-1 flex items-center justify-center flex-shrink-0">
                                            <img :src="logoPreview" class="w-full h-full object-contain" alt="Logo preview">
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-extrabold text-[11px] block truncate leading-tight" x-text="appName"></span>
                                            <span class="text-[8px] text-indigo-300 font-bold tracking-wider uppercase">ADMIN</span>
                                        </div>
                                    </div>

                                    <!-- Enlaces Nav Mockup -->
                                    <div class="space-y-1 text-[10px]">
                                        <div class="px-2 py-1.5 rounded-lg font-bold text-white shadow-xs flex items-center gap-1.5"
                                             :style="'background-color: ' + primaryColor + ';'">
                                             <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                             <span>Dashboard</span>
                                        </div>
                                        <div class="px-2 py-1.5 rounded-lg text-white/70 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                                            <span>Usuarios</span>
                                        </div>
                                        <div class="px-2 py-1.5 rounded-lg text-white/70 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                                            <span>Nómina</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Sidebar Mockup -->
                                <div class="pt-2 border-t border-white/10 text-[9px] text-white/50 flex items-center gap-1.5">
                                    <div class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center text-[8px] text-white font-bold">A</div>
                                    <span class="truncate">Admin User</span>
                                </div>
                            </div>

                            <!-- Área de Contenido Mockup -->
                            <div class="flex-1 min-w-0 p-4 bg-slate-50 dark:bg-slate-900/60 flex flex-col justify-between space-y-3">
                                <div class="space-y-3">
                                    <!-- Topbar Mini -->
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                        <span class="font-extrabold text-slate-800 dark:text-slate-100 text-[11px]">Resumen de Nómina</span>
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full text-white shadow-2xs"
                                              :style="'background-color: ' + secondaryColor + ';'">
                                            Activo
                                        </span>
                                    </div>

                                    <!-- Tarjeta Métrica Mini -->
                                    <div class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs space-y-1">
                                        <span class="text-[9px] text-slate-400 font-semibold block">Total Liquidado</span>
                                        <div class="text-xs font-black text-slate-800 dark:text-slate-100">$18,450.00 MXN</div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-300"
                                                 :style="'width: 75%; background-color: ' + primaryColor + ';'"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón Primario Miniatura -->
                                <div class="pt-2 flex justify-end">
                                    <button type="button"
                                            class="px-3 py-1.5 rounded-lg text-white font-bold text-[10px] shadow-xs flex items-center gap-1.5"
                                            :style="'background-color: ' + primaryColor + ';'">
                                        <span>Generar Reporte</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 text-[11px] text-indigo-900 dark:text-indigo-300 flex items-start gap-2">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Cualquier cambio se reflejará al instante en todas las vistas de administración, cajeros, clientes y pantallas de acceso.</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
