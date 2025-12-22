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
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
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
<script>
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
