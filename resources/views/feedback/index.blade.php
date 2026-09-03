<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <span>💬</span> {{ __('Quejas, Comentarios y Sugerencias') }}
            </h2>
            <button id="tour-btn-nuevo-feedback" onclick="openFeedbackModal()" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition flex items-center gap-2">
                <span>📢</span>
                <span>Nuevo Comentario / Queja</span>
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Alertas -->
            @if (session('success'))
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r shadow-sm" role="alert">
                    <p class="font-bold">¡Éxito!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-sm" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r shadow-sm" role="alert">
                    <p class="font-bold">Por favor corrige los siguientes errores:</p>
                    <ul class="list-disc list-inside mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8" id="tour-feedback-stats">
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase">Total Registros</p>
                        <p class="text-2xl font-extrabold text-gray-800 mt-1">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 text-2xl">
                        📋
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border border-red-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-red-600 uppercase">🔴 Enviados (Sin Ver)</p>
                        <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $stats['enviado'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-600 text-2xl">
                        🔴
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border border-amber-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-amber-600 uppercase">🟠 Leyendo / En Revisión</p>
                        <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['leyendo'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 rounded-full flex items-center justify-center text-amber-600 text-2xl">
                        🟠
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border border-emerald-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-emerald-600 uppercase">🟢 Leídos / Resueltos</p>
                        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $stats['leido'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600 text-2xl">
                        🟢
                    </div>
                </div>
            </div>

            <!-- Barra de Búsqueda y Filtros -->
            <form method="GET" action="{{ route('feedback.index') }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-wrap gap-4 items-center justify-between" id="tour-feedback-filtros">
                <div class="flex flex-wrap gap-3 flex-1">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative w-full group">
    <input type="text" name="q" id="search_q" value="{{ request('q') }}" class="block rounded-t-lg px-3 pb-2 pt-6 w-full text-sm text-slate-800 bg-slate-100 border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer pr-10 transition-colors focus:bg-slate-200/50" placeholder=" " autocomplete="off" />
    <label for="search_q" class="absolute text-sm text-slate-500 duration-300 transform -translate-y-4 scale-75 top-4 z-10 origin-[0] start-3 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-4 cursor-text">
        Buscar por asunto, mensaje o usuario...
    </label>
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div>
                    </div>
                    <div>
                        <select name="estatus" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="todos" {{ request('estatus', 'todos') === 'todos' ? 'selected' : '' }}>Todos los estatus</option>
                            <option value="enviado" {{ request('estatus') === 'enviado' ? 'selected' : '' }}>🔴 Enviado</option>
                            <option value="leyendo" {{ request('estatus') === 'leyendo' ? 'selected' : '' }}>🟠 Leyendo / Revisando</option>
                            <option value="leido" {{ request('estatus') === 'leido' ? 'selected' : '' }}>🟢 Leído / Cerrado</option>
                        </select>
                    </div>
                    <div>
                        <select name="tipo" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="todos" {{ request('tipo', 'todos') === 'todos' ? 'selected' : '' }}>Todos los tipos</option>
                            <option value="queja" {{ request('tipo') === 'queja' ? 'selected' : '' }}>🚨 Quejas</option>
                            <option value="comentario" {{ request('tipo') === 'comentario' ? 'selected' : '' }}>💬 Comentarios</option>
                            <option value="sugerencia" {{ request('tipo') === 'sugerencia' ? 'selected' : '' }}>💡 Sugerencias</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        Filtrar
                    </button>
                    @if(request()->hasAny(['q', 'estatus', 'tipo']))
                        <a href="{{ route('feedback.index') }}" class="text-gray-500 hover:text-gray-700 text-sm ml-2 underline">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <!-- Lista de Feedbacks -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100" id="tour-feedback-lista">
                @if($feedbacks->isEmpty())
                    <div class="p-12 text-center">
                        <div class="text-5xl mb-4">📭</div>
                        <h3 class="text-lg font-medium text-gray-700">No hay quejas, comentarios o sugerencias</h3>
                        <p class="text-gray-500 text-sm mt-1">Cuando tú o un usuario envíe feedback, aparecerá en esta lista.</p>
                        <button onclick="openFeedbackModal()" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium inline-block transition">
                            📢 Enviar mi primer comentario
                        </button>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($feedbacks as $item)
                            <a href="{{ route('feedback.show', $item->id) }}" class="block hover:bg-gray-50 transition p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        <!-- Tipo Badge Icon -->
                                        @if($item->tipo === 'queja')
                                            <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-lg font-bold shrink-0" title="Queja">
                                                🚨
                                            </div>
                                        @elseif($item->tipo === 'sugerencia')
                                            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-lg font-bold shrink-0" title="Sugerencia">
                                                💡
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold shrink-0" title="Comentario">
                                                💬
                                            </div>
                                        @endif

                                        <!-- Contenido del Ticket -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-gray-900 text-base">
                                                    {{ $item->asunto ?: ucfirst($item->tipo) }}
                                                </span>
                                                <span class="text-xs px-2 py-0.5 rounded font-semibold uppercase tracking-wide 
                                                    @if($item->tipo === 'queja') bg-rose-100 text-rose-700 
                                                    @elseif($item->tipo === 'sugerencia') bg-purple-100 text-purple-700 
                                                    @else bg-blue-100 text-blue-700 @endif">
                                                    {{ $item->tipo }}
                                                </span>

                                                @if($item->imagen)
                                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded flex items-center gap-1">
                                                        <span>📷</span> Imagen adjunta
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="text-gray-600 text-sm mt-1 line-clamp-2">
                                                {{ $item->mensaje }}
                                            </p>

                                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                                <span>👤 {{ $item->user->name ?? 'Usuario' }}</span>
                                                <span>•</span>
                                                <span>🕒 {{ $item->created_at->diffForHumans() }}</span>
                                                @if($item->mensajes->count() > 0)
                                                    <span>•</span>
                                                    <span class="font-medium text-indigo-600">
                                                        💬 {{ $item->mensajes->count() }} respuesta(s)
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Badge con Color Dinámico -->
                                    <div class="flex flex-col items-end shrink-0">
                                        @if($item->estatus === 'enviado')
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-300 flex items-center gap-1.5 shadow-sm">
                                                <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                                                🔴 Enviado
                                            </span>
                                        @elseif($item->estatus === 'leyendo')
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1.5 shadow-sm">
                                                <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span>
                                                🟠 Leyendo / En revisión
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-300 flex items-center gap-1.5 shadow-sm">
                                                <span>✓</span>
                                                🟢 Leído / Resuelto
                                            </span>
                                        @endif

                                        <span class="text-xs text-indigo-600 font-medium mt-2 flex items-center gap-1 group-hover:translate-x-1 transition">
                                            Ver conversación &rarr;
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="p-4 border-t border-gray-100">
                        {{ $feedbacks->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- MODAL NUEVO FEEDBACK -->
    <div id="feedbackModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeFeedbackModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('feedback.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-6">
                        <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span>📢</span> Enviar Queja, Comentario o Sugerencia
                            </h3>
                            <button type="button" onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                        </div>

                        <!-- Selector de Tipo -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Selecciona el tipo de mensaje</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="block cursor-pointer group">
                                    <input type="radio" name="tipo" value="queja" class="sr-only peer" required>
                                    <div class="border-2 border-gray-200 rounded-xl p-3 text-center transition group-hover:border-rose-500 peer-checked:border-rose-500 peer-checked:bg-rose-50/50">
                                        <div class="text-xl mb-1">🚨</div>
                                        <div class="text-xs font-bold text-gray-700 peer-checked:text-rose-600">Queja</div>
                                    </div>
                                </label>
                                <label class="block cursor-pointer group">
                                    <input type="radio" name="tipo" value="comentario" class="sr-only peer" required checked>
                                    <div class="border-2 border-gray-200 rounded-xl p-3 text-center transition group-hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50/50">
                                        <div class="text-xl mb-1">💬</div>
                                        <div class="text-xs font-bold text-gray-700 peer-checked:text-blue-600">Comentario</div>
                                    </div>
                                </label>
                                <label class="block cursor-pointer group">
                                    <input type="radio" name="tipo" value="sugerencia" class="sr-only peer" required>
                                    <div class="border-2 border-gray-200 rounded-xl p-3 text-center transition group-hover:border-purple-500 peer-checked:border-purple-500 peer-checked:bg-purple-50/50">
                                        <div class="text-xl mb-1">💡</div>
                                        <div class="text-xs font-bold text-gray-700 peer-checked:text-purple-600">Sugerencia</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Asunto -->
                        <div class="mb-4">
                            <label for="asunto" class="block text-sm font-semibold text-gray-700 mb-1">Asunto / Título (Opcional)</label>
                            <input type="text" name="asunto" id="asunto" placeholder="Ej: Mejorar velocidad, error al comprar, felicidades..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Mensaje -->
                        <div class="mb-4">
                            <label for="mensaje" class="block text-sm font-semibold text-gray-700 mb-1">Tu Mensaje *</label>
                            <textarea name="mensaje" id="mensaje" rows="4" required placeholder="Describe tu sugerencia, comentario o el detalle que encontraste..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                        </div>

                        <!-- Adjuntar Imagen -->
                        <div class="mb-2">
                            <label for="imagen" class="block text-sm font-semibold text-gray-700 mb-1">Adjuntar Foto o Captura de pantalla (Opcional)</label>
                            <input type="file" name="imagen" id="imagen" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-200 rounded-lg">
                            <p class="text-xs text-gray-400 mt-1">Formatos: JPG, PNG, WEBP. Máximo 5 MB.</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" onclick="closeFeedbackModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition">
                            Enviar al Administrador 🚀
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('hidden');
        }
        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.add('hidden');
        }
    </script>

    @php
        $hasSeenTutorial = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'feedback')->exists();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const forceTutorial = new URLSearchParams(window.location.search).get('tutorial') === 'true';
            const hasSeenTutorial = @json($hasSeenTutorial);

            if (forceTutorial || !hasSeenTutorial) {
                const driverObj = window.driver.js.driver({
                    showProgress: true,
                    nextBtnText: 'Siguiente ➔',
                    prevBtnText: '⬅ Anterior',
                    doneBtnText: '¡Entendido!',
                    progressText: 'Paso @{{current}} de @{{total}}',
                    steps: [
                        {
                            element: '#tour-btn-nuevo-feedback',
                            popover: {
                                title: 'Cuéntanos qué piensas',
                                description: 'Si tienes alguna queja, sugerencia o comentario, presiona este botón para hacérnoslo saber.',
                                side: "bottom",
                                align: 'end'
                            }
                        },
                        {
                            element: '#tour-feedback-stats',
                            popover: {
                                title: 'Estado de tus Reportes',
                                description: 'Estos indicadores te muestran rápidamente si tus mensajes ya fueron leídos o están en revisión.',
                                side: "bottom",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-feedback-filtros',
                            popover: {
                                title: 'Filtra tus tickets',
                                description: 'Si has enviado muchos reportes, puedes filtrarlos por tipo (Queja, Sugerencia, etc.) o por estado.',
                                side: "bottom",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-feedback-lista',
                            popover: {
                                title: 'Tus Mensajes',
                                description: 'Haz clic en cualquier ticket de esta lista para ver nuestra respuesta y darle seguimiento.',
                                side: "top",
                                align: 'start'
                            }
                        }
                    ],
                    onDestroyStarted: () => {
                        if (!driverObj.hasNextStep() || confirm("¿Seguro que quieres saltar el tutorial?")) {
                            driverObj.destroy();
                            fetch('{{ route("tutorial.marcar-visto") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ tutorial_name: 'feedback' })
                            });
                        }
                    }
                });

                if (forceTutorial) {
                    const url = new URL(window.location);
                    url.searchParams.delete('tutorial');
                    window.history.replaceState({}, '', url);
                }

                driverObj.drive();
            }
        });
    </script>
</x-app-layout>
