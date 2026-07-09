@extends('trainer.layouts.app', ['title' => 'Trainer Login | MCARE'])

@section('content')
    <section class="mx-auto grid max-w-5xl grid-cols-1 overflow-hidden rounded-[2rem] border border-purple-100 bg-white shadow-xl shadow-purple-100/40 lg:grid-cols-[1fr_420px]">
        <div class="bg-gradient-to-br from-purple-50 via-white to-slate-50 p-8 sm:p-10">
            <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 ring-1 ring-purple-100">
                Trainer access
            </div>
            <h1 class="mt-7 max-w-xl text-4xl font-black leading-tight text-slate-950 sm:text-5xl">
                Manage learning sessions and trainee progress.
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                Sign in with a trainer account to monitor assigned learners, prepare modules, review progress, and coordinate upcoming class sessions.
            </p>
            <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 bg-white p-5">
                    <p class="text-2xl font-black text-slate-950">Modules</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">LMS materials</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-5">
                    <p class="text-2xl font-black text-slate-950">Progress</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Learner status</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-5">
                    <p class="text-2xl font-black text-slate-950">Schedule</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Class sessions</p>
                </div>
            </div>
        </div>

        <div class="p-8 sm:p-10">
            <h2 class="text-2xl font-black text-slate-950">Trainer login</h2>
            <form method="POST" action="{{ route('trainer.login.store') }}" class="mt-7 space-y-5">
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

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                    Sign in
                </button>
            </form>
        </div>
    </section>
@endsection
