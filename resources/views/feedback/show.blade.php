<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('feedback.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm flex items-center gap-1 transition">
                    &larr; Volver a la lista
                </a>
                <span class="text-gray-300">|</span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>💬</span> Conversación #{{ $feedback->id }} - {{ $feedback->asunto ?: ucfirst($feedback->tipo) }}
                </h2>
            </div>

            <!-- Estatus Badge -->
            <div>
                @if($feedback->estatus === 'enviado')
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-300 flex items-center gap-1.5 shadow-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                        🔴 Enviado (Sin ver por Admin)
                    </span>
                @elseif($feedback->estatus === 'leyendo')
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1.5 shadow-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-600 animate-pulse"></span>
                        🟠 Leyendo / En Revisión
                    </span>
                @else
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-300 flex items-center gap-1.5 shadow-sm">
                        <span>✓</span>
                        🟢 Leído / Resuelto / Cerrado
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <!-- Alertas -->
            @if (session('success'))
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r shadow-sm" role="alert">
                    <p class="font-bold">¡Éxito!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <!-- BANNER INFORMATIVO DE ESTATUS (Para que el usuario sepa claramente qué pasó con su sugerencia/queja) -->
            <div class="mb-6 rounded-xl p-4 border shadow-sm flex items-center justify-between
                @if($feedback->estatus === 'enviado') bg-red-50 border-red-200 text-red-800
                @elseif($feedback->estatus === 'leyendo') bg-amber-50 border-amber-200 text-amber-800
                @else bg-emerald-50 border-emerald-200 text-emerald-800 @endif">
                <div class="flex items-center gap-3">
                    @if($feedback->estatus === 'enviado')
                        <span class="text-2xl">🔴</span>
                        <div>
                            <p class="font-bold text-sm">Tu mensaje ha sido enviado correctamente</p>
                            <p class="text-xs opacity-90">El Administrador aún no ha abierto este mensaje. En cuanto lo revise, verás el cambio de estado.</p>
                        </div>
                    @elseif($feedback->estatus === 'leyendo')
                        <span class="text-2xl">🟠</span>
                        <div>
                            <p class="font-bold text-sm">El Administrador está revisando tu mensaje</p>
                            <p class="text-xs opacity-90">El equipo ya abrió esta conversación y está al tanto de tu queja o sugerencia.</p>
                        </div>
                    @else
                        <span class="text-2xl">🟢</span>
                        <div>
                            <p class="font-bold text-sm">Sugerencia / Queja respondida y cerrada</p>
                            <p class="text-xs opacity-90">El Administrador ha respondido o cerrado este comentario. Esta conversación ha finalizado.</p>
                        </div>
                    @endif
                </div>

                <!-- Botones de Gestión Exclusivos del Admin -->
                @if($isAdmin)
                    <div class="flex items-center gap-2">
                        @if($feedback->estatus !== 'leido')
                            <form action="{{ route('feedback.status', $feedback->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="estatus" value="leido">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1">
                                    <span>✓</span> Marcar como Leído / Cerrar
                                </button>
                            </form>
                        @else
                            <form action="{{ route('feedback.status', $feedback->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="estatus" value="leyendo">
                                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow transition">
                                    Reabrir en Revisión (🟠)
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <!-- TICKET INICIAL (Mensaje Original) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-start justify-between border-b border-gray-100 pb-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-lg">
                            {{ substr($feedback->user->name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">
                                {{ $feedback->user->name ?? 'Usuario del sistema' }}
                                @if($feedback->user && $feedback->user->hasRole('admin'))
                                    <span class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded-full ml-1">ADMIN</span>
                                @endif
                            </h3>
                            <p class="text-xs text-gray-400">
                                {{ $feedback->user->email ?? '' }} • Enviado el {{ $feedback->created_at->format('d/m/Y h:i A') }} ({{ $feedback->created_at->diffForHumans() }})
                            </p>
                        </div>
                    </div>

                    <span class="text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wide 
                        @if($feedback->tipo === 'queja') bg-rose-100 text-rose-700 
                        @elseif($feedback->tipo === 'sugerencia') bg-purple-100 text-purple-700 
                        @else bg-blue-100 text-blue-700 @endif">
                        {{ $feedback->tipo }}
                    </span>
                </div>

                <div class="prose max-w-none text-gray-800 text-base leading-relaxed whitespace-pre-line">
                    {{ $feedback->mensaje }}
                </div>

                @if($feedback->imagen)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2 flex items-center gap-1">
                            <span>📷</span> Imagen adjunta por el usuario:
                        </p>
                        <a href="{{ asset($feedback->imagen) }}" target="_blank" class="inline-block border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                            <img src="{{ asset($feedback->imagen) }}" alt="Adjunto del Feedback" class="max-h-72 object-contain bg-gray-50">
                        </a>
                    </div>
                @endif
            </div>

            <!-- HILO DE CONVERSACIÓN / RESPUESTAS -->
            @if($feedback->mensajes->count() > 0)
                <div class="space-y-4 mb-6">
                    <h4 class="font-bold text-sm text-gray-600 uppercase tracking-wider px-2">
                        Historial de Conversación ({{ $feedback->mensajes->count() }})
                    </h4>

                    @foreach($feedback->mensajes as $reply)
                        @php
                            $esAdminReply = $reply->user && $reply->user->hasRole('admin');
                        @endphp
                        <div class="rounded-xl shadow-sm border p-5 {{ $esAdminReply ? 'bg-indigo-50/50 border-indigo-200 ml-6' : 'bg-white border-gray-100 mr-6' }}">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full font-bold flex items-center justify-center text-xs
                                        {{ $esAdminReply ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                                        {{ substr($reply->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="font-bold text-sm text-gray-900">
                                        {{ $reply->user->name ?? 'Usuario' }}
                                    </span>
                                    @if($esAdminReply)
                                        <span class="text-xs bg-indigo-600 text-white font-semibold px-2 py-0.5 rounded-full">
                                            ADMINISTRADOR
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400">
                                    {{ $reply->created_at->format('d/m/Y h:i A') }} ({{ $reply->created_at->diffForHumans() }})
                                </span>
                            </div>

                            <div class="text-gray-800 text-sm whitespace-pre-line leading-relaxed">
                                {{ $reply->mensaje }}
                            </div>

                            @if($reply->imagen)
                                <div class="mt-3">
                                    <a href="{{ asset($reply->imagen) }}" target="_blank" class="inline-block border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                        <img src="{{ asset($reply->imagen) }}" alt="Adjunto respuesta" class="max-h-48 object-contain">
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- CAJA PARA ENVIAR NUEVA RESPUESTA -->
            @if($feedback->estatus !== 'leido')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h4 class="font-bold text-gray-800 text-base mb-3 flex items-center gap-2">
                    <span>💬</span> Agregar un comentario o respuesta
                </h4>
                <form action="{{ route('feedback.reply', $feedback->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <textarea name="mensaje" rows="3" required placeholder="Escribe tu respuesta aquí..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <label for="reply_img" class="cursor-pointer bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-2 rounded-lg transition flex items-center gap-1.5 border border-gray-300">
                                <span>📷</span> Adjuntar imagen
                            </label>
                            <input type="file" name="imagen" id="reply_img" accept="image/*" class="hidden" onchange="document.getElementById('file_name').innerText = this.files[0] ? this.files[0].name : ''">
                            <span id="file_name" class="text-xs text-gray-500"></span>
                        </div>

                        <div class="flex items-center gap-3">
                            @if($isAdmin)
                                <!-- Permite al admin elegir si marcar como leído/resuelto al responder -->
                                <select name="estatus" class="text-xs border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="leido" selected>🟢 Responder y Marcar como Leído / Resuelto</option>
                                    <option value="leyendo">🟠 Responder y Dejar en Revisión</option>
                                </select>
                            @endif
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow-md transition">
                                Enviar Respuesta &rarr;
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
