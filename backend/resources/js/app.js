import './bootstrap';

const modalOpeners = new WeakMap();

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-modal-open]');

    if (openTrigger) {
        const modal = document.getElementById(openTrigger.dataset.modalOpen);

        if (modal instanceof HTMLDialogElement) {
            modalOpeners.set(modal, openTrigger);
            modal.showModal();
        }

        return;
    }

    const closeTrigger = event.target.closest('[data-modal-close]');

    if (closeTrigger) {
        closeTrigger.closest('dialog')?.close();
        return;
    }

    if (event.target instanceof HTMLDialogElement && event.target.hasAttribute('data-modal')) {
        event.target.close();
    }
});

document.addEventListener('close', (event) => {
    if (! (event.target instanceof HTMLDialogElement)) {
        return;
    }

    modalOpeners.get(event.target)?.focus();
    modalOpeners.delete(event.target);
}, true);
