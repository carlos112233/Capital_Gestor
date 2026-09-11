<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between no-print">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Comprobante de Venta #{{ $venta->id }}</span>
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('ventas.index') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl font-semibold text-sm transition-all">
                    &larr; Volver
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Imprimir Nota</span>
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        @page {
            margin: 15mm;
        }

        @media print {
            .no-print, nav, header { display: none !important; }
            body { background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important; }
            .py-8 { padding-top: 0 !important; padding-bottom: 0 !important; }
            .max-w-3xl { max-width: 100% !important; width: 100% !important; }
            .print-container { box-shadow: none !important; border: none !important; margin: 0 auto !important; width: 100% !important; padding: 0 !important; }
            .bg-\[\#f4f7fb\] { background-color: #f4f7fb !important; -webkit-print-color-adjust: exact; color-adjust: exact; }
            .bg-\[\#d6e2ee\] { background-color: #d6e2ee !important; -webkit-print-color-adjust: exact; color-adjust: exact; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-sm border border-slate-200 print-container font-sans text-black">
                
                <!-- Header Section -->
                <div class="flex justify-between items-start border-b-[1.5px] border-slate-300 pb-4 mb-5">
                    <div class="w-1/2">
                        <!-- Espacio para el logo -->
                        <div class="text-[22px] font-extrabold text-slate-800 uppercase tracking-wide">EL BAJÓN</div>
                    </div>
                    <div class="w-1/2 text-right">
                        <div class="text-[22px] font-extrabold text-slate-800 uppercase tracking-wide">EL BAJÓN</div>
                        <div class="text-sm font-bold text-black mt-0.5">Comprobante de Venta</div>
                        <div class="text-[11px] text-slate-600 mt-1">Fecha de Emisión: {{ $venta->created_at ? $venta->created_at->format('d/m/Y') : now()->format('d/m/Y') }}</div>
                    </div>
                </div>

                <!-- Client Info Grid -->
                <div class="flex gap-4 mb-5">
                    <div class="w-full bg-[#f4f7fb] border border-slate-300 rounded-xl p-4">
                        <div class="mb-1 text-xs"><span class="font-bold text-black">Cliente:</span> <span class="text-black">{{ $venta->user->name ?? 'Cliente General' }}</span></div>
                        <div class="mb-1 text-xs"><span class="font-bold text-black">Dirección:</span> <span class="text-black">{{ $venta->user->direccion ?? 'Calle Falsa 123, Colonia Centro' }}</span></div>
                        <div class="mb-1 text-xs"><span class="font-bold text-black">RFC:</span> <span class="text-black">{{ $venta->user->rfc ?? 'GALA900101XYZ' }}</span></div>
                        <div class="mb-0 text-xs"><span class="font-bold text-black">Teléfono:</span> <span class="text-black">{{ $venta->user->telefono ?? 'Sin teléfono' }}</span></div>
                    </div>
                </div>

                <!-- Consumo Card -->
                <div class="bg-[#f4f7fb] border border-slate-300 rounded-xl text-center py-4 px-3 mb-5">
                    <div class="text-[15px] font-bold text-black">Total Venta</div>
                    <div class="text-[34px] font-black text-black mt-1 tracking-tight">${{ number_format($venta->total_venta, 2) }}</div>
                </div>

                <!-- Table Items -->
                <div class="border border-slate-300 rounded-lg overflow-hidden mb-5">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#d6e2ee]">
                                <th class="py-2 px-3 text-center border border-slate-300 text-xs font-bold text-black">Fecha</th>
                                <th class="py-2 px-3 text-left border border-slate-300 text-xs font-bold text-black">Concepto</th>
                                <th class="py-2 px-3 text-center border border-slate-300 text-xs font-bold text-black">Referencia</th>
                                <th class="py-2 px-3 text-right border border-slate-300 text-xs font-bold text-black">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white">
                                <td class="py-2 px-3 border border-slate-300 text-xs text-black text-center">{{ $venta->created_at ? $venta->created_at->format('d/m/Y') : now()->format('d/m/Y') }}</td>
                                <td class="py-2 px-3 border border-slate-300 text-xs text-black">
                                    {{ $venta->articulo->nombre ?? 'Artículo de Venta' }}
                                    @if($venta->cantidad > 1)
                                        <span class="text-slate-500">(x{{ $venta->cantidad }})</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 border border-slate-300 text-xs text-black text-center">#{{ $venta->id }}</td>
                                <td class="py-2 px-3 border border-slate-300 text-xs text-black text-right font-bold">${{ number_format($venta->total_venta, 2) }}</td>
                            </tr>
                            <tr class="bg-white">
                                <td colspan="3" class="py-2 px-3 border border-slate-300 text-xs text-black text-right font-bold">Total:</td>
                                <td class="py-2 px-3 border border-slate-300 text-xs text-black text-right font-bold">${{ number_format($venta->total_venta, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="text-center text-[10px] text-slate-500 mt-6 pt-4 border-t border-slate-200">
                    Documento oficial emitido por EL BAJÓN © {{ date('Y') }}. Todos los derechos reservados.
                </div>

            </div>
        </div>
    </div>
</x-app-layout>