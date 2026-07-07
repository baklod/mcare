import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-dashboard-sidebar]');
    const backdrop = document.querySelector('[data-dashboard-backdrop]');
    const openButtons = document.querySelectorAll('[data-dashboard-menu-open]');
    const closeButtons = document.querySelectorAll('[data-dashboard-menu-close]');

    if (!sidebar || !backdrop) {
        return;
    }

    const setMenuOpen = (isOpen) => {
        sidebar.classList.toggle('is-open', isOpen);
        backdrop.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('overflow-hidden', isOpen);
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => setMenuOpen(true));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => setMenuOpen(false));
    });

    backdrop.addEventListener('click', () => setMenuOpen(false));

    sidebar.querySelectorAll('a[href^="#"], a[href^="/"], button[type="submit"]').forEach((item) => {
        item.addEventListener('click', () => setMenuOpen(false));
    });
});
