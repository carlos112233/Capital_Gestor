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

                    <div class="mb-4 flex gap-2">
                        <input type="text" id="search" placeholder="Buscar cliente..."
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200 px-4 py-2">
                        <button onclick="exportarExcel()"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900">

                            <!-- Icono de Excel más visible -->
                            <svg class="w-6 h-6 mr-2" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2zM15 8V3.5L18.5 7H15zM8.227 18.273l1.82-2.613-1.701-2.45h1.678l.85 1.483.84-1.483h1.613l-1.695 2.45 1.82 2.613h-1.634l-1.02-1.618-1.027 1.618H8.227z" />
                            </svg>

                            <span>Exportar</span>
                        </button>
                        <button id="btn-envio-masivo"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900">
                            WhatsApp Masivo
                            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="30" height="30"
                                viewBox="0 0 48 48">
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
                        </button>
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
                                        Saldo</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        WhatsApp</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    </th>
                                </tr>

                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($resumen as $r)
                                    @if ($r->saldo > 0)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $r->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <span
                                                    class="text-green-600 font-bold">${{ number_format($r->saldo, 2) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if ($r->telefono)
                                                    @php
                                                        $urlWa = '#';
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
                                            <td class="px-6 py-4 whitespace-nowrap text-center"> <input type="checkbox"
                                                    class="cliente-checkbox  rounded border-gray-300 text-indigo-600 shadow-sm"
                                                    data-id="{{ $r->id }}" 
                                                    data-url="{{ $urlWa }}"></td>   
                                        </tr>
                                    @elseif($r->saldo < 0)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $r->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <span
                                                    class="text-red-600 font-bold">${{ number_format($r->saldo, 2) }}</span>
                                            </td>

                                        </tr>
                                    @endif

                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            No hay datos registrados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            {{-- Fila de sumatoria --}}
                            <tfoot class="bg-gray-100 font-bold">
                                <tr>
                                    <td colspan="1" class="px-6 py-4 text-right">Sumatoria a favor:</td>
                                    <td class="px-6 py-4 text-right text-green-600">
                                        ${{ number_format($totalSaldo, 2) }}
                                    </td>
                                    <td colspan="2" class="px-6 py-4 text-right"></td>
                                </tr>
                            </tfoot>
                        </table>

                    </div>
                </div>
            </div>
        </div>
</x-app-layout>

<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>

<script>
    function exportarExcel() {
        const tabla = document.getElementById('tabla-resumen');
        if (!tabla) return;

        // 1. Convertir la tabla a una "Hoja de Trabajo" (Worksheet)
        // Usamos { raw: false } para que respete el formato de texto de los números
        const ws = XLSX.utils.table_to_sheet(tabla, {
            raw: false
        });

        // 2. Definir los estilos
        const estiloVerde = {
            font: {
                color: {
                    rgb: "16A34A"
                },
                bold: true
            }, // Verde de Tailwind (600)
            alignment: {
                horizontal: "right"
            }
        };
        const estiloRojo = {
            font: {
                color: {
                    rgb: "DC2626"
                },
                bold: true
            }, // Rojo de Tailwind (600)
            alignment: {
                horizontal: "right"
            }
        };
        const estiloBold = {
            font: {
                bold: true
            }
        };

        // 3. Recorrer todas las celdas de la hoja
        for (let cell in ws) {
            // Ignorar propiedades que no son celdas (como !ref o !cols)
            if (cell[0] === '!') continue;

            const cellData = ws[cell];
            const valorStr = cellData.v.toString();

            // Si la celda contiene un símbolo de moneda "$" o es un número
            if (valorStr.includes('$') || !isNaN(parseFloat(valorStr))) {

                // Limpiamos el texto para saber si es negativo o positivo
                const valorNumerico = parseFloat(valorStr.replace(/[$,]/g, ''));

                if (valorNumerico > 0) {
                    cellData.s = estiloVerde;
                } else if (valorNumerico < 0) {
                    cellData.s = estiloRojo;
                } else {
                    cellData.s = {
                        alignment: {
                            horizontal: "right"
                        }
                    };
                }
            }

            // Estilo especial para la cabecera (primera fila)
            if (cell.replace(/[^0-9]/g, '') === "1") {
                cellData.s = {
                    font: {
                        bold: true
                    },
                    fill: {
                        fgColor: {
                            rgb: "F3F4F6"
                        }
                    }
                };
            }
        }

        // 4. Crear el libro y descargar
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Resumen");
        XLSX.writeFile(wb, "Resumen_quinsenal.xlsx");
    }
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('search');
        const table = document.querySelector('table tbody');

        input.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tr');

            rows.forEach(row => {
                const cell = row.querySelector('td:first-child'); // columna Cliente
                if (!cell) return;

                const text = cell.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });


    // --- 2. LÓGICA DE BUSCADOR, SELECCIÓN Y ENVÍO MASIVO ---
    document.addEventListener('DOMContentLoaded', function() {
        const inputBusqueda = document.getElementById('search');
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.cliente-checkbox');
        const btnMasivo = document.getElementById('btn-envio-masivo');
        const countSpan = document.getElementById('count-selected');
        const tableBody = document.querySelector('table tbody');

        // A. Buscador en tiempo real
        inputBusqueda.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const cellCliente = row.querySelector('td:nth-child(2)');
                if (cellCliente) {
                    const text = cellCliente.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                    // Desmarcar si se oculta
                    if (row.style.display === 'none') {
                        const cb = row.querySelector('.cliente-checkbox');
                        if (cb) cb.checked = false;
                    }
                }
            });
            actualizarContador();
        });

        // B. Seleccionar Todos
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                // Solo seleccionar los que están visibles en el filtro
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
            actualizarContador();
        });

        // C. Actualizar contador al marcar individualmente
        checkboxes.forEach(cb => {
            cb.addEventListener('change', actualizarContador);
        });

        function actualizarContador() {
            const seleccionados = document.querySelectorAll('.cliente-checkbox:checked').length;
            countSpan.innerText = seleccionados;
            btnMasivo.disabled = (seleccionados === 0);
        }

        // D. PROCESO DE ENVÍO MASIVO (CON RETRASO)
        btnMasivo.addEventListener('click', function() {
            const seleccionados = Array.from(document.querySelectorAll('.cliente-checkbox:checked'))
                .map(cb => cb.getAttribute(
                'data-id')); // Asegúrate de añadir data-id="{{ $r->id }}" al checkbox

            if (seleccionados.length === 0) return alert('Selecciona clientes');

            if (confirm(`¿Enviar ${seleccionados.length} recordatorios automáticos?`)) {
                btnMasivo.disabled = true;
                btnMasivo.innerText = 'Encolando mensajes...';

                fetch("{{ route('admin.enviar.masivo') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            user_ids: seleccionados
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        btnMasivo.innerText = 'Cobrar Seleccionados';
                        btnMasivo.disabled = false;
                    });
            }
        });
    });
</script>
