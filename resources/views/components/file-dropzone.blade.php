@props([
    'name',
    'id' => null,
    'label' => null,
    'required' => false,
    'accept' => 'image/png,image/jpeg,image/webp,image/jpg',
    'placeholder' => 'Haga clic para seleccionar archivo o arrastre aquí',
    'helpText' => 'Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB / 5MB)',
    'currentImage' => null,
    'currentImageLabel' => 'Imagen actual:',
    'errorKey' => null,
])

@php
    $inputId = $id ?? $name;
    $errorName = $errorKey ?? $name;
@endphp

<div class="space-y-1.5"
     x-data="{
        fileName: '',
        filePreview: null,
        isDropping: false,
        handleFileSelect(event) {
            const file = event.target.files && event.target.files.length > 0 ? event.target.files[0] : null;
            this.setFile(file);
        },
        handleDrop(event) {
            this.isDropping = false;
            if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length > 0) {
                const file = event.dataTransfer.files[0];
                const input = $refs.fileInput;
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                this.setFile(file);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
        setFile(file) {
            if (!file) {
                this.fileName = '';
                this.filePreview = null;
                return;
            }
            this.fileName = file.name;
            if (file.type && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.filePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                this.filePreview = null;
            }
        },
        clearFile() {
            this.fileName = '';
            this.filePreview = null;
            $refs.fileInput.value = '';
            $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
     }">

    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            {{ $label }}
            @if($required)
                <span class="text-rose-500 font-bold ml-0.5">*</span>
            @endif
        </label>
    @endif

    <div @click="$refs.fileInput.click()"
         @dragover.prevent="isDropping = true"
         @dragleave.prevent="isDropping = false"
         @drop.prevent="handleDrop($event)"
         :class="isDropping ? 'border-indigo-500 bg-indigo-100/60 dark:bg-indigo-950/60 ring-2 ring-indigo-500/30 scale-[1.01]' : 'border-indigo-300 dark:border-slate-600 hover:border-indigo-500 bg-indigo-50/30 dark:bg-slate-900/40'"
         class="relative border-2 border-dashed rounded-2xl p-4 text-center cursor-pointer transition-all duration-200 shadow-xs group">

        <input type="file"
               name="{{ $name }}"
               id="{{ $inputId }}"
               accept="{{ $accept }}"
               @if($required) required @endif
               x-ref="fileInput"
               class="hidden"
               @change="handleFileSelect($event)">

        <!-- Icono de Subida -->
        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </div>

        <!-- Texto Principal -->
        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="fileName ? fileName : '{{ $placeholder }}'">
            {{ $placeholder }}
        </p>

        <!-- Leyenda Inferior -->
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ $helpText }}
        </p>

        <!-- Previsualización Miniatura Inmediata (Alpine.js) -->
        <template x-if="filePreview">
            <div class="mt-3 inline-flex items-center gap-3 p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm text-left max-w-full"
                 @click.stop>
                <img :src="filePreview" class="w-12 h-12 object-cover rounded-lg border border-slate-200 dark:border-slate-700 flex-shrink-0" alt="Vista previa">
                <div class="min-w-0 flex-1">
                    <span class="block text-xs font-bold text-slate-800 dark:text-white truncate" x-text="fileName"></span>
                    <span class="block text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Archivo seleccionado
                    </span>
                </div>
                <button type="button" @click.stop="clearFile()" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors ml-2 cursor-pointer" title="Quitar archivo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>

        <!-- Imagen Actual (si existe en base de datos y no se ha seleccionado una nueva) -->
        @if($currentImage)
            <div x-show="!filePreview" class="mt-3 inline-flex items-center gap-3 px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 shadow-2xs text-left"
                 @click.stop>
                <img src="{{ $currentImage }}" class="w-11 h-11 object-cover rounded-lg border border-slate-200 dark:border-slate-700 flex-shrink-0" alt="{{ $currentImageLabel }}">
                <div class="text-xs">
                    <span class="block font-bold text-slate-700 dark:text-slate-300 text-[11px]">{{ $currentImageLabel }}</span>
                    <span class="block text-slate-500 dark:text-slate-400 text-[10px]">Se conservará si no seleccionas otra</span>
                </div>
            </div>
        @endif
    </div>

    @error($errorName)
        <span class="text-rose-500 text-xs font-semibold block mt-1">{{ $message }}</span>
    @enderror
</div>

