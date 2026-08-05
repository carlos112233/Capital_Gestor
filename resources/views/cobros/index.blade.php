<x-app-layout>
    <!-- Header de la Vista -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Historial y Gestión de Cobros y Pagos</h2>
            <p class="text-sm text-slate-500 font-medium">Supervisa los cobros de ventas y pedidos, entradas de capital y gestiona los ingresos del negocio.</p>
        </div>
        
        <div>
            <button onclick="openModal('modal-nuevo-cobro')" 
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Cobro Manual
            </button>
        </div>
    </div>

    @php
        $isAdminUser = Auth::user() && Auth::user()->hasRole('admin');
    @endphp

    <!-- Tarjetas de Métricas KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ $isAdminUser ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4 mb-6">
        
        @if($isAdminUser)
        <!-- KPI 1: Total Cobrado (Solo Admin) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase block mb-1">TOTAL COBRADO</span>
                <span class="text-2xl font-black text-slate-900 block">${{ number_format($totalCobrado, 2) }}</span>
                <span class="text-xs font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Ingresos confirmados
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        @endif

        <!-- KPI 2: Cobros de este Mes -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase block mb-1">COBROS DE ESTE MES</span>
                <span class="text-2xl font-black text-slate-900 block">${{ number_format($cobrosMes, 2) }}</span>
                <span class="text-xs font-medium text-slate-400 block mt-1">Mes actual</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        <!-- KPI 3: Cobros Pendientes -->
        <div class="bg-white rounded-2xl p-5 border border-amber-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold tracking-wider text-amber-500 uppercase block mb-1">COBROS PENDIENTES</span>
                <span class="text-2xl font-black text-amber-600 block">${{ number_format($cobrosPendientes, 2) }}</span>
                <span class="text-xs font-semibold text-amber-500 flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Por conciliar
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- KPI 4: Transacciones -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase block mb-1">TRANSACCIONES</span>
                <span class="text-2xl font-black text-slate-900 block">{{ $totalTransacciones }}</span>
                <span class="text-xs font-medium text-slate-400 block mt-1">Registros en total</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6">
        <form method="GET" action="{{ route('cobros.index') }}" class="flex flex-col md:flex-row gap-4 items-center justify-between">
            
            <!-- Campo de Búsqueda -->
            <div class="relative w-full md:w-2/3">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="q" value="{{ request('q') }}" 
                       placeholder="Buscar por usuario, correo, folio o concepto..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all bg-slate-50/50">
            </div>

            <!-- Filtro de Estado -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">ESTADO:</label>
                <select name="estado" onchange="this.form.submit()" 
                        class="w-full md:w-44 px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-slate-800 bg-white">
                    <option value="todos" {{ request('estado', 'todos') == 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="pagado" {{ request('estado') == 'pagado' ? 'selected' : '' }}>Pagado</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Tabla de Registros de Cobros y Pagos -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                        <th class="py-3.5 px-6">FECHA / REGISTRO</th>
                        <th class="py-3.5 px-6">USUARIO</th>
                        <th class="py-3.5 px-6">CONCEPTO / TIPO</th>
                        <th class="py-3.5 px-6 text-right">MONTO</th>
                        <th class="py-3.5 px-6 text-center">ESTADO</th>
                        <th class="py-3.5 px-6 text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($paginador as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            
                            <!-- Fecha / Folio -->
                            <td class="py-4 px-6 font-medium text-slate-700 whitespace-nowrap">
                                <div>{{ \Carbon\Carbon::parse($item['fecha'])->format('d M Y, h:i a') }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $item['id'] }}</div>
                            </td>

                            <!-- Usuario / Correo -->
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-800">{{ $item['usuario'] }}</div>
                                <div class="text-xs text-slate-400">{{ $item['email'] }}</div>
                            </td>

                            <!-- Concepto -->
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-800">{{ $item['concepto'] }}</div>
                                <div class="text-xs text-slate-400 font-medium">{{ $item['tipo'] }}</div>
                            </td>

                            <!-- Monto -->
                            <td class="py-4 px-6 text-right font-black text-slate-900 whitespace-nowrap">
                                ${{ number_format($item['monto'], 2) }}
                            </td>

                            <!-- Estado Badge -->
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                @if($item['estado'] === 'PAGADO')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> PAGADO
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-600 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> PENDIENTE
                                    </span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <button class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No se encontraron registros de cobros o pagos en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginador -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $paginador->links() }}
        </div>
    </div>

    <!-- Modal para Nuevo Cobro Manual -->
    <x-modal name="modal-nuevo-cobro" focusable>
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Registrar Nuevo Cobro Manual</h3>
            <form action="{{ route('ventas.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Cliente / Usuario</label>
                    <select name="cliente_id" class="w-full border-slate-300 rounded-xl p-2.5 text-sm" required>
                        @foreach(App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Artículo / Concepto</label>
                    <select name="articulo_id" class="w-full border-slate-300 rounded-xl p-2.5 text-sm" required>
                        @foreach(App\Models\Articulo::comerciales()->orderBy('nombre')->get() as $art)
                            <option value="{{ $art->id }}">{{ $art->nombre }} - ${{ number_format($art->precio, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Cantidad</label>
                        <input type="number" name="cantidad" value="1" min="1" class="w-full border-slate-300 rounded-xl p-2.5 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Monto ($)</label>
                        <input type="number" step="0.01" name="precio_venta" placeholder="Auto / Personalizado" class="w-full border-slate-300 rounded-xl p-2.5 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Notas / Descripción</label>
                    <textarea name="descripcion" rows="2" placeholder="Cobro manual registrado desde el panel de Cobros" class="w-full border-slate-300 rounded-xl p-2.5 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-4 border-t border-slate-100 pt-4">
                    <button type="button" onclick="closeModal('modal-nuevo-cobro')" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer">Cancelar</button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">Guardar Cobro</button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
