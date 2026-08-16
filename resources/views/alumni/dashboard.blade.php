@extends('alumni.layouts.app', ['title' => 'Career Hub | MCARE Alumni'])

@section('content')
<section class="space-y-6">
    <header class="rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-50 via-white to-white p-6 sm:p-8">
        <p class="dashboard-section-kicker">Alumni Career Hub</p>
        <div class="mt-3 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="max-w-3xl text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Your next caregiving opportunity starts here.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">Browse opportunities shared through MCARE and keep your professional journey connected to the training center.</p>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-white bg-white/80 px-4 py-3 shadow-sm">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-700"><x-dashboard-icon name="bell" class="h-4 w-4" /></span>
                <span><span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Unread updates</span><span class="mt-1 block text-xl font-black text-slate-950">{{ $unreadNotifications }}</span></span>
            </div>
        </div>
    </header>

    <section aria-labelledby="career-board-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Opportunities</p><h2 id="career-board-title" class="mt-1 text-2xl font-black text-slate-950">Open roles for MCARE alumni</h2></div>
            <a href="{{ route('notifications.index') }}" class="text-sm font-bold text-purple-700 hover:text-purple-900">Open notifications</a>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @forelse ($jobs as $job)
                <article class="dashboard-panel flex h-full flex-col p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700"><x-dashboard-icon name="briefcase" class="h-5 w-5" /></span><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Open opportunity</span></div>
                    <h3 class="mt-5 text-xl font-black text-slate-950">{{ $job->title }}</h3>
                    <p class="mt-2 text-sm font-bold text-purple-700">{{ $job->employer }}</p>
                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold text-slate-500">
                        @if ($job->location)<span><x-dashboard-icon name="location-dot" class="mr-1 inline h-3.5 w-3.5" />{{ $job->location }}</span>@endif
                        @if ($job->employment_type)<span><x-dashboard-icon name="clipboard-list" class="mr-1 inline h-3.5 w-3.5" />{{ $job->employmentTypeLabel() }}</span>@endif
                        @if ($job->application_deadline)<span><x-dashboard-icon name="calendar-days" class="mr-1 inline h-3.5 w-3.5" />Apply by {{ $job->application_deadline->format('M d, Y g:i A') }}</span>@endif
                    </div>
                    <p class="mt-5 flex-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ str($job->description)->limit(280) }}</p>
                    @if ($job->requirements)<p class="mt-4 border-t border-slate-100 pt-4 text-sm leading-6 text-slate-600"><span class="font-bold text-slate-900">Requirements:</span> {{ str($job->requirements)->limit(220) }}</p>@endif
                    <div class="mt-6 flex flex-wrap gap-2">
                        @if ($job->application_url)
                            <a href="{{ $job->application_url }}" target="_blank" rel="noopener noreferrer" class="primary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="arrow-up-right-from-square" class="h-4 w-4" />Open application link</a>
                        @elseif ($job->application_email)
                            <a href="mailto:{{ $job->application_email }}" class="primary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="arrow-up-right-from-square" class="h-4 w-4" />Email employer</a>
                        @else
                            <span class="secondary-action inline-flex items-center text-sm">Contact MCARE for details</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="dashboard-panel py-16 text-center lg:col-span-2"><x-dashboard-icon name="briefcase" class="mx-auto h-9 w-9 text-slate-300" /><h3 class="mt-4 text-lg font-bold text-slate-900">No open opportunities yet</h3><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">MCARE will post verified partner opportunities here when they are available.</p></div>
            @endforelse
        </div>

        @if ($jobs->hasPages())<div class="mt-6">{{ $jobs->links() }}</div>@endif
    </section>
</section>
@endsection
