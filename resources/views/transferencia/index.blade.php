<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Datos de Transferencia') }}
            </h2>
    </x-slot>
    <div style="display:flex; justify-content:center;">
        <div class="py-12 w-full h-auto max-w-xl rounded-base ">
            <div class="bg-neutral-primary-soft block max-w-sm p-6 border border-default rounded-base shadow-xs">
                <h5 class="mb-3 text-2xl font-semibold tracking-tight text-heading leading-8 text-center">Cuentas para
                    transferencia </h5>
                <b>
                    <h6 class="text-body text-center mb-6 ">BBVA:</h6>
                </b>
                <p>Cuenta: <b>158 086 7512</b> <br>
                    Cuenta CLABE: <b>012 650 01580867512 5</b></p>
                <b>
                    <br>
                    <h6 class="text-body text-center mb-6 ">Mercado Pago:</h6>
                </b>
                <p>Cuenta CLABE: <b>722969010384935035</b></p>
            </div>
        </div>
    </div>
</x-app-layout>
