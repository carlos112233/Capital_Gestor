<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-slate-800 leading-tight">
            {{ __('Configuración del Sistema') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto">
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
