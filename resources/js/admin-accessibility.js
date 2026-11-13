const dialogStates = new WeakMap();

// Shared by the confirmation, command palette, and keyboard help dialogs.
window.syncAdminDialog = (element, open) => {
    const previous = dialogStates.get(element);
    if (!open) {
        if (!previous) return;
        element.removeEventListener('keydown', previous.onKeyDown);
        document.getElementById('admin-app')?.removeAttribute('inert');
        if (previous.trigger?.isConnected) previous.trigger.focus();
        dialogStates.delete(element);
        return;
    }
    if (previous) return;

    const controls = () => [...element.querySelectorAll('a[href], button, input, select, textarea, [tabindex]')]
        .filter(control => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length);
    const onKeyDown = event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            element.dispatchEvent(new CustomEvent('admin-dialog-close'));
        }
        if (event.key !== 'Tab') return;
        const items = controls();
        if (!items.length) { event.preventDefault(); element.focus(); return; }
        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    };
    dialogStates.set(element, { trigger: document.activeElement, onKeyDown });
    document.getElementById('admin-app')?.setAttribute('inert', '');
    element.addEventListener('keydown', onKeyDown);
    requestAnimationFrame(() => {
        if (dialogStates.has(element)) (controls()[0] || element).focus();
    });
};
