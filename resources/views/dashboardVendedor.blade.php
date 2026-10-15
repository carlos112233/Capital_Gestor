<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </span>
                    Panel de Vendedor
                </h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">
                    Resumen operativo de ventas, comisiones y liquidación de nómina de 
                    <span class="font-bold text-slate-800">{{ $vendedor->name }}</span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Selector de vendedor si el Administrador está supervisando --}}
                @if(Auth::user()->hasRole('admin') && $vendedores->count() > 0)
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs text-xs">
                        <span class="font-bold text-slate-500 uppercase">Supervisar:</span>
                        <select onchange="window.location.href='{{ route('dashboardVendedor') }}?vendedor_id=' + this.value"
                                class="text-xs font-semibold text-slate-800 border-none bg-transparent focus:ring-0 cursor-pointer pr-8 py-0.5">
                            @foreach($vendedores as $v)
                                <option value="{{ $v->id }}" {{ $v->id === $vendedor->id ? 'selected' : '' }}>
                                    {{ $v->name }} ({{ $v->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Avatar del Vendedor --}}
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs">
                    <img src="{{ $vendedor->image_url }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';"
                         class="w-7 h-7 rounded-full object-cover border border-slate-200" alt="Avatar">
                    <span class="text-xs font-bold text-slate-700">{{ $vendedor->name }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Alertas de Éxito / Error --}}
            @if(session('success'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 shadow-xs flex items-center justify-between">
                    <div class="flex items-center gap-3 text-emerald-800 text-sm font-semibold">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 shadow-xs">
                    <div class="flex items-start gap-3 text-rose-800 text-sm font-semibold">
                        <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            {{-- Grid de 4 Tarjetas Métricas (Solo Lectura) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                {{-- 1. Total Vendido --}}
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Vendido</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        ${{ number_format($totalVendido, 2) }}
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        Suma acumulada de ventas registradas
                    </p>
                </div>

                {{-- 2. Comisión / Ganancia Estimada (20%) --}}
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ganancia Estimada ({{ number_format($porcentajeComision, 0) }}%)</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-600 tracking-tight">
                        ${{ number_format($comisionEstimada, 2) }}
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        {{ number_format($porcentajeComision, 0) }}% de comisión sobre ventas
                    </p>
                </div>

                {{-- 3. Pagos Recibidos --}}
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pagos Recibidos</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 tracking-tight">
                        ${{ number_format($totalPagadoNeto, 2) }}
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        Historial liquidado por el administrador
                    </p>
                </div>

                {{-- 4. Saldo Pendiente por Cobrar --}}
                <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-2xl p-5 shadow-md text-white relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Saldo por Cobrar</span>
                        <div class="w-9 h-9 rounded-xl bg-white/10 text-indigo-300 flex items-center justify-center backdrop-blur-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">
                        ${{ number_format($saldoPendienteCobro, 2) }}
                    </div>
                    <p class="text-xs text-indigo-300 mt-1 font-medium">
                        Comisión acumulada pendiente de pago
                    </p>
                </div>

            </div>

            {{-- Banner Informativo de Solo Lectura --}}
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-slate-200/60 text-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Panel de Solo Lectura</p>
                        <p class="text-[11px] text-slate-500">
                            Las liquidaciones de nómina y comisiones son auditadas, autorizadas y pagadas directamente por la Administración central.
                        </p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Cuenta Operativa Activa
                </span>
            </div>

            {{-- Tablas de Historial de Liquidaciones y Ventas Recientes --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Tabla 1: Historial de Pagos Recibidos --}}
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Historial de Pagos Recibidos
                        </h4>
                        <span class="text-xs font-semibold text-slate-500">{{ $historialPagos->count() }} pagos</span>
                    </div>

                    <div class="overflow-x-auto flex-1">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Fecha</th>
                                    <th class="px-4 py-3 text-right">Base Vendida</th>
                                    <th class="px-4 py-3 text-center">% Retención</th>
                                    <th class="px-4 py-3 text-right">Neto Recibido</th>
                                    <th class="px-4 py-3 text-center">Método</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($historialPagos as $pago)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium">
                                            {{ $pago->created_at ? $pago->created_at->format('d/m/Y H:i') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap text-slate-500 font-semibold">
                                            ${{ number_format((float) $pago->total_vendido, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap text-amber-600 font-bold">
                                            {{ number_format((float) $pago->porcentaje_retencion, 1) }}%
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap font-extrabold text-emerald-600">
                                            ${{ number_format((float) $pago->monto_neto_pagado, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">
                                                {{ $pago->metodo_pago }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                            No se han emitido liquidaciones previas para este vendedor.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tabla 2: Ventas Recientes del Vendedor --}}
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Últimas Ventas Realizadas
                        </h4>
                        <a href="{{ route('ventas.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">
                            Ver todas &rarr;
                        </a>
                    </div>

                    <div class="overflow-x-auto flex-1">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">ID</th>
                                    <th class="px-4 py-3 text-left">Cliente</th>
                                    <th class="px-4 py-3 text-left">Artículo</th>
                                    <th class="px-4 py-3 text-right">Monto</th>
                                    <th class="px-4 py-3 text-center">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($ventasRecientes as $vta)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-400 font-mono">
                                            #{{ $vta->id }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold text-slate-800">
                                            {{ $vta->user?->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                            {{ $vta->articulo?->nombre ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap font-bold text-slate-900">
                                            ${{ number_format((float) $vta->total_venta, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap text-slate-400">
                                            {{ $vta->created_at ? $vta->created_at->format('d/m H:i') : 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                            Aún no hay ventas registradas por este vendedor.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
