<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between no-print">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Comprobante de Venta #{{ $venta->id }}</span>
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('ventas.index') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl font-semibold text-sm transition-all">
                    &larr; Volver
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Imprimir Nota</span>
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        @page {
            margin: 12mm;
        }
        @media print {
            .no-print, nav, header { display: none !important; }
            body { background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important; }
            .py-8 { padding-top: 0 !important; padding-bottom: 0 !important; }
            .max-w-3xl { max-width: 100% !important; width: 100% !important; }
            .print-container { box-shadow: none !important; border: 1px solid #cbd5e1 !important; border-radius: 12px !important; margin: 0 auto !important; width: 100% !important; padding: 28px !important; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-sm border border-slate-200 print-container">
                <!-- Encabezado de la Nota -->
                <div class="flex justify-between items-start border-b border-slate-200 pb-6 mb-6">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">EL BAJÓN</h1>
                        <p class="text-xs text-slate-500 mt-1">Comprobante de Venta Directa</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full">
                            Venta #{{ $venta->id }}
                        </span>
                        <p class="text-xs text-slate-500 mt-2">Fecha: {{ $venta->created_at ? $venta->created_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A') }}</p>
                    </div>
                </div>

                <!-- Información del Cliente -->
                <div class="grid grid-cols-2 gap-6 bg-slate-50 p-5 rounded-xl mb-6 border border-slate-100">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400">Cliente</p>
                        <p class="font-semibold text-slate-800 mt-1 text-base">{{ $venta->user->name ?? 'Cliente General' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $venta->user->email ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400">Teléfono</p>
                        <p class="font-semibold text-slate-800 mt-1 text-base">{{ $venta->user->telefono ?? 'Sin teléfono' }}</p>
                    </div>
                </div>

                <!-- Detalle del Artículo -->
                <table class="w-full text-left border-collapse mb-6">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500 font-bold bg-slate-100">
                            <th class="py-3 px-4">Artículo</th>
                            <th class="py-3 px-4 text-center">Cantidad</th>
                            <th class="py-3 px-4 text-right">Precio Unitario</th>
                            <th class="py-3 px-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr>
                            <td class="py-4 px-4 font-semibold text-slate-800">
                                {{ $venta->articulo->nombre ?? 'Artículo' }}
                                @if($venta->descripcion)
                                    <p class="text-xs font-normal text-slate-500 mt-1">{{ $venta->descripcion }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center font-medium">{{ $venta->cantidad }}</td>
                            <td class="py-4 px-4 text-right">${{ number_format($venta->precio_venta, 2) }}</td>
                            <td class="py-4 px-4 text-right font-bold text-slate-900">${{ number_format($venta->total_venta, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Resumen de Total -->
                <div class="flex justify-end border-t border-slate-200 pt-6 mt-4">
                    <div class="w-full sm:w-80 bg-slate-50 p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                        <div class="flex justify-between items-center text-slate-600 text-sm">
                            <span class="font-medium">Subtotal:</span>
                            <span class="font-semibold text-slate-800 text-base">${{ number_format($venta->total_venta, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-900 border-t border-slate-200 pt-3">
                            <span class="font-bold text-base">Total a Cobrar:</span>
                            <span class="font-black text-emerald-600 text-2xl">${{ number_format($venta->total_venta, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pie de página -->
                <div class="mt-10 text-center text-xs text-slate-400 border-t border-slate-100 pt-6">
                    <p class="font-medium">¡Gracias por su compra!</p>
                    <p class="mt-1 font-semibold text-slate-500">El Bajón</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
