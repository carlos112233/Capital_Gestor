<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Resumen semanal') }}
            </h2>
                <div x-data="{ openDashboardPhoto: false }" class="flex items-center">
                    <button @click="openDashboardPhoto = true" class="focus:outline-none cursor-pointer transform hover:scale-105 transition-transform" title="Ver foto de perfil">
                        <img src="{{ route('user.image', Auth::user()->id) }}?v={{ Auth::user()->updated_at?->timestamp }}" class="w-10 h-10 rounded-full object-cover shadow-sm border-2 border-indigo-200" alt="Foto de perfil">
                    </button>
                    <!-- Modal Foto de Perfil (Dashboard) -->
                    <div x-show="openDashboardPhoto" style="display: none;" x-transition class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
                        <div @click.away="openDashboardPhoto = false" class="relative w-full max-w-3xl flex justify-center items-center">
                            <button @click="openDashboardPhoto = false" class="absolute -top-12 right-0 md:-right-8 md:-top-8 text-white hover:text-slate-300 focus:outline-none bg-white/10 p-2 rounded-full backdrop-blur-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <img src="{{ route('user.image', Auth::user()->id) }}?v={{ Auth::user()->updated_at?->timestamp }}" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10">
                        </div>
                    </div>
                </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-12" style="padding:  0px 35px">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <h3 class="text-lg font-semibold mb-4">Estado de cuenta semanal</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Corte Anterior (Vencido)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Corte Actual</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($resumen as $r)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $r->nombre }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            @if($r->saldo_corte_anterior > 0)
                                                <span class="text-red-600 font-bold">${{ number_format($r->saldo_corte_anterior, 2) }}</span>
                                            @else
                                                <span class="text-gray-400">$0.00</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            @if($r->saldo_corte_actual > 0)
                                                <span class="text-gray-700">${{ number_format($r->saldo_corte_actual, 2) }}</span>
                                            @else
                                                <span class="text-gray-400">$0.00</span>
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
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            No hay datos para este periodo
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8 flex justify-between items-center border-t pt-6">
                        <h3 class="text-lg font-semibold text-slate-800">Mis Comprobantes de Pago</h3>
                        <button onclick="openModal('upload-comprobante-modal')" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Subir Comprobante
                        </button>
                    </div>

                    @if(session('success'))
                        <div class="mt-4 p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(isset($comprobantes) && count($comprobantes) > 0)
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprobante</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($comprobantes as $comp)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $comp->created_at->format('d/m/Y h:i A') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">${{ number_format($comp->monto, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600">
                                                <a href="{{ Storage::url($comp->imagen) }}" target="_blank" class="hover:underline">Ver Imagen</a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if($comp->status == 'pendiente')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                                                @elseif($comp->status == 'aprobado')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aprobado</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rechazado</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500 italic">No has subido ningún comprobante recientemente.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Modal para subir comprobante -->
    <x-modal name="upload-comprobante-modal" focusable>
        <div class="p-6 text-left">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Subir Comprobante de Pago</h3>
                <button type="button" onclick="closeModal('upload-comprobante-modal')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('comprobantes.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Monto Pagado ($)</label>
                    <input type="number" step="0.01" name="monto" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej. 500.00" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Captura / Foto del comprobante</label>
                    <input type="file" name="imagen" accept="image/*" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                    <p class="text-xs text-gray-500 mt-1">Formatos soportados: JPG, PNG, JPEG. Máx 5MB.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Notas (Opcional)</label>
                    <textarea name="notas" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Referencia o mensaje para el administrador..."></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t pt-4">
                    <button type="button" onclick="closeModal('upload-comprobante-modal')" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md cursor-pointer">
                        Subir Comprobante
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>

