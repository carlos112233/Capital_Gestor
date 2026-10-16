<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="font-bold text-xl text-slate-800 dark:text-white leading-tight flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                    {{ __('Nómina y Liquidaciones de Vendedores') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Control contable centralizado: cálculo de retención empresa ({{ number_format($retencionDefault, 0) }}%), ganancia neta del vendedor ({{ number_format(100 - $retencionDefault, 0) }}%) y auditoría de dispersiones.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" onclick="window.iniciarTourNomina()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition-all cursor-pointer"
                    title="Iniciar recorrido guiado por este módulo">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Guía Interactiva</span>
                </button>

                <button id="tour-btn-config-retencion" type="button"
                    onclick="openRetencionModal('', 'Global (Todos los vendedores)', {{ $retencionDefault }})"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Ajustar % Retención ({{ number_format($retencionDefault, 0) }}%)</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Alertas de Sesión --}}
            @if (session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/40 flex items-center gap-3 text-emerald-800 dark:text-emerald-300 text-sm font-semibold shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/40 flex items-center gap-3 text-rose-800 dark:text-rose-300 text-sm font-semibold shadow-xs">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- 1. KPIs Globales de Nómina y Retenciones --}}
            <div id="tour-kpis-nomina" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Vendido (Base)</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">${{ number_format($kpis['total_vendido'], 2) }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">100% de ventas operadas</p>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Retención Empresa ({{ number_format($retencionDefault, 0) }}%)</p>
                    <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">${{ number_format($kpis['monto_retenido'], 2) }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Margen retenido por negocio</p>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-amber-200/80 dark:border-amber-800/40 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Ganancia Neta ({{ number_format(100 - $retencionDefault, 0) }}%)</p>
                    <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">${{ number_format($kpis['ganancia_neta'], 2) }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Monto neto devengado</p>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-emerald-200/80 dark:border-emerald-800/40 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total Liquidado</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($kpis['total_pagado'], 2) }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Pagos netos ya dispersados</p>
                </div>

                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl p-5 shadow-md border border-slate-800">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-300">Saldo por Dispersar</p>
                    <p class="text-2xl font-black text-white mt-1">${{ number_format($kpis['saldo_pendiente'], 2) }}</p>
                    <p class="text-[11px] text-indigo-200/80 mt-1">Pendiente de liquidación</p>
                </div>
            </div>

            {{-- 2. Tabla de Resumen de Vendedores Activos --}}
            <div id="tour-tabla-vendedores" class="bg-white dark:bg-slate-800 overflow-hidden shadow-xs sm:rounded-2xl border border-slate-200/80 dark:border-slate-700">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <span>👥</span> Resumen de Vendedores Activos
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Desglose individual con fórmula contable: <strong>Total Vendido − Retención Empresa = Ganancia Neta Vendedor</strong>.
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/40">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            {{ $vendedores->count() }} {{ $vendedores->count() === 1 ? 'Vendedor Activo' : 'Vendedores Activos' }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3.5 text-left">Vendedor</th>
                                    <th class="px-5 py-3.5 text-right">Total Vendido</th>
                                    <th class="px-5 py-3.5 text-center">% Retención</th>
                                    <th class="px-5 py-3.5 text-right">Retenido Empresa</th>
                                    <th class="px-5 py-3.5 text-right">Ganancia Neta</th>
                                    <th class="px-5 py-3.5 text-right">Pagos Realizados</th>
                                    <th class="px-5 py-3.5 text-right">Saldo Pendiente</th>
                                    <th class="px-5 py-3.5 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                                @forelse($vendedores as $v)
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $v->image_url }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';"
                                                     class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" alt="Avatar">
                                                <div>
                                                    <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $v->name }}</div>
                                                    <div class="text-[11px] text-slate-400">{{ $v->email }}</div>
                                                    @if(!empty($v->telefono))
                                                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                                                            <span>📱 {{ $v->telefono }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap font-bold text-slate-900 dark:text-white text-sm">
                                            ${{ number_format($v->total_vendido, 2) }}
                                            <div class="text-[11px] text-slate-400 font-normal">{{ $v->ventas_operador_count ?? 0 }} ventas</div>
                                        </td>
                                        <td class="px-5 py-4 text-center whitespace-nowrap">
                                            <button type="button"
                                                onclick="openRetencionModal('{{ $v->id }}', '{{ addslashes($v->name) }}', {{ $v->porcentaje_retencion }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 text-slate-700 dark:text-slate-200 hover:text-indigo-700 dark:hover:text-indigo-300 font-bold text-xs border border-slate-200 dark:border-slate-600 transition-colors cursor-pointer"
                                                title="Cambiar porcentaje de retención para este vendedor">
                                                <span>{{ number_format($v->porcentaje_retencion, 0) }}%</span>
                                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap font-semibold text-slate-500 dark:text-slate-400 text-sm">
                                            -${{ number_format($v->monto_retenido, 2) }}
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap font-bold text-amber-600 dark:text-amber-400 text-sm">
                                            ${{ number_format($v->ganancia_neta, 2) }}
                                            <div class="text-[10px] text-amber-700/70 dark:text-amber-400/80 font-semibold">{{ number_format($v->porcentaje_ganancia, 0) }}% neto</div>
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                            ${{ number_format($v->total_pagado_neto, 2) }}
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black {{ $v->saldo_pendiente > 0 ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                                                ${{ number_format($v->saldo_pendiente, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('dashboardVendedor', ['vendedor_id' => $v->id]) }}"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold text-xs border border-slate-200 dark:border-slate-600 transition-colors"
                                                   title="Inspeccionar Panel del Vendedor">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>Panel</span>
                                                </a>

                                                <button type="button"
                                                        onclick="openLiquidacionModal({{ $v->id }}, '{{ addslashes($v->name) }}', {{ $v->base_pendiente }}, {{ $v->total_vendido }}, {{ $v->ganancia_neta }}, {{ $v->saldo_pendiente }}, {{ $v->porcentaje_retencion }})"
                                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs hover:shadow transition-all cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>Pagar / Liquidar</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                            No hay usuarios con el rol <strong>vendedor</strong> registrados en el sistema.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 3. Historial Completo de Pagos Realizados (Auditable) --}}
            <div id="tour-historial-pagos" class="bg-white dark:bg-slate-800 overflow-hidden shadow-xs sm:rounded-2xl border border-slate-200/80 dark:border-slate-700">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-700">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <span>🧾</span> Historial Auditable de Pagos y Liquidaciones
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Bitácora histórica de todas las dispersiones realizadas a vendedores con folio, método de pago y administrador autorizante.
                            </p>
                        </div>

                        {{-- Filtros Rápidos --}}
                        <form method="GET" action="{{ route('admin.pagos-vendedores.index') }}" class="flex flex-wrap items-center gap-2.5">
                            <select name="vendedor_id" onchange="this.form.submit()"
                                class="text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                <option value="">Todos los vendedores</option>
                                @foreach($vendedores as $v)
                                    <option value="{{ $v->id }}" {{ (string) request('vendedor_id') === (string) $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="metodo_pago" onchange="this.form.submit()"
                                class="text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                <option value="">Todos los métodos</option>
                                <option value="efectivo" {{ request('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                <option value="transferencia_bbva" {{ request('metodo_pago') === 'transferencia_bbva' ? 'selected' : '' }}>Transferencia BBVA</option>
                                <option value="mercado_pago" {{ request('metodo_pago') === 'mercado_pago' ? 'selected' : '' }}>Mercado Pago</option>
                                <option value="deposito" {{ request('metodo_pago') === 'deposito' ? 'selected' : '' }}>Depósito Bancario</option>
                                <option value="otro" {{ request('metodo_pago') === 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>

                            @if(request()->filled('vendedor_id') || request()->filled('metodo_pago'))
                                <a href="{{ route('admin.pagos-vendedores.index') }}"
                                   class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 text-xs font-bold transition-colors">
                                    Limpiar
                                </a>
                            @endif
                        </form>
                    </div>

                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Folio</th>
                                    <th class="px-4 py-3 text-left">Fecha</th>
                                    <th class="px-4 py-3 text-left">Vendedor</th>
                                    <th class="px-4 py-3 text-right">Base Vendida</th>
                                    <th class="px-4 py-3 text-right">Retención</th>
                                    <th class="px-4 py-3 text-right">Neto Pagado</th>
                                    <th class="px-4 py-3 text-center">Método</th>
                                    <th class="px-4 py-3 text-left">Notas</th>
                                    <th class="px-4 py-3 text-left">Autorizado Por</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                                @forelse($historialPagos as $pago)
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                                        <td class="px-4 py-3.5 whitespace-nowrap font-black text-indigo-600 dark:text-indigo-400">
                                            #{{ $pago->id }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-400">
                                            <div class="font-semibold text-slate-800 dark:text-white">{{ $pago->created_at?->format('d/m/Y') }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $pago->created_at?->format('H:i') }} hrs</div>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $pago->vendedor?->name ?? 'Vendedor #' . $pago->vendedor_id }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $pago->vendedor?->email ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap font-semibold text-slate-800 dark:text-slate-200">
                                            ${{ number_format((float) $pago->total_vendido, 2) }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap text-slate-500 dark:text-slate-400">
                                            <span class="font-semibold text-rose-600 dark:text-rose-400">-${{ number_format((float) $pago->monto_retenido, 2) }}</span>
                                            <div class="text-[10px] text-slate-400">{{ number_format((float) $pago->porcentaje_retencion, 1) }}%</div>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-black border border-emerald-200 dark:border-emerald-800/40 text-xs">
                                                ${{ number_format((float) $pago->monto_neto_pagado, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-[11px] capitalize">
                                                {{ str_replace('_', ' ', (string) $pago->metodo_pago) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300 max-w-xs truncate" title="{{ $pago->notas }}">
                                            {{ $pago->notas ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-300 font-medium">
                                            {{ $pago->registradoPor?->name ?? 'Administrador' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-10 text-center text-slate-400">
                                            Aún no se han registrado liquidaciones de nómina para los criterios seleccionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $historialPagos->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal 1: Registrar Liquidación / Pago a Vendedor --}}
    <x-modal name="liquidar-vendedor-modal">
        <div class="p-6 text-left bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
            <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black border border-indigo-100 dark:border-indigo-800/40">
                        $
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Registrar Liquidación de Vendedor</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Vendedor: <strong id="liq_vendedor_nombre" class="text-slate-700 dark:text-slate-200"></strong></p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('liquidar-vendedor-modal')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-white font-bold text-xl cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('pagos-vendedores.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="vendedor_id" id="liq_vendedor_id">

                {{-- Total Vendido a Liquidar --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Total Vendido Base ($)</label>
                    <input type="number" step="0.01" min="0.01" name="total_vendido" id="liq_total_vendido" oninput="recalcularLiquidacion()" required
                           class="w-full text-base font-bold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">Monto base acumulado de ventas sobre el que se efectúa la liquidación.</p>
                </div>

                {{-- Porcentaje y Retención Calculada --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">% Retención Empresa</label>
                        <div class="relative">
                            <input type="number" step="0.5" min="0" max="100" name="porcentaje_retencion" id="liq_porcentaje" value="20.00" oninput="recalcularLiquidacion()" required
                                   class="w-full text-base font-bold text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                            <span class="absolute right-3 top-2.5 font-bold text-slate-400 text-sm">%</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Retenido Empresa ($)</label>
                        <input type="number" step="0.01" name="monto_retenido" id="liq_monto_retenido" readonly
                               class="w-full text-base font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-xl border-slate-200 dark:border-slate-700">
                    </div>
                </div>

                {{-- Monto Neto a Pagar al Vendedor --}}
                <div class="bg-indigo-50/70 dark:bg-indigo-950/40 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800/40">
                    <label class="block text-xs font-bold uppercase text-indigo-900 dark:text-indigo-300 mb-1">
                        Monto Neto a Pagar al Vendedor (<span id="liq_label_ganancia_pct">80</span>%) ($)
                    </label>
                    <div class="flex items-center gap-1 text-2xl font-black text-indigo-700 dark:text-indigo-400">
                        <span>$</span>
                        <input type="number" step="0.01" min="0.01" name="monto_neto_pagado" id="liq_monto_neto" oninput="ajustarNetoManual()" required
                               class="w-full text-2xl font-black text-indigo-700 dark:text-indigo-400 bg-transparent border-none p-0 focus:ring-0">
                    </div>
                    <p class="text-[11px] text-indigo-500 dark:text-indigo-400/80 mt-0.5 font-medium">Monto efectivo a transferir o entregar al vendedor (Total Vendido − Retención).</p>
                </div>

                {{-- Método de Pago --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full text-sm font-semibold text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="efectivo" selected>Efectivo</option>
                        <option value="transferencia_bbva">Transferencia BBVA</option>
                        <option value="mercado_pago">Mercado Pago</option>
                        <option value="deposito">Depósito Bancario</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                {{-- Notas --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Notas / Observaciones (Opcional)</label>
                    <textarea name="notas" rows="2" placeholder="Ej: Liquidación semanal por ventas generadas..."
                              class="w-full text-xs text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-[11px] text-emerald-800 dark:text-emerald-300 flex items-center gap-2 font-medium">
                    <span>📲</span>
                    <span>Al confirmar, si el vendedor cuenta con número telefónico registrado, recibirá automáticamente su comprobante desglosado por WhatsApp.</span>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button type="button" onclick="closeModal('liquidar-vendedor-modal')"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md cursor-pointer">
                        Confirmar y Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    {{-- Modal 2: Actualizar Porcentaje de Retención --}}
    <x-modal name="configurar-retencion-modal">
        <div class="p-6 text-left bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
            <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black border border-amber-200 dark:border-amber-800/40">
                        %
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Configurar Porcentaje de Retención</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Alcance: <strong id="ret_alcance_nombre" class="text-slate-700 dark:text-slate-200">Global</strong></p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('configurar-retencion-modal')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-white font-bold text-xl cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.pagos-vendedores.retencion') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="vendedor_id" id="ret_vendedor_id" value="">

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">% Retención para la Empresa</label>
                    <div class="relative">
                        <input type="number" step="0.5" min="0" max="100" name="porcentaje_retencion" id="ret_porcentaje_input"
                               value="{{ $retencionDefault }}" oninput="actualizarPreviewRetencion()" required
                               class="w-full text-lg font-black text-slate-900 dark:text-white bg-white dark:bg-slate-800 rounded-xl border-slate-300 dark:border-slate-700 focus:border-amber-500 focus:ring-amber-500 pr-10">
                        <span class="absolute right-3.5 top-2.5 font-black text-slate-400 text-base">%</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Porcentaje que retiene la administración sobre el total vendido por el vendedor (por defecto: 20%).</p>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400 block">% Neto para el Vendedor</span>
                        <span class="text-[11px] text-slate-400">Calculado como 100% − % Retención</span>
                    </div>
                    <span id="ret_preview_ganancia" class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format(100 - $retencionDefault, 1) }}%</span>
                </div>

                <div id="ret_global_checkbox_wrap" class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="aplicar_todos" id="ret_aplicar_todos" value="1"
                           class="rounded border-slate-300 dark:border-slate-700 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer">
                    <label for="ret_aplicar_todos" class="text-xs text-slate-700 dark:text-slate-300 font-medium cursor-pointer">
                        Establecer como porcentaje estándar para todos los vendedores
                    </label>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button type="button" onclick="closeModal('configurar-retencion-modal')"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm shadow-md cursor-pointer">
                        Guardar Porcentaje
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>

@php
    $hasSeenTutorialNomina = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'nomina_vendedores')->exists();
@endphp

<script>
    function openLiquidacionModal(vendedorId, vendedorNombre, basePendiente, totalVendido, gananciaNeta, saldoPendiente, porcentajeConfigurado) {
        document.getElementById('liq_vendedor_id').value = vendedorId;
        document.getElementById('liq_vendedor_nombre').innerText = vendedorNombre;

        const baseSugerida = basePendiente > 0 ? basePendiente : (totalVendido > 0 ? totalVendido : 0);
        document.getElementById('liq_total_vendido').value = parseFloat(baseSugerida).toFixed(2);
        document.getElementById('liq_porcentaje').value = parseFloat(porcentajeConfigurado ?? 20).toFixed(2);

        recalcularLiquidacion();
        openModal('liquidar-vendedor-modal');
    }

    function recalcularLiquidacion() {
        const totalBase = parseFloat(document.getElementById('liq_total_vendido').value) || 0;
        const porcentajeRetencion = parseFloat(document.getElementById('liq_porcentaje').value) || 0;
        const montoRetenido = Math.round((totalBase * (porcentajeRetencion / 100)) * 100) / 100;
        const montoNeto = Math.max(0, Math.round((totalBase - montoRetenido) * 100) / 100);

        document.getElementById('liq_monto_retenido').value = montoRetenido.toFixed(2);
        document.getElementById('liq_monto_neto').value = montoNeto.toFixed(2);

        const labelPct = document.getElementById('liq_label_ganancia_pct');
        if (labelPct) {
            labelPct.innerText = Math.max(0, Math.round((100 - porcentajeRetencion) * 10) / 10);
        }
    }

    function ajustarNetoManual() {
        const totalBase = parseFloat(document.getElementById('liq_total_vendido').value) || 0;
        const montoNeto = parseFloat(document.getElementById('liq_monto_neto').value) || 0;
        const montoRetenido = Math.max(0, Math.round((totalBase - montoNeto) * 100) / 100);

        document.getElementById('liq_monto_retenido').value = montoRetenido.toFixed(2);
    }

    function openRetencionModal(vendedorId, alcanceNombre, porcentajeActual) {
        document.getElementById('ret_vendedor_id').value = vendedorId || '';
        document.getElementById('ret_alcance_nombre').innerText = alcanceNombre;
        document.getElementById('ret_porcentaje_input').value = parseFloat(porcentajeActual ?? 20).toFixed(2);
        document.getElementById('ret_aplicar_todos').checked = !vendedorId;

        actualizarPreviewRetencion();
        openModal('configurar-retencion-modal');
    }

    function actualizarPreviewRetencion() {
        const pctRetencion = parseFloat(document.getElementById('ret_porcentaje_input').value) || 0;
        const pctGanancia = Math.max(0, 100 - pctRetencion);
        const previewEl = document.getElementById('ret_preview_ganancia');
        if (previewEl) {
            previewEl.innerText = pctGanancia.toFixed(1) + '%';
        }
    }

    window.iniciarTourNomina = function() {
        if (!window.driver || !window.driver.js) return;
        const driverObj = window.driver.js.driver({
            showProgress: true,
            nextBtnText: 'Siguiente ➔',
            prevBtnText: '⬅ Anterior',
            doneBtnText: '¡Entendido!',
            steps: [
                {
                    element: '#tour-kpis-nomina',
                    popover: {
                        title: 'Resumen Financiero de Nómina',
                        description: 'Visualiza en tiempo real el Total Vendido por todos los vendedores, el 20% retenido por la empresa, el 80% de ganancia neta para vendedores, lo ya pagado y el saldo pendiente.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '#tour-btn-config-retencion',
                    popover: {
                        title: 'Configurar % de Retención',
                        description: 'Permite ajustar el porcentaje de retención estándar (20% por defecto) o asignar un porcentaje personalizado a un vendedor específico.',
                        side: 'bottom',
                        align: 'end'
                    }
                },
                {
                    element: '#tour-tabla-vendedores',
                    popover: {
                        title: 'Liquidación por Vendedor',
                        description: 'Consulta el saldo pendiente de cada vendedor y haz clic en "Pagar / Liquidar" para registrar su dispersión y enviarle automáticamente su comprobante por WhatsApp.',
                        side: 'top',
                        align: 'center'
                    }
                },
                {
                    element: '#tour-historial-pagos',
                    popover: {
                        title: 'Bitácora e Historial Auditable',
                        description: 'Todos los pagos registrados quedan respaldados con folio, fecha, desglose contable, método de pago y el administrador que autorizó la dispersión.',
                        side: 'top',
                        align: 'center'
                    }
                }
            ],
            onDestroyStarted: () => {
                driverObj.destroy();
                fetch('{{ route("tutorial.marcar-visto") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ tutorial_name: 'nomina_vendedores' })
                }).catch(() => {});
            }
        });
        driverObj.drive();
    };

    document.addEventListener('DOMContentLoaded', function() {
        const hasSeen = @json($hasSeenTutorialNomina);
        const forceTutorial = new URLSearchParams(window.location.search).get('tutorial') === 'true';

        if (!hasSeen || forceTutorial) {
            if (forceTutorial) {
                const url = new URL(window.location);
                url.searchParams.delete('tutorial');
                window.history.replaceState({}, '', url);
            }
            setTimeout(() => {
                window.iniciarTourNomina();
            }, 600);
        }
    });
</script>

