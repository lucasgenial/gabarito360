import './bootstrap';

const dialogOpeners = new WeakMap();
const themeStorageKey = 'g360-theme';

function storedTheme() {
    try {
        return localStorage.getItem(themeStorageKey) === 'dark' ? 'dark' : 'light';
    } catch {
        return 'light';
    }
}

function persistTheme(theme) {
    try {
        localStorage.setItem(themeStorageKey, theme);
    } catch {
        // The explicit theme still applies for the current page when storage is unavailable.
    }
}

function applyTheme(theme) {
    const selectedTheme = theme === 'dark' ? 'dark' : 'light';

    document.documentElement.dataset.theme = selectedTheme;

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        const activatesDark = selectedTheme === 'light';
        const label = activatesDark ? 'Ativar tema escuro' : 'Ativar tema claro';

        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
        toggle.querySelector('[data-theme-icon="dark"]')?.toggleAttribute('hidden', ! activatesDark);
        toggle.querySelector('[data-theme-icon="light"]')?.toggleAttribute('hidden', activatesDark);
    });
}

applyTheme(storedTheme());

function initializeInterface() {
    applyTheme(storedTheme());

    document.querySelectorAll('[data-application-realtime]').forEach((container) => {
        if (container.dataset.initialized === 'true' || ! window.Echo) {
            return;
        }

        container.dataset.initialized = 'true';
        window.Echo.private(`applications.${container.dataset.applicationRealtime}`)
            .listen('.application.progress.updated', ({ metrics }) => {
                Object.entries(metrics).forEach(([key, value]) => {
                    container.querySelectorAll(`[data-application-metric="${key}"]`)
                        .forEach((target) => {
                            target.textContent = value;
                        });
                });
            });
    });

    document.querySelectorAll('[data-tabs]').forEach((tabs) => {
        const tabList = tabs.querySelector('[role="tablist"]');

        if (! tabList || tabList.dataset.initialized === 'true') {
            return;
        }

        tabList.dataset.initialized = 'true';
        const tabButtons = [...tabList.querySelectorAll('[role="tab"]')];

        const selectTab = (selectedTab) => {
            tabButtons.forEach((tab) => {
                const selected = tab === selectedTab;
                const panel = tabs.querySelector(`#${CSS.escape(tab.getAttribute('aria-controls'))}`);

                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.setAttribute('tabindex', selected ? '0' : '-1');
                panel?.toggleAttribute('hidden', ! selected);
            });
        };

        tabList.addEventListener('click', (event) => {
            const tab = event.target.closest('[role="tab"]');

            if (tab) {
                selectTab(tab);
            }
        });

        tabList.addEventListener('keydown', (event) => {
            if (! ['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            const currentIndex = tabButtons.indexOf(document.activeElement);
            const targetIndex = event.key === 'Home'
                ? 0
                : event.key === 'End'
                    ? tabButtons.length - 1
                    : (currentIndex + (event.key === 'ArrowRight' ? 1 : -1) + tabButtons.length) % tabButtons.length;

            tabButtons[targetIndex]?.focus();
            selectTab(tabButtons[targetIndex]);
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeInterface);
document.addEventListener('livewire:navigated', initializeInterface);

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const themeToggle = event.target.closest('[data-theme-toggle]');

    if (themeToggle) {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';

        persistTheme(nextTheme);
        applyTheme(nextTheme);
        return;
    }

    const drawerTrigger = event.target.closest('[data-drawer-open]');

    if (drawerTrigger) {
        const drawer = document.getElementById(drawerTrigger.dataset.drawerOpen);

        if (drawer instanceof HTMLDialogElement) {
            dialogOpeners.set(drawer, drawerTrigger);
            drawer.showModal();
        }

        return;
    }

    const openTrigger = event.target.closest('[data-modal-open]');

    if (openTrigger) {
        const modal = document.getElementById(openTrigger.dataset.modalOpen);

        if (modal instanceof HTMLDialogElement) {
            dialogOpeners.set(modal, openTrigger);
            modal.showModal();
        }

        return;
    }

    const closeTrigger = event.target.closest('[data-modal-close], [data-drawer-close]');

    if (closeTrigger) {
        closeTrigger.closest('dialog')?.close();
        return;
    }

    const toastClose = event.target.closest('[data-toast-close]');

    if (toastClose) {
        toastClose.closest('[data-toast]')?.remove();
        return;
    }

    if (event.target instanceof HTMLDialogElement && event.target.matches('[data-modal], [data-drawer]')) {
        event.target.close();
        return;
    }

    document.querySelectorAll('.account-menu[open]').forEach((menu) => {
        if (! menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });

    const drawerLink = event.target.closest('[data-drawer] a');

    if (drawerLink) {
        drawerLink.closest('dialog')?.close();
    }
});

document.addEventListener('close', (event) => {
    if (! (event.target instanceof HTMLDialogElement)) {
        return;
    }

    dialogOpeners.get(event.target)?.focus();
    dialogOpeners.delete(event.target);
}, true);
