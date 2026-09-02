<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Resumen semanal') }}
        </h2>
    </x-slot>   

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-12" style="padding:  0px 35px">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-5 flex flex-col sm:flex-row items-center gap-3">
                        <div class="relative flex-1 w-full">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="search"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200 shadow-sm"
                                placeholder="Buscar cliente por nombre o correo..." autocomplete="off" />
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button type="button" onclick="exportarExcel()" title="Exportar Resumen a Excel"
                                class="inline-flex items-center justify-center p-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-sm shadow-emerald-500/20 transition-all duration-200 hover:scale-105 focus:outline-none cursor-pointer">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </button>
                            <button type="button" id="btn-envio-masivo" title="Enviar WhatsApp Masivo a Clientes Seleccionados"
                                class="inline-flex items-center justify-center p-2.5 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white shadow-sm shadow-green-500/20 transition-all duration-200 hover:scale-105 focus:outline-none cursor-pointer">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border" id="tabla-resumen">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Corte Anterior</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Corte Actual</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Saldo Total</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        WhatsApp</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ajuste temp.</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Pagar
                                    </th>
                                </tr>

                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($resumen as $r)
                                    @php
                                        $entrada[] = $r->saldo;

                                    @endphp
                                    @if ($r->saldo > 0)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div x-data="{ openTablePhoto: false }">
                                                        <button @click="openTablePhoto = true" class="focus:outline-none cursor-pointer transform hover:scale-110 transition-transform">
                                                            <img src="{{ route('user.image', $r->id) }}?v={{ $r->updated_at ? $r->updated_at->timestamp : '' }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm" alt="Foto">
                                                        </button>
                                                        <!-- Modal Foto de Perfil (Tabla) -->
                                                        <div x-show="openTablePhoto" style="display: none;" x-transition class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
                                                            <div @click.away="openTablePhoto = false" class="relative w-full max-w-3xl flex justify-center items-center">
                                                                <button @click="openTablePhoto = false" class="absolute -top-12 right-0 md:-right-8 md:-top-8 text-white hover:text-slate-300 focus:outline-none bg-white/10 p-2 rounded-full backdrop-blur-md">
                                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                </button>
                                                                <img src="{{ route('user.image', $r->id) }}?v={{ $r->updated_at ? $r->updated_at->timestamp : '' }}" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="font-medium text-slate-800">{{ $r->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                @if($r->saldo_corte_anterior > 0)
                                                    <span class="text-red-600 font-bold">${{ number_format($r->saldo_corte_anterior, 2) }}</span>
                                                @else
                                                    <span class="text-gray-400">$0.00</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <span class="text-gray-700">${{ number_format($r->saldo_corte_actual, 2) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <span
                                                    class="text-green-600 font-bold">${{ number_format($r->saldo, 2) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @php  $urlWa = '#'; @endphp
                                                @if ($r->telefono)
                                                    @php
                                                      
                                                        // 1. Quitamos espacios, guiones o paréntesis que pueda tener el número
                                                        $soloNumeros = preg_replace('/[^0-9]/', '', $r->telefono);

                                                        // 2. Lógica para México (Lada 52):
                                                        // Si el número tiene 10 dígitos, le pegamos el 52 al principio.
                                                        // Si ya tiene 12 dígitos y empieza con 52, lo dejamos así.
                                                        if (strlen($soloNumeros) == 10) {
                                                            $telefonoFinal = '52' . $soloNumeros;
                                                        } else {
                                                            $telefonoFinal = $soloNumeros;
                                                        }

                                                        // 3. Creamos el mensaje (puedes editarlo a tu gusto)
                                                        $mensaje =
                                                            'Hola ' .
                                                            $r->name .
                                                            ", solo para informarte que tu saldo actual a cubrir es de $" .
                                                            number_format($r->saldo, 2) .
                                                            "\n si deseas más informacion el cobro de tu saldo, mandanos un mensaje.\n" .
                                                            "--------------------------\n" .
                                                            "*DATOS PARA PAGO:*\n\n" .
                                                            "*BBVA:*\n" .
                                                            "Cuenta: *158 086 7512*\n" .
                                                            "CLABE: *012 650 01580867512 5*\n\n" .
                                                            "*Mercado Pago:*\n" .
                                                            "CLABE: *722969010384935035*\n\n" .
                                                            "--------------------------\n" .
                                                            'Favor de enviar el comprobante a este número.';

                                                        // 4. Codificamos el mensaje para URL
                                                        $urlWa =
                                                            'https://wa.me/' .
                                                            $telefonoFinal .
                                                            '?text=' .
                                                            urlencode($mensaje);
                                                    @endphp

                                                    <a href="{{ $urlWa }}" target="_blank"
                                                        class="inline-flex items-center justify-center w-10 h-10 bg-green-500 hover:bg-green-600 text-white rounded-full transition-colors shadow-md"
                                                        title="Enviar WhatsApp">
                                                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                                                            width="30" height="30" viewBox="0 0 48 48">
                                                            <path fill="#fff"
                                                                d="M4.9,43.3l2.7-9.8C5.9,30.6,5,27.3,5,24C5,13.5,13.5,5,24,5c5.1,0,9.8,2,13.4,5.6	C41,14.2,43,18.9,43,24c0,10.5-8.5,19-19,19c0,0,0,0,0,0h0c-3.2,0-6.3-0.8-9.1-2.3L4.9,43.3z">
                                                            </path>
                                                            <path fill="#fff"
                                                                d="M4.9,43.8c-0.1,0-0.3-0.1-0.4-0.1c-0.1-0.1-0.2-0.3-0.1-0.5L7,33.5c-1.6-2.9-2.5-6.2-2.5-9.6	C4.5,13.2,13.3,4.5,24,4.5c5.2,0,10.1,2,13.8,5.7c3.7,3.7,5.7,8.6,5.7,13.8c0,10.7-8.7,19.5-19.5,19.5c-3.2,0-6.3-0.8-9.1-2.3	L5,43.8C5,43.8,4.9,43.8,4.9,43.8z">
                                                            </path>
                                                            <path fill="#cfd8dc"
                                                                d="M24,5c5.1,0,9.8,2,13.4,5.6C41,14.2,43,18.9,43,24c0,10.5-8.5,19-19,19h0c-3.2,0-6.3-0.8-9.1-2.3	L4.9,43.3l2.7-9.8C5.9,30.6,5,27.3,5,24C5,13.5,13.5,5,24,5 M24,43L24,43L24,43 M24,43L24,43L24,43 M24,4L24,4C13,4,4,13,4,24	c0,3.4,0.8,6.7,2.5,9.6L3.9,43c-0.1,0.3,0,0.7,0.3,1c0.2,0.2,0.4,0.3,0.7,0.3c0.1,0,0.2,0,0.3,0l9.7-2.5c2.8,1.5,6,2.2,9.2,2.2	c11,0,20-9,20-20c0-5.3-2.1-10.4-5.8-14.1C34.4,6.1,29.4,4,24,4L24,4z">
                                                            </path>
                                                            <path fill="#40c351"
                                                                d="M35.2,12.8c-3-3-6.9-4.6-11.2-4.6C15.3,8.2,8.2,15.3,8.2,24c0,3,0.8,5.9,2.4,8.4L11,33l-1.6,5.8	l6-1.6l0.6,0.3c2.4,1.4,5.2,2.2,8,2.2h0c8.7,0,15.8-7.1,15.8-15.8C39.8,19.8,38.2,15.8,35.2,12.8z">
                                                            </path>
                                                            <path fill="#fff" fill-rule="evenodd"
                                                                d="M19.3,16c-0.4-0.8-0.7-0.8-1.1-0.8c-0.3,0-0.6,0-0.9,0	s-0.8,0.1-1.3,0.6c-0.4,0.5-1.7,1.6-1.7,4s1.7,4.6,1.9,4.9s3.3,5.3,8.1,7.2c4,1.6,4.8,1.3,5.7,1.2c0.9-0.1,2.8-1.1,3.2-2.3	c0.4-1.1,0.4-2.1,0.3-2.3c-0.1-0.2-0.4-0.3-0.9-0.6s-2.8-1.4-3.2-1.5c-0.4-0.2-0.8-0.2-1.1,0.2c-0.3,0.5-1.2,1.5-1.5,1.9	c-0.3,0.3-0.6,0.4-1,0.1c-0.5-0.2-2-0.7-3.8-2.4c-1.4-1.3-2.4-2.8-2.6-3.3c-0.3-0.5,0-0.7,0.2-1c0.2-0.2,0.5-0.6,0.7-0.8	c0.2-0.3,0.3-0.5,0.5-0.8c0.2-0.3,0.1-0.6,0-0.8C20.6,19.3,19.7,17,19.3,16z"
                                                                clip-rule="evenodd"></path>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span class="text-gray-400 text-xs italic">Sin cel.</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex items-center justify-center">
                                                    <span class="text-gray-500 mr-1">$</span>
                                                    <input type="number"
                                                        class="input-ajuste w-20 h-8 text-sm border-gray-300 rounded-md focus:ring-indigo-500"
                                                        placeholder="0.00" step="0.01" data-id="{{ $r->id }}">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"> <input type="checkbox"
                                                    class="cliente-checkbox  rounded border-gray-300 text-indigo-600 shadow-sm"
                                                    data-id="{{ $r->id }}" data-url="{{ $urlWa }}"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <button type="button"
                                                    onclick="openPagoSaldadoModal({{ $r->id }}, '{{ addslashes($r->name) }}', {{ $r->saldo }})"
                                                    class="btn-whatsapp text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                                    Pago saldado
                                                </button>
                                            </td>
                                        </tr>
                                    @elseif($r->saldo < 0)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div x-data="{ openTablePhoto: false }">
                                                        <button @click="openTablePhoto = true" class="focus:outline-none cursor-pointer transform hover:scale-110 transition-transform">
                                                            <img src="{{ route('user.image', $r->id) }}?v={{ $r->updated_at ? $r->updated_at->timestamp : '' }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm" alt="Foto">
                                                        </button>
                                                        <!-- Modal Foto de Perfil (Tabla) -->
                                                        <div x-show="openTablePhoto" style="display: none;" x-transition class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
                                                            <div @click.away="openTablePhoto = false" class="relative w-full max-w-3xl flex justify-center items-center">
                                                                <button @click="openTablePhoto = false" class="absolute -top-12 right-0 md:-right-8 md:-top-8 text-white hover:text-slate-300 focus:outline-none bg-white/10 p-2 rounded-full backdrop-blur-md">
                                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                </button>
                                                                <img src="{{ route('user.image', $r->id) }}?v={{ $r->updated_at ? $r->updated_at->timestamp : '' }}" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="font-medium text-slate-800">{{ $r->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-400">$0.00</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-400">$0.00</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <span
                                                    class="text-red-600 font-bold">${{ number_format($r->saldo, 2) }}</span>
                                            </td>
                                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">

                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <button type="button"
                                                    onclick="openPagoSaldadoModal({{ $r->id }}, '{{ addslashes($r->name) }}', {{ $r->saldo }})"
                                                    class="btn-whatsapp text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                                    Pago saldado
                                                </button>
                                            </td>
                                        </tr>
                                    @endif

                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                            No hay datos registrados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            {{-- Fila de sumatoria --}}
                            <tfoot class="bg-gray-100 font-bold">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right">Sumatoria a favor:</td>
                                    <td class="px-6 py-4 text-right text-green-600">
                                        ${{ number_format($totalSaldo, 2) }}
                                    </td>
                                    <td colspan="5" class="px-6 py-4 text-right"></td>
                                </tr>
                            </tfoot>
                        </table>

                    </div>
                </div>
            </div>

            @if(isset($comprobantesPendientes) && count($comprobantesPendientes) > 0)
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg border border-amber-200">
                <div class="p-6 text-gray-900 bg-amber-50/50">
                    <h3 class="text-lg font-bold text-amber-800 flex items-center gap-2 mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Comprobantes Pendientes de Aprobación ({{ count($comprobantesPendientes) }})
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-amber-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-amber-700 uppercase tracking-wider">Notas</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-amber-700 uppercase tracking-wider">Comprobante</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-amber-700 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-200">
                                @foreach($comprobantesPendientes as $comp)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold">{{ $comp->user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $comp->created_at->format('d/m/Y h:i A') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-700">${{ number_format($comp->monto, 2) }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $comp->notas ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <a href="{{ Storage::url($comp->imagen) }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 hover:underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Ver Imagen
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <form action="{{ route('admin.comprobantes.aprobar', $comp->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" onclick="return confirm('¿Aprobar comprobante y registrar como entrada de capital?')" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-xs font-bold transition-colors">
                                                    Aprobar
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.comprobantes.rechazar', $comp->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" onclick="return confirm('¿Rechazar comprobante?')" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-bold transition-colors">
                                                    Rechazar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Modal para Registrar Pago Saldado en DashboardAdmin --}}
    <x-modal name="pago-saldado-modal">
        <div class="p-6 text-left">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Pago Saldado</h3>
                <button type="button" onclick="closeModal('pago-saldado-modal')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.entradas.store') }}">
                @csrf
                <input type="hidden" name="tipo_pago" value="2">

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Cliente</label>
                    <select name="cliente_id" id="modal_pago_cliente_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                        @foreach($users as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Artículo</label>
                    <select name="articulo_id" id="modal_pago_articulo_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                        @foreach($articulos as $art)
                            @php $esPagoSaldado = strtolower($art->nombre) === 'pago saldado'; @endphp
                            <option value="{{ $art->id }}" {{ $esPagoSaldado ? 'selected' : '' }}>
                                {{ $art->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Monto / Precio de Venta ($)</label>
                    <input type="number" step="0.01" name="precio_venta" id="modal_pago_precio_venta" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Descripción</label>
                    <textarea name="descripcion" id="modal_pago_descripcion" class="block w-full border-gray-300 rounded-md shadow-sm">Saldar adeudo pendiente</textarea>
                </div>

                <div class="mb-4 flex items-center">
                    <input type="hidden" name="enviar_wa" value="0">
                    <input type="checkbox" name="enviar_wa" id="modal_pago_enviar_wa" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                    <label for="modal_pago_enviar_wa" class="ml-2 block text-sm text-gray-700 font-medium cursor-pointer">
                        Enviar notificación de pago por WhatsApp al cliente
                    </label>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t pt-4">
                    <button type="button" onclick="closeModal('pago-saldado-modal')" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md cursor-pointer">
                        Guardar Pago
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>

<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
<script>
    function openPagoSaldadoModal(clienteId, clienteName, saldo) {
        const clienteSelect = document.getElementById('modal_pago_cliente_id');
        const precioInput = document.getElementById('modal_pago_precio_venta');
        if (clienteSelect) clienteSelect.value = clienteId;
        if (precioInput) precioInput.value = parseFloat(saldo).toFixed(2);
        openModal('pago-saldado-modal');
    }
    function exportarExcel() {
        const tabla = document.getElementById('tabla-resumen');
        if (!tabla) return;

        const clone = tabla.cloneNode(true);
        clone.querySelectorAll('tr').forEach(row => {
            if (row.style.display === 'none') {
                row.remove();
                return;
            }
            const cells = row.children;
            if (cells.length >= 7) {
                cells[6].remove(); // Checkbox
                cells[5].remove(); // Input Ajuste
                cells[4].remove(); // Botón WhatsApp
            }
        });

        const ws = XLSX.utils.table_to_sheet(clone, { raw: false });

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Resumen_Saldos");
        const fecha = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(wb, `Resumen_Saldos_${fecha}.xlsx`);
    }

    // --- 2. LÓGICA UNIFICADA: BUSCADOR Y WHATSAPP MASIVO ---
    document.addEventListener('DOMContentLoaded', function() {
        const inputBusqueda = document.getElementById('search');
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.cliente-checkbox');
        const btnMasivo = document.getElementById('btn-envio-masivo');
        const countSpan = document.getElementById('count-selected');
        const tableBody = document.querySelector('#tabla-resumen tbody');

        // A. Buscador en tiempo real corregido
        inputBusqueda.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                // Buscamos en la PRIMERA columna (Cliente)
                const cellCliente = row.querySelector('td:first-child'); 
                if (cellCliente) {
                    const text = cellCliente.textContent.toLowerCase();
                    const visible = text.includes(filter);
                    row.style.display = visible ? '' : 'none';
                    
                    // Si ocultamos la fila, desmarcamos su checkbox por seguridad
                    if (!visible) {
                        const cb = row.querySelector('.cliente-checkbox');
                        if (cb) cb.checked = false;
                    }
                }
            });
            actualizarContador();
        });

        // B. Seleccionar Todos (solo los visibles)
        selectAll.addEventListener('change', function() {
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cb = row.querySelector('.cliente-checkbox');
                    if (cb) cb.checked = selectAll.checked;
                }
            });
            actualizarContador();
        });

        // C. Actualizar contador
        function actualizarContador() {
            const seleccionados = document.querySelectorAll('.cliente-checkbox:checked').length;
            if(countSpan) countSpan.innerText = seleccionados;
            btnMasivo.disabled = (seleccionados === 0);
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', actualizarContador);
        });

        // D. Envío Masivo
        btnMasivo.addEventListener('click', function() {
            const seleccionados = [];
            const ajustes = {};

            document.querySelectorAll('.cliente-checkbox:checked').forEach(cb => {
                const id = cb.getAttribute('data-id');
                seleccionados.push(id);

                const inputAjuste = cb.closest('tr').querySelector('.input-ajuste');
                if (inputAjuste && inputAjuste.value !== "") {
                    ajustes[id] = parseFloat(inputAjuste.value);
                }
            });

            if (seleccionados.length === 0) {
                return Swal.fire({
                    icon: 'info',
                    title: 'Selección requerida',
                    text: 'Por favor, selecciona al menos un cliente para enviar el recordatorio.',
                    confirmButtonColor: '#4f46e5'
                });
            }

            Swal.fire({
                title: '¿Enviar recordatorios por WhatsApp?',
                text: `Se enviarán mensajes masivos a ${seleccionados.length} cliente(s) seleccionado(s).`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, enviar ahora',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    btnMasivo.disabled = true;
                    btnMasivo.style.opacity = '0.6';

                    fetch("{{ route('admin.enviar.masivo') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            user_ids: seleccionados,
                            ajustes: ajustes
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Proceso Finalizado!',
                            text: data.message || 'Los mensajes se han encolado y enviado correctamente.',
                            confirmButtonColor: '#10b981'
                        });
                        btnMasivo.disabled = false;
                        btnMasivo.style.opacity = '1';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Envío',
                            text: 'Ocurrió un problema al enviar los mensajes por WhatsApp.',
                            confirmButtonColor: '#ef4444'
                        });
                        btnMasivo.disabled = false;
                        btnMasivo.style.opacity = '1';
                    });
                }
            });
        });
    });
</script>