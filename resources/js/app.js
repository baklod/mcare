import './bootstrap';

const dashboardThemeStorageKey = 'mcare-dashboard-theme';
const storedDashboardTheme = window.localStorage.getItem(dashboardThemeStorageKey);
document.documentElement.dataset.dashboardTheme = storedDashboardTheme === 'dark' ? 'dark' : 'light';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-dashboard-sidebar]');
    const backdrop = document.querySelector('[data-dashboard-backdrop]');
    const openButtons = document.querySelectorAll('[data-dashboard-menu-open]');
    const closeButtons = document.querySelectorAll('[data-dashboard-menu-close]');
    const accountMenus = document.querySelectorAll('[data-dashboard-account]');
    const dashboardLinks = document.querySelectorAll('.dashboard-nav-link, .dashboard-mobile-link');
    const hashLinks = document.querySelectorAll('.dashboard-nav-link[href*="#"], .dashboard-mobile-link[href*="#"]');
    const themeToggleButtons = document.querySelectorAll('[data-dashboard-theme-toggle]');

    const updateThemeControls = () => {
        const isDark = document.documentElement.dataset.dashboardTheme === 'dark';

        themeToggleButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(isDark));
            const label = button.querySelector('[data-dashboard-theme-label]');
            const icon = button.querySelector('[data-dashboard-theme-icon]');

            if (label) label.textContent = isDark ? 'Light mode' : 'Night mode';
            if (icon) {
                icon.classList.toggle('fa-moon', !isDark);
                icon.classList.toggle('fa-sun', isDark);
            }
        });
    };

    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.dashboardTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.dashboardTheme = nextTheme;
            window.localStorage.setItem(dashboardThemeStorageKey, nextTheme);
            updateThemeControls();
        });
    });
    updateThemeControls();

    const setMenuOpen = (isOpen) => {
        if (!sidebar || !backdrop) {
            return;
        }

        sidebar.classList.toggle('is-open', isOpen);
        backdrop.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('overflow-hidden', isOpen);

        openButtons.forEach((button) => button.setAttribute('aria-expanded', String(isOpen)));
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => setMenuOpen(true));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => setMenuOpen(false));
    });

    backdrop?.addEventListener('click', () => setMenuOpen(false));

    sidebar?.querySelectorAll('a, button[type="submit"]').forEach((item) => {
        item.addEventListener('click', () => setMenuOpen(false));
    });

    const setActiveNavigationKey = (activeKey) => {
        if (!activeKey) {
            return;
        }

        dashboardLinks.forEach((link) => {
            const isActive = link.dataset.dashboardNavKey === activeKey;

            link.classList.toggle('is-active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const storedKeyForHash = (hash) => `mcare-dashboard-nav:${window.location.pathname}:${hash}`;

    // Use only one logical item for a hash, even when future placeholder tabs share a section.
    const setActiveHash = () => {
        const activeHash = window.location.hash;

        if (!activeHash) {
            return;
        }

        const matchingLinks = Array.from(hashLinks).filter((link) => {
            const url = new URL(link.href, window.location.href);

            return url.pathname === window.location.pathname && url.hash === activeHash;
        });

        if (matchingLinks.length === 0) {
            return;
        }

        const rememberedKey = window.sessionStorage.getItem(storedKeyForHash(activeHash));
        const rememberedLink = matchingLinks.find((link) => link.dataset.dashboardNavKey === rememberedKey);
        const activeKey = rememberedLink?.dataset.dashboardNavKey ?? matchingLinks[0].dataset.dashboardNavKey;

        setActiveNavigationKey(activeKey);
    };

    hashLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.href);
            const activeKey = link.dataset.dashboardNavKey;

            if (activeKey && url.hash) {
                window.sessionStorage.setItem(storedKeyForHash(url.hash), activeKey);
                setActiveNavigationKey(activeKey);
            }

            const target = url.hash ? document.getElementById(url.hash.slice(1)) : null;
            const isCurrentDocument = url.pathname === window.location.pathname && url.search === window.location.search;

            // Same-page dashboard tabs scroll smoothly without a full refresh or white blink.
            if (target && isCurrentDocument) {
                event.preventDefault();
                window.history.pushState({}, '', url.hash);
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setMenuOpen(false);
            }
        });
    });

    window.addEventListener('hashchange', setActiveHash);
    window.addEventListener('popstate', setActiveHash);
    setActiveHash();

    // Native details menus remain keyboard-friendly; close only when focus/click moves away.
    document.addEventListener('click', (event) => {
        accountMenus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        setMenuOpen(false);
        accountMenus.forEach((menu) => menu.removeAttribute('open'));
    });
});
