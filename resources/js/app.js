const FLASH_KEY = 'workspace-flash-toast';

const stashToast = (message, type = 'success') => {
    if (!message) return;

    try {
        sessionStorage.setItem(FLASH_KEY, JSON.stringify({ message, type }));
    } catch (error) {
        // Storage can be unavailable (private mode); the reload still works.
    }
};

const takeStashedToast = () => {
    try {
        const raw = sessionStorage.getItem(FLASH_KEY);
        if (!raw) return null;
        sessionStorage.removeItem(FLASH_KEY);
        return JSON.parse(raw);
    } catch (error) {
        return null;
    }
};

// AJAX submits follow the redirect themselves, which consumes the session flash.
// Read the rendered toast out of that response so it can be replayed after reload.
const readFlashFrom = async (response) => {
    try {
        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        return doc.querySelector('.toast-ios:not(.is-error) .toast-ios-body span')?.textContent?.trim() || null;
    } catch (error) {
        return null;
    }
};

const reloadWithToast = async (response) => {
    const message = await readFlashFrom(response);
    if (message) stashToast(message, 'success');
    window.location.reload();
};

const ready = (fn) => {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
};

const TOAST_ICONS = {
    success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
    error: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
};

const toastStack = () => {
    let stack = document.getElementById('toast-stack');

    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toast-stack';
        stack.className = 'toast-stack';
        stack.setAttribute('role', 'status');
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }

    return stack;
};

const dismissToast = (el) => {
    if (!el || el.classList.contains('closing')) return;
    el.classList.remove('paused');
    el.classList.add('closing');
    el.addEventListener('animationend', () => el.remove(), { once: true });
    setTimeout(() => el.remove(), 500);
};

const armToast = (el, life = 6000) => {
    el.style.setProperty('--toast-life', life + 'ms');
    let timer = setTimeout(() => dismissToast(el), life);

    el.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        el.classList.add('paused');
    });

    el.addEventListener('mouseleave', () => {
        el.classList.remove('paused');
        timer = setTimeout(() => dismissToast(el), 1500);
    });

    el.querySelector('[data-toast-close]')?.addEventListener('click', () => dismissToast(el));
};

const toast = (message, type = 'error') => {
    if (!message) return;

    const el = document.createElement('div');
    el.className = 'toast-ios' + (type === 'error' ? ' is-error' : '');
    el.dataset.glassToast = '';
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = '<span class="toast-ios-icon"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
        + (TOAST_ICONS[type] || TOAST_ICONS.success)
        + '</svg></span><div class="toast-ios-body"><b></b><span></span></div>'
        + '<button type="button" class="toast-ios-close" data-toast-close aria-label="Tutup notifikasi">'
        + '<svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>'
        + '</button><i class="toast-ios-progress" aria-hidden="true"></i>';

    el.querySelector('b').textContent = type === 'success' ? 'Berhasil' : 'Gagal diproses';
    el.querySelector('.toast-ios-body span').textContent = message;
    toastStack().appendChild(el);
    armToast(el);
};

const initDynamicRows = (scope) => {
    if (!scope) return;

    let counter = 1000;

    const rows = scope.querySelector('#agenda-rows');
    const rowTemplate = scope.querySelector('#agenda-row-template');

    const updateRowNumbers = () => {
        rows?.querySelectorAll('[data-agenda-row]').forEach((row, index) => {
            const label = row.querySelector('[data-row-number]');
            if (label) label.textContent = index + 1;
        });
    };

    const bindRemove = (row) => {
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => {
            if (rows && rows.children.length > 1) row.remove();
            updateRowNumbers();
        });
    };

    scope.querySelector('#add-agenda-row')?.addEventListener('click', () => {
        if (!rows || !rowTemplate) return;
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        counter += 1;
        node.innerHTML = node.innerHTML.replaceAll('__INDEX__', String(counter));
        rows.appendChild(node);
        bindRemove(node);
        updateRowNumbers();
        node.querySelector('input[name$="[title]"]')?.focus();
    });

    rows?.querySelectorAll('[data-agenda-row]').forEach(bindRemove);

    const extraRows = scope.querySelector('#extra-rows');
    const extraTemplate = scope.querySelector('#extra-row-template');

    const bindExtraRemove = (row) => {
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => row.remove());
    };

    scope.querySelector('#add-extra-row')?.addEventListener('click', () => {
        if (!extraRows || !extraTemplate) return;
        const node = extraTemplate.content.firstElementChild.cloneNode(true);
        counter += 1;
        node.innerHTML = node.innerHTML.replaceAll('__INDEX__', String(counter));
        extraRows.appendChild(node);
        bindExtraRemove(node);
        node.querySelector('input[name$="[title]"]')?.focus();
    });

    extraRows?.querySelectorAll('[data-extra-row]').forEach(bindExtraRemove);
};

ready(() => {
    // Sidebar on small screens
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');

    const syncSidebar = (open) => {
        sidebar?.classList.toggle('open', open);
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('nav-open', open);
    };

    toggle?.addEventListener('click', () => syncSidebar(!sidebar?.classList.contains('open')));

    document.querySelectorAll('[data-sidebar-close]').forEach((backdrop) => {
        backdrop.addEventListener('click', () => syncSidebar(false));
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900 && sidebar?.classList.contains('open')) syncSidebar(false);
    });

    document.addEventListener('click', (event) => {
        if (window.innerWidth <= 900 && sidebar?.classList.contains('open')
            && !sidebar.contains(event.target) && !toggle?.contains(event.target)) {
            syncSidebar(false);
        }
    });

    // Dropdowns (notifications, filters)
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const button = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        button?.addEventListener('click', (event) => {
            event.stopPropagation();
            menu?.classList.toggle('open');
        });

        dropdown.querySelectorAll('[data-dropdown-menu] a, [data-dropdown-menu] button').forEach((item) => {
            item.addEventListener('click', () => menu?.classList.remove('open'));
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-dropdown-menu].open').forEach((menu) => {
            if (!menu.parentElement?.contains(event.target)) menu.classList.remove('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('[data-dropdown-menu].open').forEach((menu) => menu.classList.remove('open'));
        syncSidebar(false);
    });

    // Auto-submit filter forms when a select changes
    document.querySelectorAll('form[data-autosubmit] select').forEach((select) => {
        select.addEventListener('change', () => select.form?.submit());
    });

    // Fill both password fields with the workspace default password
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-use-default-password]');
        if (!button) return;

        button.closest('form')?.querySelectorAll('input[name="password"], input[name="password_confirmation"]').forEach((input) => {
            input.value = button.dataset.useDefaultPassword;
        });
    });

    // Glass flash toasts rendered by the server
    document.querySelectorAll('[data-glass-toast]').forEach((el) => armToast(el));

    // Toast carried over from an AJAX submit that already consumed the flash
    const pendingToast = takeStashedToast();
    if (pendingToast) setTimeout(() => toast(pendingToast.message, pendingToast.type || 'success'), 120);

    // Dismiss flash messages
    document.querySelectorAll('.alert[data-dismissible]').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s, transform .4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(() => alert.remove(), 420);
        }, 6000);
    });

    initDynamicRows(document);
    initModals();
});

function initModals() {
    const root = document.getElementById('modal-root');
    if (!root) return;

    const card = root.querySelector('.modal-card');
    const title = document.getElementById('modal-title');
    const body = document.getElementById('modal-body');
    let returnFocus = null;
    let currentSize = null;

    const send = (form, data) => fetch(form.action, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    });

    const show = (heading, wide) => {
        returnFocus = document.activeElement;
        root.classList.add('open');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        card?.classList.toggle('lg', wide === 'lg');
        title.textContent = heading;
        body.scrollTop = 0;
        body.innerHTML = '<div class="modal-loading">Memuat…</div>';
    };

    const close = () => {
        root.classList.remove('open');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        body.innerHTML = '';
        returnFocus?.focus?.();
        returnFocus = null;
    };

    const notify = (form, message) => {
        let box = form.querySelector('.modal-alert');

        if (!box) {
            box = document.createElement('div');
            box.className = 'modal-alert';
            form.prepend(box);
        }

        box.textContent = message;
        box.scrollIntoView({ block: 'nearest' });
    };

    const open = async (url, wide) => {
        currentSize = wide || null;
        show('Memuat…', wide);

        try {
            const target = new URL(url, window.location.origin);
            target.searchParams.set('modal', '1');

            const response = await fetch(target, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Halaman tidak dapat dimuat (' + response.status + ').');

            const frame = new DOMParser()
                .parseFromString(await response.text(), 'text/html')
                .querySelector('[data-modal-content]');

            if (!frame) throw new Error('Konten modal tidak tersedia.');

            title.textContent = (frame.dataset.modalTitle || '').trim() || 'Detail';
            body.innerHTML = frame.innerHTML;
            body.scrollTop = 0;
            initDynamicRows(body);
            body.querySelector('input:not([type="hidden"]), select, textarea')?.focus({ preventScroll: true });
        } catch (error) {
            title.textContent = 'Gagal memuat';
            body.innerHTML = '<div class="modal-alert">' + error.message + '</div>';
        }
    };

    const showErrors = (form, errors, message) => {
        form.querySelectorAll('.field-error').forEach((node) => node.remove());
        form.querySelectorAll('.invalid').forEach((node) => node.classList.remove('invalid'));

        const keys = Object.keys(errors || {});

        if (!keys.length) return notify(form, message || 'Data belum valid.');

        notify(form, [].concat(errors[keys[0]])[0]);

        keys.forEach((key) => {
            const field = form.elements.namedItem(key);
            const input = field instanceof RadioNodeList ? field[0] : field;

            if (!input?.classList || input.type === 'hidden') return;

            input.classList.add('invalid');

            const note = document.createElement('small');
            note.className = 'field-error';
            note.textContent = [].concat(errors[key])[0];
            input.insertAdjacentElement('afterend', note);
        });

        form.querySelector('.invalid')?.focus?.();
    };

    const submit = async (form, submitter, inside) => {
        const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type])')];
        buttons.forEach((button) => { button.disabled = true; });

        const data = new FormData(form);
        if (submitter?.name) data.set(submitter.name, submitter.value);

        try {
            const response = await send(form, data);

            if (response.status === 422) {
                const payload = await response.json().catch(() => ({}));
                return showErrors(form, payload.errors, payload.message);
            }

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                const message = payload?.message || 'Terjadi kesalahan (' + response.status + ').';
                return inside ? notify(form, message) : toast(message);
            }

            close();
            return reloadWithToast(response);
        } catch (error) {
            if (inside) notify(form, 'Koneksi gagal. Silakan coba lagi.');
            else toast('Koneksi gagal. Silakan coba lagi.');
        } finally {
            buttons.forEach((button) => { button.disabled = false; });
        }
    };

    const askConfirm = (form) => {
        show('Konfirmasi');
        body.innerHTML = '<p class="modal-confirm-text" style="margin:0 0 18px;font-size:13px;line-height:1.7;color:#3d4a61"></p>'
            + '<div class="modal-alert" hidden></div>'
            + '<div class="flex gap-2 justify-between"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button>'
            + '<button type="button" class="btn btn-danger" data-confirm-accept>Ya, lanjutkan</button></div>';

        body.querySelector('.modal-confirm-text').textContent = form.dataset.confirm || 'Lanjutkan tindakan ini?';

        const accept = body.querySelector('[data-confirm-accept]');

        accept.addEventListener('click', async () => {
            accept.disabled = true;

            try {
                const response = await send(form, new FormData(form));

                if (!response.ok) {
                    const payload = await response.json().catch(() => null);
                    throw new Error(payload?.message || 'Tindakan gagal (' + response.status + ').');
                }

                close();
                return reloadWithToast(response);
            } catch (error) {
                const box = body.querySelector('.modal-alert');
                box.hidden = false;
                box.textContent = error.message;
                accept.disabled = false;
            }
        });
    };

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal]');

        if (opener) {
            event.preventDefault();
            return open(opener.dataset.modal, opener.dataset.modalSize);
        }

        if (event.target.closest('[data-modal-close]')) {
            event.preventDefault();
            close();
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) return;

        if (form.matches('[data-confirm]')) {
            event.preventDefault();
            return askConfirm(form);
        }

        const inside = body.contains(form);

        if (inside && form.method.toLowerCase() === 'get') {
            event.preventDefault();
            const params = new URLSearchParams(new FormData(form)).toString();
            return open(params ? form.action + '?' + params : form.action, currentSize);
        }

        if (!inside && !form.matches('[data-ajax]')) return;

        event.preventDefault();
        submit(form, event.submitter, inside);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('open')) close();
    });
}
