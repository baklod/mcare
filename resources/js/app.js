import './bootstrap';

const dashboardThemeStorageKey = 'mcare-dashboard-theme';

const readDashboardTheme = () => {
    try {
        return window.localStorage.getItem(dashboardThemeStorageKey) === 'dark' ? 'dark' : 'light';
    } catch (error) {
        // Light is the safe default when storage is unavailable or blocked.
        return 'light';
    }
};

const applyDashboardTheme = (theme) => {
    const resolvedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.dashboardTheme = resolvedTheme;
    document.documentElement.style.colorScheme = resolvedTheme;
};

applyDashboardTheme(readDashboardTheme());

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.remove('dashboard-navigating');

    const sidebar = document.querySelector('[data-dashboard-sidebar]');
    const backdrop = document.querySelector('[data-dashboard-backdrop]');
    const openButtons = document.querySelectorAll('[data-dashboard-menu-open]');
    const closeButtons = document.querySelectorAll('[data-dashboard-menu-close]');
    const accountMenus = document.querySelectorAll('[data-dashboard-account]');
    const dashboardLinks = document.querySelectorAll('.dashboard-nav-link, .dashboard-mobile-link');
    const hashLinks = document.querySelectorAll('.dashboard-nav-link[href*="#"], .dashboard-mobile-link[href*="#"]');
    const themeToggleButtons = document.querySelectorAll('[data-dashboard-theme-toggle]');
    const prefetchLinks = document.querySelectorAll('a[data-dashboard-prefetch]');
    const dashboardMain = document.querySelector('.dashboard-main');
    const protectedViewer = document.querySelector('[data-protected-module-viewer]');

    const updateThemeControls = () => {
        const isDark = document.documentElement.dataset.dashboardTheme === 'dark';

        themeToggleButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(isDark));
            const label = button.querySelector('[data-dashboard-theme-label]');
            const moonIcon = button.querySelector('[data-dashboard-theme-icon="moon"]');
            const sunIcon = button.querySelector('[data-dashboard-theme-icon="sun"]');

            if (label) label.textContent = isDark ? 'Light mode' : 'Night mode';
            moonIcon?.classList.toggle('hidden', isDark);
            sunIcon?.classList.toggle('hidden', !isDark);
        });
    };

    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.dashboardTheme === 'dark' ? 'light' : 'dark';
            applyDashboardTheme(nextTheme);

            try {
                // One shared key carries the user's choice across every role portal.
                window.localStorage.setItem(dashboardThemeStorageKey, nextTheme);
            } catch (error) {
                // The current page can still change theme when storage is disabled.
            }

            updateThemeControls();
        });
    });
    updateThemeControls();

    if (protectedViewer) {
        const notice = protectedViewer.querySelector('[data-protected-viewer-notice]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        let noticeTimer = null;

        const reportRestrictedAction = (eventName) => {
            const url = protectedViewer.dataset.securityEventUrl;
            if (!url || !csrfToken) return;

            window.fetch(url, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ event: eventName }),
            }).catch(() => {});
        };

        const showRestrictedNotice = (message) => {
            if (!notice) return;
            notice.textContent = message;
            notice.classList.remove('hidden');
            window.clearTimeout(noticeTimer);
            noticeTimer = window.setTimeout(() => notice.classList.add('hidden'), 3500);
        };

        protectedViewer.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            showRestrictedNotice('Right-click is disabled inside the protected module viewer.');
            reportRestrictedAction('context_menu');
        });

        protectedViewer.addEventListener('dragstart', (event) => event.preventDefault());

        document.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey)) return;
            const key = event.key.toLowerCase();

            if (key === 'p' || key === 's') {
                event.preventDefault();
                showRestrictedNotice(key === 'p'
                    ? 'Printing is disabled for protected learning materials.'
                    : 'Saving protected learning materials is disabled.');
                reportRestrictedAction(key === 'p' ? 'print_shortcut' : 'save_shortcut');
            }
        });

        window.addEventListener('beforeprint', () => {
            document.documentElement.classList.add('protected-module-printing');
            reportRestrictedAction('before_print');
        });
        window.addEventListener('afterprint', () => document.documentElement.classList.remove('protected-module-printing'));
    }

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

    let dashboardScrollFrame = null;
    const scrollDashboardTo = (target) => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const startPosition = window.scrollY;
        const targetPosition = Math.max(0, target.getBoundingClientRect().top + startPosition - 96);
        const distance = targetPosition - startPosition;

        if (reduceMotion || Math.abs(distance) < 8) {
            window.scrollTo({ top: targetPosition, left: 0 });
            return;
        }

        if (dashboardScrollFrame) {
            window.cancelAnimationFrame(dashboardScrollFrame);
        }

        document.documentElement.classList.add('dashboard-scroll-in-progress');
        const duration = 240;
        let startedAt = null;
        const animate = (timestamp) => {
            startedAt ??= timestamp;
            const progress = Math.min((timestamp - startedAt) / duration, 1);
            const easedProgress = 1 - Math.pow(1 - progress, 3);

            window.scrollTo({ top: startPosition + distance * easedProgress, left: 0 });

            if (progress < 1) {
                dashboardScrollFrame = window.requestAnimationFrame(animate);
            } else {
                dashboardScrollFrame = null;
                document.documentElement.classList.remove('dashboard-scroll-in-progress');
            }
        };

        dashboardScrollFrame = window.requestAnimationFrame(animate);
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
                scrollDashboardTo(target);
                setMenuOpen(false);
            }
        });
    });

    window.addEventListener('hashchange', setActiveHash);
    window.addEventListener('popstate', setActiveHash);
    setActiveHash();

    const prefetchedUrls = new Set();
    const prefetchDashboardPage = (link) => {
        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || url.href === window.location.href || prefetchedUrls.has(url.href)) {
            return;
        }

        prefetchedUrls.add(url.href);
        const hint = document.createElement('link');
        hint.rel = 'prefetch';
        hint.href = url.href;
        hint.as = 'document';
        document.head.append(hint);
    };

    prefetchLinks.forEach((link) => {
        link.addEventListener('pointerenter', () => prefetchDashboardPage(link), { once: true });
        link.addEventListener('focus', () => prefetchDashboardPage(link), { once: true });
        link.addEventListener('touchstart', () => prefetchDashboardPage(link), { once: true, passive: true });

        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.href);
            const isModifiedClick = event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
            const isSameDocumentHash = url.pathname === window.location.pathname
                && url.search === window.location.search
                && url.hash;

            if (isModifiedClick || url.origin !== window.location.origin || isSameDocumentHash) {
                return;
            }

            document.documentElement.classList.add('dashboard-navigating');
            dashboardMain?.setAttribute('aria-busy', 'true');
        });
    });

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

window.addEventListener('pageshow', () => {
    applyDashboardTheme(readDashboardTheme());
    document.documentElement.classList.remove('dashboard-navigating');
    document.querySelector('.dashboard-main')?.removeAttribute('aria-busy');
});

window.addEventListener('storage', (event) => {
    if (event.key === dashboardThemeStorageKey) {
        applyDashboardTheme(readDashboardTheme());
    }
});
