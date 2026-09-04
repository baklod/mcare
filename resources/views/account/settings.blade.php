@php
    $layout = \App\Support\AccountPortal::dashboardLayoutFor($user);
@endphp

@extends($layout, ['title' => 'Account Settings | MCARE '.$roleLabel])

@section('content')
<section class="space-y-6">
    @if (session('saved') && $user->role === 'admin')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status" aria-live="polite" data-auto-dismiss="5000">{{ session('saved') }}</div>
    @endif

    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Account preferences</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Settings</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Manage the signed-in {{ strtolower($roleLabel) }} account, profile photo, display preference, and password.</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]">
        <div class="space-y-6">
            <section class="dashboard-panel space-y-4" data-profile-photo-form>
                <p class="dashboard-section-kicker">Signed-in account</p>
                <div class="flex items-center gap-4">
                    <x-user-avatar :user="$user" class="grid h-14 w-14 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800" />
                    <div class="min-w-0">
                        <h2 class="truncate text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                        <p class="mt-1 truncate text-sm text-slate-600">{{ $user->email }}</p>
                    </div>
                </div>
                <span class="inline-flex rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-800">{{ $roleLabel }}</span>

                <form method="POST" action="{{ route('account.avatar.update') }}" enctype="multipart/form-data" class="space-y-3 border-t border-slate-200 pt-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="avatar" class="block text-sm font-bold text-slate-700">Profile photo</label>
                        <p class="mt-1 text-xs leading-5 text-slate-500">JPG, PNG, or WEBP. Maximum 5MB. The photo is stored in public storage and used across MCARE dashboards.</p>
                        <input
                            id="avatar"
                            name="avatar"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                            class="form-field mt-3 text-sm"
                            data-profile-photo-input
                        >
                        @error('avatar') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="secondary-action w-full sm:w-auto">
                        <x-dashboard-icon name="cloud-arrow-up" class="h-4 w-4" />
                        <span>Save photo</span>
                    </button>
                </form>
            </section>

            <section id="preferences" class="dashboard-panel space-y-3">
                <p class="dashboard-section-kicker">Display</p>
                <h2 class="text-lg font-bold text-slate-950">Theme preference</h2>
                <p class="text-sm leading-6 text-slate-600">Night mode is stored only on this browser and can be changed anytime.</p>
                <button type="button" class="secondary-action w-full sm:w-auto" data-dashboard-theme-toggle aria-pressed="false">
                    <x-dashboard-icon name="moon" class="h-4 w-4" data-dashboard-theme-icon="moon" />
                    <x-dashboard-icon name="sun" class="hidden h-4 w-4" data-dashboard-theme-icon="sun" />
                    <span data-dashboard-theme-label>Night mode</span>
                </button>
            </section>

            <a href="{{ route('account.help') }}" class="dashboard-panel block space-y-2 transition hover:border-purple-300">
                <p class="dashboard-section-kicker">Need assistance?</p>
                <h2 class="text-lg font-bold text-slate-950">Open help center</h2>
                <p class="text-sm leading-6 text-slate-600">View guidance tailored to the {{ strtolower($roleLabel) }} portal.</p>
            </a>
        </div>

        <section id="change-password" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">Security</p>
            <h2 class="text-lg font-bold text-slate-950">Change password</h2>
            <p class="text-sm leading-6 text-slate-600">Use at least eight characters with both letters and numbers.</p>
            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')
                <label class="block text-sm font-bold text-slate-700">Current password
                    <input name="current_password" type="password" autocomplete="current-password" required class="form-field mt-2 text-base">
                    @error('current_password') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-bold text-slate-700">New password
                    <input name="password" type="password" autocomplete="new-password" required class="form-field mt-2 text-base">
                    @error('password') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-bold text-slate-700">Confirm new password
                    <input name="password_confirmation" type="password" autocomplete="new-password" required class="form-field mt-2 text-base">
                </label>
                <button type="submit" class="primary-action">
                    <x-dashboard-icon name="key" class="h-4 w-4" />
                    <span>Update password</span>
                </button>
            </form>
        </section>
    </div>
</section>
@endsection
