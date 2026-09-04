@php
    $layout = \App\Support\AccountPortal::dashboardLayoutFor($user);
@endphp

@extends($layout, ['title' => $roleLabel.' Help | MCARE'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Role-aware assistance</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Help for {{ $roleLabel }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Quick guidance for the actions available in your current portal.</p>
    </header>

    <div class="grid gap-5 md:grid-cols-3">
        @foreach ($topics as [$topicTitle, $description])
            <section class="dashboard-panel space-y-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-purple-50 text-purple-700"><x-dashboard-icon name="circle-check" class="h-5 w-5" /></span>
                <h2 class="text-lg font-bold text-slate-950">{{ $topicTitle }}</h2>
                <p class="text-sm leading-6 text-slate-600">{{ $description }}</p>
            </section>
        @endforeach
    </div>

    <section class="dashboard-panel flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-950">Still need help?</h2>
            <p class="mt-2 text-sm text-slate-600">Contact Mission Care Training Center and include your account email: {{ $user->email }}</p>
        </div>
        <a href="{{ route('account.settings') }}" class="secondary-action shrink-0">Account settings</a>
    </section>
</section>
@endsection
