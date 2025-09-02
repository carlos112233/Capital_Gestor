<x-app-layout>
     <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de clientes') }}
            </h2>
                    <a href="{{ route('admin.clientes.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900">
                {{ __('Nueva cliente') }}
            </a>
    </x-slot>

       <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Email</th>
                                {{-- <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Telefono</th> --}}
                                {{-- <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Direccion</th> --}}
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($clientes as $cliente)
                                <tr>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $cliente->id }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $cliente->name }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">{{ $cliente->email }}</td>
                                    {{-- <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $cliente->telefono }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $cliente->direccion }}</td> --}}
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{-- Botón Editar --}}
                                        <a href="{{ route('admin.clientes.edit', $cliente) }}"
                                        class="text-indigo-600 hover:text-indigo-900">
                                            Editar
                                        </a>
                                        {{-- Botón Eliminar --}}
                                        <form class="inline-block ml-4" action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST"
                                            onsubmit="return confirm('¿Eliminar esta cliente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-900">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-2 border text-center text-gray-500">
                                        No hay clientes registradas
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                 </div>
                 <div class="p-4">
                    {{ $clientes->links() }}
                </div>
            </div>
            </div>
        </div>
</x-app-layout>
        