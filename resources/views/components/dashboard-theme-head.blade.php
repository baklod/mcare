<meta name="dashboard-security-event-url" content="{{ route('account.security-event') }}">
<script>
    try {
        const mcareTheme = window.localStorage.getItem('mcare-dashboard-theme') === 'dark' ? 'dark' : 'light';
        document.documentElement.dataset.dashboardTheme = mcareTheme;
        document.documentElement.style.colorScheme = mcareTheme;
    } catch (error) {
        document.documentElement.dataset.dashboardTheme = 'light';
        document.documentElement.style.colorScheme = 'light';
    }
</script>
