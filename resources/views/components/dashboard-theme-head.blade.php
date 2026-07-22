<meta name="dashboard-security-event-url" content="{{ route('account.security-event') }}">
<script>
    try {
        const mcareTheme = window.localStorage.getItem('mcare-dashboard-theme') === 'dark' ? 'dark' : 'light';
        document.documentElement.dataset.dashboardTheme = mcareTheme;
        document.documentElement.style.colorScheme = mcareTheme;

        // Restore the desktop navigation choice before the dashboard paints so
        // a collapsed sidebar does not briefly flash open between pages.
        if (window.localStorage.getItem('mcare-dashboard-sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('dashboard-sidebar-collapsed');
        }
    } catch (error) {
        document.documentElement.dataset.dashboardTheme = 'light';
        document.documentElement.style.colorScheme = 'light';
    }
</script>
