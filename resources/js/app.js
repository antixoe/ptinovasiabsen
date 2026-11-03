import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('#modal');
    const modalCard = document.querySelector('.modal-card');
    const modalEyebrow = modalCard.querySelector('.eyebrow');
    const modalTitle = modalCard.querySelector('h2');
    const modalIntro = modalCard.querySelector('.modal-intro');
    const modalSave = document.querySelector('#modalSave');
    const modalCancel = document.querySelector('#modalCancel');
    const titleInput = document.querySelector('#agendaTitle');
    const toast = document.querySelector('#toast');
    const toastText = document.querySelector('#toastText');
    const formFields = modalCard.querySelectorAll('label, .form-row');
    let timer;
    let modalMode = 'create';

    const showToast = (message) => {
        toastText.textContent = message;
        toast.classList.add('show');
        clearTimeout(timer);
        timer = setTimeout(() => toast.classList.remove('show'), 2600);
    };

    const openAgendaModal = (mode = 'create', row = null) => {
        modalMode = mode;
        formFields.forEach((field) => { field.style.removeProperty('display'); });
        modalSave.style.display = 'inline-flex';
        modalCancel.textContent = 'Batal';
        if (mode === 'edit' && row) {
            modalEyebrow.textContent = 'EDIT AGENDA';
            modalTitle.textContent = 'Perbarui agenda kerja';
            modalIntro.textContent = 'Sesuaikan detail agenda agar tim tetap sinkron.';
            titleInput.value = row.querySelector('.agenda-title b').textContent.trim();
            modalSave.textContent = 'Simpan perubahan';
        } else {
            modalEyebrow.textContent = 'AGENDA BARU';
            modalTitle.textContent = 'Tambah agenda kerja';
            modalIntro.textContent = 'Catat rencana kerja supaya tim tetap sinkron.';
            titleInput.value = '';
            modalSave.textContent = 'Simpan agenda';
        }
        modal.classList.add('open');
        setTimeout(() => titleInput.focus(), 80);
    };

    const openReviewModal = (button) => {
        modalMode = 'review';
        const item = button.closest('.review-item');
        const itemTitle = item?.querySelector('.review-info b')?.textContent || 'Kiriman agenda';
        formFields.forEach((field) => { field.style.display = 'none'; });
        modalEyebrow.textContent = 'REVIEW KIRIMAN';
        modalTitle.textContent = itemTitle;
        modalIntro.textContent = 'Periksa ringkasan ini sebelum memberi persetujuan kepada anggota tim.';
        modalSave.textContent = 'Tandai sudah direview';
        modalCancel.textContent = 'Tutup';
        modal.classList.add('open');
    };

    const closeModal = () => { modal.classList.remove('open'); modalMode = 'create'; };
    document.querySelector('#addAgenda')?.addEventListener('click', () => openAgendaModal());
    document.querySelector('#modalClose')?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    modalSave?.addEventListener('click', () => {
        if (modalMode === 'review') { closeModal(); showToast('Kiriman ditandai sudah direview'); return; }
        if (!titleInput.value.trim()) { showToast('Isi judul agenda terlebih dahulu'); titleInput.focus(); return; }
        closeModal();
        showToast(modalMode === 'edit' ? 'Perubahan agenda berhasil disimpan' : 'Agenda berhasil ditambahkan');
    });

    document.querySelectorAll('.review-action').forEach((button) => button.addEventListener('click', () => openReviewModal(button)));
    document.querySelectorAll('.row-more').forEach((button) => button.addEventListener('click', () => openAgendaModal('edit', button.closest('.agenda-row'))));

    const filterButton = document.querySelector('.filter-button');
    const agendaRows = [...document.querySelectorAll('.agenda-row')];
    if (filterButton) {
        const menu = document.createElement('div');
        menu.className = 'filter-menu';
        menu.innerHTML = '<button data-filter="all">Semua status</button><button data-filter="reviewed">Direview</button><button data-filter="progress">Berjalan</button><button data-filter="planned">Terjadwal</button>';
        filterButton.parentElement.append(menu);
        filterButton.addEventListener('click', (event) => { event.stopPropagation(); menu.classList.toggle('open'); });
        menu.querySelectorAll('button').forEach((option) => option.addEventListener('click', () => {
            const filter = option.dataset.filter;
            agendaRows.forEach((row) => { row.style.display = filter === 'all' || row.querySelector(`.${filter === 'progress' ? 'in-progress' : filter === 'planned' ? 'planned-pill' : 'reviewed'}`) ? 'flex' : 'none'; });
            filterButton.firstChild.textContent = option.textContent + ' ';
            menu.classList.remove('open');
            showToast(`Filter: ${option.textContent}`);
        }));
    }
    document.addEventListener('click', () => document.querySelector('.filter-menu')?.classList.remove('open'));

    document.querySelector('.search input')?.addEventListener('input', (event) => {
        const query = event.target.value.toLowerCase();
        agendaRows.forEach((row) => { row.style.display = row.textContent.toLowerCase().includes(query) ? 'flex' : 'none'; });
    });
    const notificationButton = document.querySelector('.notification-button');
    if (notificationButton) {
        notificationButton.removeAttribute('data-toast');
        const notificationWrap = document.createElement('div');
        notificationWrap.className = 'notification-wrap';
        notificationButton.parentElement.insertBefore(notificationWrap, notificationButton);
        notificationWrap.appendChild(notificationButton);
        const notificationPanel = document.createElement('div');
        notificationPanel.className = 'notification-popover';
        notificationPanel.innerHTML = '<div class="notification-head"><div><b>Notifikasi</b><span>4 belum dibaca</span></div><button class="mark-read">Tandai semua dibaca</button></div><div class="notification-list"><button class="notification-item unread"><span class="notification-dot violet">↻</span><span><b>Ringkasan kerja menunggu review</b><small>Salsabila mengirim ringkasan hari ini · 2 mnt</small></span></button><button class="notification-item unread"><span class="notification-dot blue">＋</span><span><b>Agenda baru ditambahkan</b><small>Nadia menambahkan agenda ke Tim Produk · 18 mnt</small></span></button><button class="notification-item"><span class="notification-dot green">✓</span><span><b>Agenda berhasil direview</b><small>Fikri menyetujui agenda Andi · 1 jam</small></span></button><button class="notification-item"><span class="notification-dot orange">!</span><span><b>Pengingat pengumpulan sore</b><small>4 siswa PKL belum mengirim ringkasan</small></span></button></div><button class="notification-footer">Buka pusat notifikasi →</button>';
        notificationWrap.appendChild(notificationPanel);
        notificationButton.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); notificationPanel.classList.toggle('open'); showToast(notificationPanel.classList.contains('open') ? '4 notifikasi menunggu perhatian' : 'Notifikasi ditutup'); });
        notificationPanel.querySelector('.mark-read').addEventListener('click', () => { notificationPanel.querySelectorAll('.unread').forEach((item) => item.classList.remove('unread')); notificationButton.querySelector('.notification-dot')?.remove(); showToast('Semua notifikasi sudah dibaca'); });
        notificationPanel.querySelectorAll('.notification-item').forEach((item) => item.addEventListener('click', () => { item.classList.remove('unread'); showToast('Notifikasi dibuka'); }));
        notificationPanel.querySelector('.notification-footer').addEventListener('click', () => { notificationPanel.classList.remove('open'); showToast('Membuka pusat notifikasi'); });
        document.addEventListener('click', (event) => { if (!notificationWrap.contains(event.target)) notificationPanel.classList.remove('open'); });
    }
    document.querySelectorAll('[data-toast]').forEach((button) => button.addEventListener('click', () => showToast(button.dataset.toast)));

    const monthLabel = document.querySelector('.calendar-head b');
    const months = ['Juni 2025', 'Juli 2025', 'Agustus 2025'];
    let monthIndex = 0;
    document.querySelectorAll('.calendar-head button').forEach((button, index) => button.addEventListener('click', () => {
        monthIndex = Math.max(0, Math.min(months.length - 1, monthIndex + (index === 0 ? -1 : 1)));
        monthLabel.textContent = months[monthIndex];
        showToast(`Kalender berpindah ke ${months[monthIndex]}`);
    }));
    document.querySelectorAll('.nav-item').forEach((item) => item.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelectorAll('.nav-item').forEach((nav) => nav.classList.remove('active'));
        item.classList.add('active');
        document.querySelector('#breadcrumbCurrent').textContent = item.textContent.trim().replace(/\d+$/, '').trim();
        document.querySelector('#sidebar').classList.remove('open');
        showToast(`Membuka ${item.dataset.section}`);
    }));
    document.querySelector('#mobileMenu')?.addEventListener('click', () => document.querySelector('#sidebar').classList.toggle('open'));
});
