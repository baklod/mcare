@props([
    'logoutRoute',
    'roleLabel',
    'showAdminLogs' => false,
])

<p class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-400">{{ $roleLabel }} account</p>

@if ($showAdminLogs)
    <a href="{{ route('admin.logs.index') }}" class="dashboard-account-action">
        <x-dashboard-icon name="shield-halved" class="mr-3 w-4" />Admin logs
    </a>
@endif

<button type="button" class="dashboard-account-action" data-dashboard-theme-toggle aria-pressed="false">
    <x-dashboard-icon name="moon" class="mr-3 w-4" data-dashboard-theme-icon="moon" />
    <x-dashboard-icon name="sun" class="mr-3 hidden w-4" data-dashboard-theme-icon="sun" />
    <span data-dashboard-theme-label>Night mode</span>
</button>
<a href="{{ route('account.settings') }}" class="dashboard-account-action">
    <x-dashboard-icon name="gear" class="mr-3 w-4" />Settings
</a>
<a href="{{ route('account.settings') }}#change-password" class="dashboard-account-action">
    <x-dashboard-icon name="key" class="mr-3 w-4" />Change password
</a>
<a href="{{ route('account.help') }}" class="dashboard-account-action">
    <x-dashboard-icon name="circle-question" class="mr-3 w-4" />Help
</a>

<form method="POST" action="{{ $logoutRoute }}" class="mt-1 border-t border-slate-100 pt-1">
    @csrf
    <button type="submit" class="dashboard-account-action is-danger">
        <x-dashboard-icon name="arrow-right-from-bracket" class="mr-3 w-4" />Sign out
    </button>
</form>
