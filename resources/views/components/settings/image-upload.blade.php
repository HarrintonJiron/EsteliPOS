@props([
    'name',
    'label',
    'currentUrl' => null,
    'help' => 'Cualquier tamaño. Se optimiza automáticamente.',
    'max' => '20 MB',
    'removeName' => null,
])

@php($inputId = 'upload-'.$name)

<div class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-300 hover:shadow-md" data-image-upload>
    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-4 py-3">
        <div>
            <label for="{{ $inputId }}" class="text-sm font-semibold text-slate-900">{{ $label }}</label>
            <p class="mt-0.5 text-[11px] text-slate-500">JPG, PNG, WebP o GIF · máximo {{ $max }}</p>
        </div>
        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $currentUrl ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}" data-image-status>
            {{ $currentUrl ? 'Imagen actual' : 'Sin imagen' }}
        </span>
    </div>

    <div class="p-4">
        <label for="{{ $inputId }}" class="relative flex min-h-48 cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-gradient-to-br from-slate-50 to-indigo-50/50 transition hover:border-indigo-400 hover:bg-indigo-50" data-image-dropzone>
            <img src="{{ $currentUrl ?: '' }}"
                 alt="Vista previa de {{ strtolower($label) }}"
                 class="absolute inset-0 h-full w-full object-contain p-4 {{ $currentUrl ? '' : 'hidden' }}"
                 data-image-preview>

            <div class="relative z-10 flex flex-col items-center px-6 py-8 text-center {{ $currentUrl ? 'hidden' : '' }}" data-image-placeholder>
                <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16.5V19a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2.5M8 8l4-4m0 0 4 4m-4-4v12"/>
                    </svg>
                </span>
                <span class="text-sm font-semibold text-slate-700">Selecciona o arrastra una imagen</span>
                <span class="mt-1 max-w-sm text-xs leading-5 text-slate-500">{{ $help }}</span>
            </div>

            <span class="absolute bottom-3 right-3 hidden rounded-xl bg-slate-900/85 px-3 py-2 text-xs font-semibold text-white shadow-lg backdrop-blur group-hover:inline-flex" data-image-change>
                Cambiar imagen
            </span>
        </label>

        <input id="{{ $inputId }}" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-image-input>

        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="min-w-0 truncate text-xs text-slate-500" data-image-filename>
                {{ $currentUrl ? 'Imagen guardada en el sistema' : 'Ningún archivo seleccionado' }}
            </p>

            @if($removeName && $currentUrl)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                    <input type="checkbox" name="{{ $removeName }}" value="1" class="rounded border-slate-300 text-red-600" data-image-remove>
                    Quitar imagen
                </label>
            @endif
        </div>

        @error($name)
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.querySelectorAll('[data-image-upload]').forEach(wrapper => {
            const input = wrapper.querySelector('[data-image-input]');
            const preview = wrapper.querySelector('[data-image-preview]');
            const placeholder = wrapper.querySelector('[data-image-placeholder]');
            const dropzone = wrapper.querySelector('[data-image-dropzone]');
            const status = wrapper.querySelector('[data-image-status]');
            const filename = wrapper.querySelector('[data-image-filename]');
            const remove = wrapper.querySelector('[data-image-remove]');

            const showFile = file => {
                if (!file) return;
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
                placeholder?.classList.add('hidden');
                status.textContent = 'Nueva imagen';
                status.className = 'rounded-full bg-indigo-100 px-2.5 py-1 text-[10px] font-semibold text-indigo-700';
                filename.textContent = file.name;
                if (remove) remove.checked = false;
            };

            input?.addEventListener('change', () => showFile(input.files?.[0]));

            preview?.addEventListener('error', () => {
                preview.classList.add('hidden');
                placeholder?.classList.remove('hidden');
                status.textContent = 'No disponible';
                status.className = 'rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700';
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone?.addEventListener(eventName, event => {
                    event.preventDefault();
                    dropzone.classList.add('border-indigo-500', 'bg-indigo-50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone?.addEventListener(eventName, event => {
                    event.preventDefault();
                    dropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
                });
            });

            dropzone?.addEventListener('drop', event => {
                const file = event.dataTransfer?.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            remove?.addEventListener('change', () => {
                if (!remove.checked) return;
                preview.classList.add('hidden');
                placeholder?.classList.remove('hidden');
                status.textContent = 'Se quitará';
                status.className = 'rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700';
                filename.textContent = 'La imagen se eliminará al guardar';
                input.value = '';
            });
        });
    </script>
    @endpush
@endonce
