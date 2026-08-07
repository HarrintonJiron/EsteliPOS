document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('ui-toast-container');
    if (!container) return;

    document.querySelectorAll('[data-ui-toast]').forEach((el) => {
        const type = el.dataset.uiToast || 'info';
        const message = el.textContent.trim();
        if (message) showToast(message, type);
        el.remove();
    });

    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn && !btn.disabled) {
                btn.dataset.originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-block animate-pulse">Procesando...</span>';
            }
        });
    });
});

function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('ui-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `ui-alert ui-alert-${type} ui-toast`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(12px)';
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

window.showToast = showToast;
