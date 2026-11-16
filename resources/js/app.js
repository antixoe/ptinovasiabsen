const ready = (fn) => {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
};

ready(() => {
    // Sidebar on small screens
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');

    toggle?.addEventListener('click', () => sidebar?.classList.toggle('open'));

    document.addEventListener('click', (event) => {
        if (window.innerWidth <= 900 && sidebar?.classList.contains('open')
            && !sidebar.contains(event.target) && !toggle?.contains(event.target)) {
            sidebar.classList.remove('open');
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
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-dropdown-menu].open').forEach((menu) => menu.classList.remove('open'));
            sidebar?.classList.remove('open');
        }
    });

    // Confirm destructive actions
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    // Auto-submit filter forms when a select changes
    document.querySelectorAll('form[data-autosubmit] select').forEach((select) => {
        select.addEventListener('change', () => select.form?.submit());
    });

    // Dismiss flash messages
    document.querySelectorAll('.alert[data-dismissible]').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s, transform .4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(() => alert.remove(), 420);
        }, 6000);
    });

    // Agenda rows in the morning form
    const rows = document.getElementById('agenda-rows');
    const rowTemplate = document.getElementById('agenda-row-template');

    const bindRemove = (row) => {
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => {
            if (rows && rows.children.length > 1) row.remove();
            updateRowNumbers();
        });
    };

    const updateRowNumbers = () => {
        rows?.querySelectorAll('[data-agenda-row]').forEach((row, index) => {
            const label = row.querySelector('[data-row-number]');
            if (label) label.textContent = index + 1;
        });
    };

    let rowCounter = 1000;

    document.getElementById('add-agenda-row')?.addEventListener('click', () => {
        if (!rows || !rowTemplate) return;
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        rowCounter += 1;
        node.innerHTML = node.innerHTML.replaceAll('__INDEX__', String(rowCounter));
        rows.appendChild(node);
        bindRemove(node);
        updateRowNumbers();
        node.querySelector('input[name$="[title]"]')?.focus();
    });

    rows?.querySelectorAll('[data-agenda-row]').forEach(bindRemove);

    // Extra tasks added while writing the evening summary
    const extraRows = document.getElementById('extra-rows');
    const extraTemplate = document.getElementById('extra-row-template');

    const bindExtraRemove = (row) => {
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => row.remove());
    };

    document.getElementById('add-extra-row')?.addEventListener('click', () => {
        if (!extraRows || !extraTemplate) return;
        const node = extraTemplate.content.firstElementChild.cloneNode(true);
        rowCounter += 1;
        node.innerHTML = node.innerHTML.replaceAll('__INDEX__', String(rowCounter));
        extraRows.appendChild(node);
        bindExtraRemove(node);
        node.querySelector('input[name$="[title]"]')?.focus();
    });

    extraRows?.querySelectorAll('[data-extra-row]').forEach(bindExtraRemove);
});
