<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Resumen semanal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-12" style="padding:  0px 395px">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-4 flex gap-2">
                        <input type="text" id="search" placeholder="Buscar cliente..."
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200 px-4 py-2">
                       <button onclick="exportarExcel()" 
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900">
                            
                            <!-- Icono de Excel más visible -->
                            <svg class="w-6 h-6 mr-2" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2zM15 8V3.5L18.5 7H15zM8.227 18.273l1.82-2.613-1.701-2.45h1.678l.85 1.483.84-1.483h1.613l-1.695 2.45 1.82 2.613h-1.634l-1.02-1.618-1.027 1.618H8.227z"/>
                            </svg>

                            <span>Exportar</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border" id="tabla-resumen" >
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Saldo</th>
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
    const ws = XLSX.utils.table_to_sheet(tabla, { raw: false });

    // 2. Definir los estilos
    const estiloVerde = {
        font: { color: { rgb: "16A34A" }, bold: true }, // Verde de Tailwind (600)
        alignment: { horizontal: "right" }
    };
    const estiloRojo = {
        font: { color: { rgb: "DC2626" }, bold: true }, // Rojo de Tailwind (600)
        alignment: { horizontal: "right" }
    };
    const estiloBold = {
        font: { bold: true }
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
                cellData.s = { alignment: { horizontal: "right" } };
            }
        }
        
        // Estilo especial para la cabecera (primera fila)
        if (cell.replace(/[^0-9]/g, '') === "1") {
            cellData.s = { font: { bold: true }, fill: { fgColor: { rgb: "F3F4F6" } } };
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
</script>
