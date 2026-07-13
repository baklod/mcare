@props([
    'logoutRoute',
    'roleLabel',
    'showAdminLogs' => false,
])

<p class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-400">{{ $roleLabel }} account</p>

@if ($showAdminLogs)
    <a href="{{ route('admin.logs.index') }}" class="dashboard-account-action">
        <i class="fa-solid fa-shield-halved mr-3 w-4 text-center" aria-hidden="true"></i>Admin logs
    </a>
@endif

<button type="button" class="dashboard-account-action" data-dashboard-theme-toggle aria-pressed="false">
    <i class="fa-solid fa-moon mr-3 w-4 text-center" data-dashboard-theme-icon aria-hidden="true"></i>
    <span data-dashboard-theme-label>Night mode</span>
</button>
<a href="{{ route('account.settings') }}" class="dashboard-account-action">
    <i class="fa-solid fa-gear mr-3 w-4 text-center" aria-hidden="true"></i>Settings
</a>
<a href="{{ route('account.settings') }}#change-password" class="dashboard-account-action">
    <i class="fa-solid fa-key mr-3 w-4 text-center" aria-hidden="true"></i>Change password
</a>
<a href="{{ route('account.help') }}" class="dashboard-account-action">
    <i class="fa-solid fa-circle-question mr-3 w-4 text-center" aria-hidden="true"></i>Help
</a>

<form method="POST" action="{{ $logoutRoute }}" class="mt-1 border-t border-slate-100 pt-1">
    @csrf
    <button type="submit" class="dashboard-account-action is-danger">
        <i class="fa-solid fa-arrow-right-from-bracket mr-3 w-4 text-center" aria-hidden="true"></i>Sign out
    </button>
</form>
