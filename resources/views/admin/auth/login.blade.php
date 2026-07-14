@extends('admin.layouts.app', ['title' => 'Admin Login | MCARE'])

@section('content')
    <section class="admin-login-card mx-auto grid max-w-5xl grid-cols-1 overflow-hidden rounded-3xl border border-purple-100 bg-white shadow-xl shadow-purple-100/40 lg:grid-cols-[1fr_420px]">
        <div class="admin-login-promo border-b border-slate-200 bg-slate-50 p-8 sm:p-10 lg:border-b-0 lg:border-r">
            <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 ring-1 ring-purple-100">
                Staff access
            </div>
            <h1 class="mt-7 max-w-xl text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">
                Review MCARE enrollment applications.
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                Sign in with an admin account to view submitted learner profiles and mark applications for pre-enlistment, approval, or denial.
            </p>
            <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 bg-white p-5">
                    <p class="text-2xl font-bold text-slate-900">Queue</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Submitted forms</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-5">
                    <p class="text-2xl font-bold text-slate-900">Review</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Applicant details</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-5">
                    <p class="text-2xl font-bold text-slate-900">Decide</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Status updates</p>
                </div>
            </div>
        </div>

        <div class="admin-login-form bg-white p-8 sm:p-10">
            @include('auth.partials.current-account', ['activeUser' => $activeUser ?? auth()->user()])

            <h2 class="text-2xl font-bold text-slate-900">Admin login</h2>
            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                    @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Password</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                    @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                    Remember this device
                </label>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                    Sign in
                </button>
            </form>
        </div>
    </section>
@endsection
