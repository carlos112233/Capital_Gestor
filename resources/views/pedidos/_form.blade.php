@php
    if (old('pedidos')) {
        $items = old('pedidos');
    } elseif (isset($pedido) && isset($pedido->id)) {
        $items = [$pedido->toArray()];
    } else {
        $items = [[]];
    }
    $userSelected = $userSelected ?? ($pedido->user_id ?? Auth::id());
@endphp

<style>
    .ts-control {
        border-radius: 0.5rem !important;
        padding: 0.5rem !important;
        border: 1px solid #d1d5db !important;
    }
</style>

<div class="pedidos-container">
    @foreach ($items as $i => $p)
        <div class="pedido-item mb-4 border p-4 rounded-lg bg-white shadow-sm relative">
            <div class="flex justify-end mb-2">
                <button type="button"
                    class="btn-eliminar-item p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-full transition-colors duration-150 focus:outline-none cursor-pointer"
                    title="Eliminar este ítem">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if (Auth::user()->hasRole('admin'))
                <div class="mb-2">
                    <label class="block font-bold text-gray-700">Usuario</label>
                    <select name="pedidos[{{ $i }}][user_id]" class="user-select w-full border-gray-300 rounded-lg"
                        required>
                        <option value="">Seleccione un usuario</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old("pedidos.$i.user_id", $userSelected) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            {{-- Artículo --}}
            <div class="mb-2">
                <label class="block font-bold text-gray-700">Artículo</label>
                <select name="pedidos[{{ $i }}][articulo_id]"
                    class="articulo-select w-full border-gray-300 rounded-lg" required>
                    <option value="">Seleccione un artículo...</option>
                    @foreach ($articulos as $articulo)
                        @if ($articulo->nombre !== 'Pago saldado')
                            <option value="{{ $articulo->id }}"
                                {{ old("pedidos.$i.articulo_id", $p['articulo_id'] ?? '') == $articulo->id ? 'selected' : '' }}>
                                {{ $articulo->nombre }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Cantidad --}}
            <div class="mb-2">
                <label class="block font-bold text-gray-700">Cantidad</label>
                <input type="number" name="pedidos[{{ $i }}][cantidad]" min="1"
                    class="cantidad-input w-full border-gray-300 rounded-lg"
                    value="{{ old("pedidos.$i.cantidad", $p['cantidad'] ?? 1) }}" required>
            </div>

            {{-- Costo --}}
            <div class="mb-2">
                <label class="block font-bold text-gray-700">Costo unitario</label>
                @if (Auth::user()->hasRole('admin'))
                    <input type="number" name="pedidos[{{ $i }}][costo]"
                        class="costo-input w-full border-gray-300 rounded-lg"
                        value="{{ old("pedidos.$i.costo", $p['costo'] ?? '') }}" required>
                @else
                    <input type="number" name="pedidos[{{ $i }}][costo]"
                        class="costo-input w-full border-gray-300 rounded-lg bg-gray-50"
                        value="{{ old("pedidos.$i.costo", $p['costo'] ?? '') }}" readonly required>
                @endif
            </div>

            {{-- Descripción --}}
            <div class="mb-2">
                <label class="block font-bold text-gray-700">Descripción</label>
                <textarea name="pedidos[{{ $i }}][descripcion]" class="w-full border-gray-300 rounded-lg">{{ old("pedidos.$i.descripcion", $p['descripcion'] ?? '') }}</textarea>
            </div>

            @if (isset($p['id']))
                <input type="hidden" name="pedidos[{{ $i }}][id]" value="{{ $p['id'] }}">
            @endif
        </div>
    @endforeach
</div>

<div class="flex justify-end mb-4">
    <button type="button" title="Añadir ítem" class="btn-agregar-pedido inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-lg shadow-md shadow-indigo-500/25 hover:shadow-lg transition-all duration-200 cursor-pointer">+</button>
</div>

<div class="flex justify-end gap-3 mt-4 border-t border-slate-100 pt-4">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-pedido'); $dispatch('close-modal', 'edit-pedido-{{ $pedido->id ?? 0 }}')"
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer">Cancelar</button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">Guardar</button>
</div>

<script>
(function() {
    const precios = {
        @foreach ($articulos as $articulo)
            "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }},
        @endforeach
    };

    const currentScript = document.currentScript;
    const form = currentScript ? currentScript.closest('form') : null;
    if (!form) return;

    const container = form.querySelector('.pedidos-container') || form.querySelector('#pedidos-container');
    const btnAgregar = form.querySelector('.btn-agregar-pedido') || form.querySelector('#agregar-pedido');

    function actualizarCosto(select) {
        const block = select.closest('.pedido-item');
        if (!block) return;
        const costo = block.querySelector('.costo-input');
        if (costo) costo.value = precios[select.value] ?? '';
    }

    function activarBuscador(el) {
        if (!el || el.tomselect || typeof TomSelect === 'undefined') return;
        new TomSelect(el, {
            create: false,
            placeholder: "Buscar artículo...",
            onChange: function() {
                actualizarCosto(el);
            }
        });
    }

    function activarBuscadorUser(el) {
        if (!el || typeof TomSelect === 'undefined') return;
        if (el.tomselect) {
            el.tomselect.destroy();
        }
        new TomSelect(el, {
            create: false,
            placeholder: "Buscar usuario...",
            maxOptions: null
        });
    }

    let moldeLimpio = null;
    let index = form.querySelectorAll('.pedido-item').length || 1;

    function initForm() {
        const original = form.querySelector('.pedido-item');
        if (original && !moldeLimpio) {
            moldeLimpio = original.cloneNode(true);
            moldeLimpio.querySelectorAll('.ts-wrapper').forEach(ts => ts.remove());
            moldeLimpio.querySelectorAll('.tomselected').forEach(ts => ts.classList.remove('tomselected'));
            moldeLimpio.querySelectorAll('select').forEach(s => {
                s.style.display = '';
                if (s.tomselect) delete s.tomselect;
            });
            moldeLimpio.querySelectorAll('input, textarea').forEach(el => el.value = '');
            const selectMolde = moldeLimpio.querySelector('.articulo-select');
            if (selectMolde) selectMolde.value = "";
        }

        form.querySelectorAll('.articulo-select').forEach(select => {
            activarBuscador(select);
            actualizarCosto(select);
        });

        form.querySelectorAll('.user-select').forEach(select => {
            activarBuscadorUser(select);
        });
    }

    initForm();

    // Eliminar ítem individual del arreglo
    form.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-eliminar-item');
        if (btn) {
            const item = btn.closest('.pedido-item');
            const items = form.querySelectorAll('.pedido-item');
            if (items.length > 1) {
                if (item) item.remove();
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'El pedido debe contener al menos un ítem.',
                    timer: 2500,
                    showConfirmButton: false
                });
            } else {
                alert('El pedido debe contener al menos un ítem.');
            }
        }
    });

    if (btnAgregar) {
        btnAgregar.addEventListener('click', async () => {
            if (!container) return;
            if (!moldeLimpio) {
                const firstItem = form.querySelector('.pedido-item');
                if (firstItem) {
                    moldeLimpio = firstItem.cloneNode(true);
                    moldeLimpio.querySelectorAll('.ts-wrapper').forEach(ts => ts.remove());
                    moldeLimpio.querySelectorAll('.tomselected').forEach(ts => ts.classList.remove('tomselected'));
                    moldeLimpio.querySelectorAll('select').forEach(s => {
                        s.style.display = '';
                        if (s.tomselect) delete s.tomselect;
                    });
                    moldeLimpio.querySelectorAll('input, textarea').forEach(el => el.value = '');
                    const selectMolde = moldeLimpio.querySelector('.articulo-select');
                    if (selectMolde) selectMolde.value = "";
                    const selectUserMolde = moldeLimpio.querySelector('.user-select');
                    if (selectUserMolde) selectUserMolde.value = "";
                }
            }
            if (!moldeLimpio) return;

            const clone = moldeLimpio.cloneNode(true);
            // Limpiar residuos de TomSelect en el clone
            clone.querySelectorAll('.ts-wrapper').forEach(ts => ts.remove());
            clone.querySelectorAll('.tomselected').forEach(ts => ts.classList.remove('tomselected'));
            clone.querySelectorAll('select').forEach(s => {
                s.style.display = '';
                if (s.tomselect) delete s.tomselect;
            });

            clone.querySelectorAll('input, textarea, select').forEach(el => {
                if (el.name) {
                    el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
                }
                if (el.type === 'number') el.value = el.name.includes('cantidad') ? 1 : '';
                if (el.id) el.id = "";
            });
            const hiddenId = clone.querySelector('input[type="hidden"][name*="[id]"]');
            if (hiddenId) hiddenId.remove();
            
            const nuevoUserSelect = clone.querySelector('.user-select');
            if (nuevoUserSelect) {
                try {
                    const response = await fetch("{{ route('admin.api.clientes') }}", {
                        headers: { "X-Requested-With": "XMLHttpRequest" }
                    });
                    if (response.ok) {
                        const clientes = await response.json();
                        let optionsHtml = '<option value="">Seleccione un usuario</option>';
                        clientes.forEach(c => {
                            optionsHtml += `<option value="${c.id}">${c.name}</option>`;
                        });
                        nuevoUserSelect.innerHTML = optionsHtml;
                    }
                } catch(e) {
                    console.error('Error al actualizar usuarios en pedidos:', e);
                }
            }

            container.appendChild(clone);
            const nuevoSelect = clone.querySelector('.articulo-select');
            activarBuscador(nuevoSelect);
            
            if (nuevoUserSelect) activarBuscadorUser(nuevoUserSelect);
            
            index++;
        });
    }
})();
</script>
