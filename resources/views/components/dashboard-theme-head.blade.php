<script>
    try {
        document.documentElement.dataset.dashboardTheme = window.localStorage.getItem('mcare-dashboard-theme') === 'dark' ? 'dark' : 'light';
    } catch (error) {
        document.documentElement.dataset.dashboardTheme = 'light';
    }
</script>
