<div id="selectedItemBar" class="pos-pad-item hidden">
    <div class="pos-pad-item__meta">
        <span class="pos-pad-item__label">Editando</span>
        <span id="selectedItemName" class="pos-pad-item__name"></span>
    </div>
    <div class="pos-pad-item__qty" role="group" aria-label="Cantidad">
        <button type="button" class="pos-key pos-key--step" onclick="padAdjust(-1)" aria-label="Restar cantidad">−</button>
        <span id="selectedItemQty" class="pos-pad-item__value">0</span>
        <button type="button" class="pos-key pos-key--step" onclick="padAdjust(1)" aria-label="Sumar cantidad">+</button>
    </div>
</div>

<div id="posNumpad" class="pos-pad">
    <button type="button" id="posPadToggle" class="pos-pad__toggle" onclick="togglePosPad()" aria-expanded="false" aria-controls="posPadKeys">
        <span class="pos-pad__icon" aria-hidden="true">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
        </span>
        <span class="pos-pad__toggle-copy">
            <strong>Teclado</strong>
            <small>Cantidad</small>
        </span>
        <svg class="pos-pad-chevron" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    <div class="pos-pad__collapse">
        <div id="posPadKeys" class="pos-pad__body">
            <div class="pos-pad__grid">
                @foreach (['7', '8', '9', '4', '5', '6', '1', '2', '3'] as $key)
                    <button type="button" class="pos-key" onclick="padInput('{{ $key }}')">{{ $key }}</button>
                @endforeach
                <button type="button" class="pos-key" onclick="padInput('.')">.</button>
                <button type="button" class="pos-key" onclick="padInput('0')">0</button>
                <button type="button" class="pos-key pos-key--back" onclick="padBackspace()" aria-label="Borrar">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 4H8l-7 8 7 8h13a2 2 0 002-2V6a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9l-6 6m0-6 6 6"/>
                    </svg>
                </button>
            </div>
            <button type="button" class="pos-key pos-key--confirm" onclick="padConfirm()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Confirmar
            </button>
        </div>
    </div>
</div>
