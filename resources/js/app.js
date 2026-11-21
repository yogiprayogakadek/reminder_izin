const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const isVisible = (element) => {
    const style = getComputedStyle(element);

    return style.display !== 'none' && style.visibility !== 'hidden' && element.getClientRects().length > 0;
};

const activeModal = () => {
    return [...document.querySelectorAll('[role="dialog"][aria-modal="true"]')].find(isVisible);
};

const focusFirst = (dialog) => {
    const target = dialog.querySelector('[data-autofocus]') ?? dialog.querySelector(focusableSelector);

    target?.focus();
};

let modalWasOpen = false;
let returnFocusTo = null;

const syncModalFocus = () => {
    const dialog = activeModal();

    if (dialog) {
        if (!modalWasOpen) {
            modalWasOpen = true;
            returnFocusTo = document.activeElement;
            focusFirst(dialog);
        } else if (!dialog.contains(document.activeElement)) {
            focusFirst(dialog);
        }

        return;
    }

    if (modalWasOpen) {
        modalWasOpen = false;

        if (returnFocusTo?.isConnected) {
            returnFocusTo.focus();
        }

        returnFocusTo = null;
    }
};

new MutationObserver(syncModalFocus).observe(document.documentElement, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['style', 'class'],
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab') {
        return;
    }

    const dialog = activeModal();

    if (!dialog) {
        return;
    }

    const items = [...dialog.querySelectorAll(focusableSelector)].filter(isVisible);

    if (items.length === 0) {
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (!dialog.contains(active)) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();

        return;
    }

    if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
});

const sidebarCookie = 'siperiz_sidebar';

const sidebarClosed = () => document.documentElement.dataset.sidebar === 'closed';

const syncSidebarToggle = () => {
    const closed = sidebarClosed();

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', String(!closed));
        button.setAttribute('aria-label', closed ? 'Tampilkan navigasi' : 'Sembunyikan navigasi');
    });
};

const toggleSidebar = () => {
    const closed = !sidebarClosed();

    if (closed) {
        document.documentElement.dataset.sidebar = 'closed';
    } else {
        delete document.documentElement.dataset.sidebar;
    }

    try {
        document.cookie = `${sidebarCookie}=${closed ? 'closed' : 'open'}; path=/; max-age=31536000; SameSite=Lax`;
    } catch {
        // Cookie diblokir: state hanya bertahan sampai halaman dimuat ulang.
    }

    syncSidebarToggle();
};

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('[data-sidebar-toggle]')) {
        return;
    }

    toggleSidebar();
});

document.addEventListener('DOMContentLoaded', syncSidebarToggle);
document.addEventListener('livewire:navigated', syncSidebarToggle);
