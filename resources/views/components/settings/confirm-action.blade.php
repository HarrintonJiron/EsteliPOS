@props(['action', 'method' => 'POST', 'label', 'title', 'message', 'variant' => 'warning'])
@php($dialogId = 'confirm-'.md5($action.$label))

<button type="button" onclick="document.getElementById('{{ $dialogId }}').showModal()"
        {{ $attributes->class($variant === 'danger' ? 'text-red-600 text-sm font-medium' : 'text-amber-600 text-sm font-medium') }}>
    {{ $label }}
</button>
<dialog id="{{ $dialogId }}" class="w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50">
    <form method="dialog" class="p-6">
        <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $message }}</p>
        <div class="mt-6 flex justify-end gap-2">
            <button class="btn-outline">Cancelar</button>
            <button type="button" onclick="this.closest('dialog').querySelector('form[data-action]').submit()"
                    class="rounded-xl px-4 py-2 font-semibold text-white {{ $variant === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700' }}">
                Confirmar
            </button>
        </div>
    </form>
    <form data-action method="POST" action="{{ $action }}" class="hidden">
        @csrf
        @if(!in_array(strtoupper($method), ['GET', 'POST'])) @method($method) @endif
    </form>
</dialog>
