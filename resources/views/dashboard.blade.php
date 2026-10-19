<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                {{ __('Resumen semanal') }}
            </h2>
                <div x-data="{ openDashboardPhoto: false }" class="flex items-center">
                    <button @click="openDashboardPhoto = true" class="focus:outline-none cursor-pointer transform hover:scale-105 transition-transform" title="Ver foto de perfil">
                        <img src="{{ Auth::user()->image_url }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';" class="w-10 h-10 rounded-full object-cover shadow-sm border-2 border-indigo-200 dark:border-indigo-800" alt="Foto de perfil">
                    </button>
                    <!-- Modal Foto de Perfil (Dashboard) -->
                    <div x-show="openDashboardPhoto" style="display: none;" x-transition class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
                        <div @click.away="openDashboardPhoto = false" class="relative w-full max-w-3xl flex justify-center items-center">
                            <button @click="openDashboardPhoto = false" class="absolute -top-12 right-0 md:-right-8 md:-top-8 text-white hover:text-slate-300 focus:outline-none bg-white/10 p-2 rounded-full backdrop-blur-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <img src="{{ Auth::user()->image_url }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10" alt="Foto de perfil">
                        </div>
                    </div>
                </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-12" style="padding:  0px 35px">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 dark:border-slate-700">
                <div class="p-6 text-gray-900 dark:text-slate-100">
                    
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Estado de cuenta semanal</h3>

                    <div class="overflow-x-auto" id="tour-tabla-saldos">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 border border-slate-200 dark:border-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Corte Anterior (Vencido)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Corte Actual</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Saldo Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse($resumen as $r)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div x-data="{ openTablePhoto: false }">
                                                    <button @click="openTablePhoto = true" class="focus:outline-none cursor-pointer transform hover:scale-110 transition-transform">
                                                        <img src="{{ $r->image_url ?? asset('img/default_user.svg') }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 shadow-sm" alt="Foto">
                                                    </button>
                                                    <!-- Modal Foto de Perfil (Tabla) -->
                                                    <div x-show="openTablePhoto" style="display: none;" x-transition class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
                                                        <div @click.away="openTablePhoto = false" class="relative w-full max-w-3xl flex justify-center items-center">
                                                            <button @click="openTablePhoto = false" class="absolute -top-12 right-0 md:-right-8 md:-top-8 text-white hover:text-slate-300 focus:outline-none bg-white/10 p-2 rounded-full backdrop-blur-md">
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                            <img src="{{ $r->image_url ?? asset('img/default_user.svg') }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10" alt="Foto">
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="font-medium text-slate-800 dark:text-white">{{ $r->nombre }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            @if($r->saldo_corte_anterior > 0)
                                                <span class="text-red-600 font-bold">${{ number_format($r->saldo_corte_anterior, 2) }}</span>
                                            @else
                                                <span class="text-gray-400 dark:text-slate-500">$0.00</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            @if($r->saldo_corte_actual > 0)
                                                <span class="text-gray-700 dark:text-slate-300">${{ number_format($r->saldo_corte_actual, 2) }}</span>
                                            @else
                                                <span class="text-gray-400 dark:text-slate-500">$0.00</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            @if($r->saldo >= 0)
                                                <span class="text-green-600 font-bold">${{ number_format($r->saldo, 2) }}</span>
                                            @else
                                                <span class="text-red-600 font-bold">${{ number_format(abs($r->saldo), 2) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-slate-400">
                                            No hay datos para este periodo
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8 flex justify-between items-center border-t border-slate-200 dark:border-slate-700 pt-6">
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Mis Comprobantes de Pago</h3>
                        <button id="tour-btn-subir-comprobante" onclick="openModal('upload-comprobante-modal')" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors cursor-pointer text-xs uppercase tracking-wider">
                            Subir Comprobante
                        </button>
                    </div>

                    @if(session('success'))
                        <div class="mt-4 p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(isset($comprobantes) && count($comprobantes) > 0)
                        <div class="mt-4 overflow-x-auto" id="tour-historial-comprobantes">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 border border-slate-200 dark:border-slate-700">
                                <thead class="bg-gray-50 dark:bg-slate-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Monto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Comprobante</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                    @foreach($comprobantes as $comp)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">{{ $comp->created_at->format('d/m/Y h:i A') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-bold">${{ number_format($comp->monto, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 dark:text-indigo-400">
                                                <a href="{{ Storage::url($comp->imagen) }}" target="_blank" class="hover:underline">Ver Imagen</a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if($comp->status == 'pendiente')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800/40">Pendiente</span>
                                                @elseif($comp->status == 'aprobado')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-green-200 dark:border-emerald-800/40">Aprobado</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-rose-950/60 dark:text-rose-400 border border-red-200 dark:border-rose-800/40">Rechazado</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500 dark:text-slate-400 italic">No has subido ningún comprobante recientemente.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Modal para subir comprobante -->
    <x-modal name="upload-comprobante-modal" focusable>
        <div class="p-6 text-left bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
            <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Subir Comprobante de Pago</h3>
                <button type="button" onclick="closeModal('upload-comprobante-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-white font-bold text-xl cursor-pointer">&times;</button>
            </div>
            <form method="POST" action="{{ route('comprobantes.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Monto Pagado ($)</label>
                    <input type="number" step="0.01" name="monto" class="w-full bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej. 500.00" required>
                </div>

                <div class="mb-4">
                    <x-file-dropzone
                        name="imagen"
                        label="Captura / Foto del comprobante"
                        accept="image/*"
                        :required="true"
                        placeholder="Haga clic para seleccionar comprobante o arrastre aquí"
                        help-text="Formatos permitidos: JPG, PNG, WEBP (Máx. 5MB)"
                    />
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Notas (Opcional)</label>
                    <textarea name="notas" rows="2" class="w-full bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Referencia o mensaje para el administrador..."></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button type="button" onclick="closeModal('upload-comprobante-modal')" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md cursor-pointer">
                        Subir Comprobante
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @php
        $hasSeenTutorial = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'dashboard')->exists();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const forceTutorial = new URLSearchParams(window.location.search).get('tutorial') === 'true';
            const hasSeenTutorial = @json($hasSeenTutorial);

            if (forceTutorial || !hasSeenTutorial) {
                const driverObj = window.driver.js.driver({
                    showProgress: true,
                    nextBtnText: 'Siguiente ➔',
                    prevBtnText: '⬅ Anterior',
                    doneBtnText: '¡Entendido!',
                    progressText: 'Paso @{{current}} de @{{total}}',
                    steps: [
                        {
                            element: '#tour-tabla-saldos',
                            popover: {
                                title: 'Tu Estado de Cuenta',
                                description: 'Aquí puedes ver exactamente cuánto debes de cortes pasados y de tus consumos actuales.',
                                side: "top",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-btn-subir-comprobante',
                            popover: {
                                title: 'Reporta tus pagos',
                                description: 'Cuando nos hagas una transferencia, haz clic aquí para enviarnos la foto de tu comprobante y aplicarlo a tu saldo.',
                                side: "bottom",
                                align: 'end'
                            }
                        },
                        {
                            element: '#tour-historial-comprobantes',
                            popover: {
                                title: 'Historial de Comprobantes',
                                description: 'Lleva un control de todos los comprobantes que has subido y revisa si ya fueron aprobados por nosotros.',
                                side: "top",
                                align: 'start'
                            }
                        }
                    ],
                    onDestroyStarted: () => {
                        if (!driverObj.hasNextStep() || confirm("¿Seguro que quieres saltar el tutorial?")) {
                            driverObj.destroy();
                            // Marcar como visto en la base de datos
                            fetch('{{ route("tutorial.marcar-visto") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ tutorial_name: 'dashboard' })
                            });
                        }
                    }
                });

                // Remover el parámetro tutorial de la URL sin recargar para mantener limpio el historial
                if (forceTutorial) {
                    const url = new URL(window.location);
                    url.searchParams.delete('tutorial');
                    window.history.replaceState({}, '', url);
                }

                driverObj.drive();
            }
        });
    </script>
</x-app-layout>

