{{-- resources/views/admin/ventas/edit.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar cliente #') . $cliente->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST"
                        action="{{ route('admin.clientes.update', $cliente) }}"enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('admin.clientes._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
