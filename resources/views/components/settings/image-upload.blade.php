@props([
    'name',
    'label',
    'currentUrl' => null,
    'help' => 'Cualquier tamaño. Se optimiza automáticamente.',
    'max' => '20 MB',
    'removeName' => null,
])

@php($inputId = 'upload-'.$name)

<div class="rounded-xl border border-slate-200 p-4" data-image-upload>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
            <img src="{{ $currentUrl ?: '' }}" alt="Vista previa de {{ strtolower($label) }}" class="h-full w-full object-contain {{ $currentUrl ? '' : 'hidden' }}" data-image-preview>
            <span class="text-center text-xs text-slate-400 {{ $currentUrl ? 'hidden' : '' }}" data-image-placeholder>Sin imagen</span>
        </div>

        <div class="min-w-0 flex-1">
            <label for="{{ $inputId }}" class="block text-sm font-semibold text-slate-800">{{ $label }}</label>
            <p class="mt-1 text-xs text-slate-500">{{ $help }} Máximo {{ $max }}.</p>
            <input id="{{ $inputId }}" type="file" name="{{ $name }}" accept="image/*" class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" data-image-input>

            @if($removeName && $currentUrl)
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="{{ $removeName }}" value="1" class="rounded border-slate-300 text-indigo-600">
                    Quitar imagen actual
                </label>
            @endif

            @error($name)
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.querySelectorAll('[data-image-upload]').forEach(wrapper => {
            const input = wrapper.querySelector('[data-image-input]');
            const preview = wrapper.querySelector('[data-image-preview]');
            const placeholder = wrapper.querySelector('[data-image-placeholder]');

            input?.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) return;
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
                placeholder?.classList.add('hidden');
            });
        });
    </script>
    @endpush
@endonce
