@extends('trainer.layouts.app')

@section('title', 'Teaching Day')

@section('content')
    @php
        $trainerDisplayName = trim(auth()->user()?->name ?? 'Trainer');
        $followUpCount = $learnerFollowUps->where('needs_action', true)->count();
    @endphp

    <div class="mx-auto max-w-7xl space-y-8">
        <header class="flex flex-col gap-5 border-b border-stone-200 pb-7 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-violet-700">Teaching day</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">Welcome back, {{ $trainerDisplayName }}</h1>
                <p class="mt-2 text-base text-stone-600">Here’s your teaching day, organized around what needs your attention.</p>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="inline-flex items-center gap-2 font-medium text-stone-700">
                    <x-dashboard-icon name="calendar" class="text-violet-700" />
                    {{ now()->format('l, F j, Y') }}
                </span>
                <a href="{{ route('trainer.dashboard') }}" class="inline-flex min-h-10 items-center justify-center border border-stone-300 bg-white px-4 font-semibold text-stone-800 transition hover:border-violet-400 hover:text-violet-800">
                    Today
                </a>
            </div>
        </header>

        <section id="teaching-timeline" class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-stone-950">Today’s timeline</h2>
                        <p class="mt-1 text-sm text-stone-600">A clear view of your preparation, live delivery, and follow-through.</p>
                    </div>
                    <span class="hidden text-sm font-medium text-stone-500 sm:inline">{{ $teachingTimeline->count() }} items</span>
                </div>

                <ol class="border-l border-stone-300">
                    @forelse ($teachingTimeline as $item)
                        @if ($item['state'] === 'current')
                            <li class="relative ml-6 border border-violet-200 bg-white p-5 sm:ml-8 sm:p-6">
                                <span class="absolute -left-[2.05rem] top-7 flex h-4 w-4 items-center justify-center rounded-full bg-violet-700 ring-4 ring-[#faf9f7]" aria-hidden="true"></span>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <time class="text-sm font-bold text-stone-950">{{ $item['time'] }}</time>
                                            <span class="inline-flex items-center gap-1.5 bg-violet-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-violet-800">
                                                <x-dashboard-icon name="circle-play" /> Live session
                                            </span>
                                        </div>
                                        <h3 class="mt-3 text-xl font-bold text-stone-950">{{ $item['title'] }}</h3>
                                        <p class="mt-2 text-sm text-stone-600">{{ $item['duration'] }} · {{ $item['training'] }}</p>
                                    </div>
                                    <span class="inline-flex w-fit items-center gap-2 border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800">
                                        <x-dashboard-icon name="signal" /> In progress
                                    </span>
                                </div>

                                <div class="mt-6 grid border-t border-stone-200 pt-5 sm:grid-cols-2">
                                    <div class="border-b border-stone-200 pb-5 sm:border-b-0 sm:border-r sm:pr-5">
                                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">Attendance</p>
                                        <p class="mt-2 text-2xl font-bold text-stone-950">{{ $stats['total_trainees'] ?? 0 }} <span class="text-sm font-medium text-stone-500">learners assigned</span></p>
                                        <a href="#learner-follow-up" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-violet-800 hover:text-violet-950">
                                            View learner follow-up <x-dashboard-icon name="arrow-right" />
                                        </a>
                                    </div>
                                    <div class="pt-5 sm:pl-5 sm:pt-0">
                                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">Session room</p>
                                        <p class="mt-2 font-semibold text-stone-950">{{ $item['room'] }}</p>
                                        <p class="mt-1 text-sm text-stone-600">Keep the learner checklist open while you deliver.</p>
                                        <a href="{{ route('trainer.sessions') }}" class="mt-3 inline-flex min-h-10 items-center justify-center bg-violet-700 px-4 text-sm font-bold text-white transition hover:bg-violet-800">
                                            Open sessions
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @else
                            <li class="relative ml-6 border-b border-stone-200 py-5 sm:ml-8 sm:py-6">
                                <span class="absolute -left-[2.05rem] top-8 flex h-4 w-4 rounded-full ring-4 ring-[#faf9f7] {{ $item['state'] === 'complete' ? 'bg-emerald-600' : 'bg-stone-300' }}" aria-hidden="true"></span>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-4">
                                        <time class="w-14 shrink-0 pt-0.5 text-sm font-bold text-stone-950">{{ $item['time'] }}</time>
                                        <div>
                                            <h3 class="font-bold text-stone-900">{{ $item['title'] }}</h3>
                                            <p class="mt-1 text-sm text-stone-600">{{ $item['duration'] }} · {{ $item['training'] }}</p>
                                        </div>
                                    </div>
                                    <span class="w-fit text-sm font-semibold {{ $item['state'] === 'complete' ? 'text-emerald-700' : 'text-stone-500' }}">{{ $item['label'] }}</span>
                                </div>
                            </li>
                        @endif
                    @empty
                        <li class="ml-6 border border-stone-200 bg-white p-6 sm:ml-8"><p class="font-bold text-stone-950">No session scheduled today.</p><p class="mt-2 text-sm text-stone-600">Open Sessions to review the full month generated from the admin schedule.</p><a href="{{ route('trainer.sessions') }}" class="mt-4 inline-flex bg-violet-700 px-4 py-2 text-sm font-bold text-white">View monthly calendar</a></li>
                    @endforelse
                </ol>
            </div>

            <aside id="learner-follow-up" class="border border-stone-200 bg-white">
                <div class="border-b border-stone-200 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-stone-950">Learner follow-up</h2>
                            <p class="mt-1 text-sm text-stone-600">Small actions that keep delivery moving.</p>
                        </div>
                        <span class="flex h-8 min-w-8 items-center justify-center bg-amber-100 px-2 text-sm font-bold text-amber-900">{{ $followUpCount }}</span>
                    </div>
                </div>

                @if ($learnerFollowUps->isNotEmpty())
                    <ul class="divide-y divide-stone-200">
                        @foreach ($learnerFollowUps as $learner)
                            <li class="p-5">
                                <div class="flex gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-900 text-sm font-bold text-white" aria-label="{{ $learner['name'] }}">{{ $learner['initial'] }}</span>
                                    <div class="min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="font-bold text-stone-950">{{ $learner['name'] }}</p>
                                            @if ($learner['needs_action'])
                                                <span class="shrink-0 text-xs font-bold uppercase tracking-wide text-amber-800">Action</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-stone-600">{{ $learner['training'] }}</p>
                                        <p class="mt-3 text-sm font-medium text-stone-800">{{ $learner['action'] }}</p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="p-5">
                        <x-dashboard-icon name="circle-check" class="text-2xl text-emerald-700" />
                        <p class="mt-3 font-bold text-stone-950">No learners need follow-up yet.</p>
                        <p class="mt-1 text-sm leading-6 text-stone-600">As learner activity is recorded, any next steps will appear here.</p>
                    </div>
                @endif

                <div class="border-t border-stone-200 p-5" aria-labelledby="system-notifications-title">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">From admin</p>
                            <h2 id="system-notifications-title" class="mt-1 text-lg font-bold text-stone-950">System notifications</h2>
                        </div>
                        <x-dashboard-icon name="bell" class="mt-1 text-violet-700" />
                    </div>

                    @if ($systemNotifications->isNotEmpty())
                        <ul class="mt-4 space-y-3">
                            @foreach ($systemNotifications as $notification)
                                <li class="border-l-2 border-violet-300 pl-3">
                                    <p class="text-sm font-semibold leading-5 text-stone-900">{{ $notification['title'] }}</p>
                                    <p class="mt-1 text-xs text-stone-500">{{ $notification['actor'] }} · {{ $notification['occurred_at'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-4 text-sm leading-6 text-stone-600">No new schedule, enrollment, or module notices.</p>
                    @endif
                </div>
            </aside>
        </section>

        <section id="modules" class="border border-stone-200 bg-white p-5 sm:p-6" aria-labelledby="delivery-snapshot-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-violet-700">Learning delivery</p>
                    <h2 id="delivery-snapshot-title" class="mt-2 text-xl font-bold text-stone-950">Delivery snapshot</h2>
                    <p class="mt-1 text-sm text-stone-600">Keep this page focused on today. Manage files and audiences from Resources.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('trainer.resources') }}" class="inline-flex min-h-10 items-center justify-center bg-violet-700 px-4 text-sm font-bold text-white hover:bg-violet-800">Manage resources</a>
                    <a href="{{ route('trainer.trainees') }}" class="inline-flex min-h-10 items-center justify-center border border-stone-300 px-4 text-sm font-bold text-stone-800 hover:border-violet-400 hover:text-violet-800">View trainees</a>
                </div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="border border-stone-200 bg-stone-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-stone-500">Published modules</p><p class="mt-2 text-2xl font-bold text-stone-950">{{ $moduleCount }}</p></div>
                <div class="border border-stone-200 bg-stone-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-stone-500">Assigned learners</p><p class="mt-2 text-2xl font-bold text-stone-950">{{ $stats['total_trainees'] ?? 0 }}</p></div>
                <div class="border border-stone-200 bg-stone-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-stone-500">Sessions today</p><p class="mt-2 text-2xl font-bold text-stone-950">{{ $stats['sessions_today'] ?? 0 }}</p></div>
            </div>
        </section>
    </div>
@endsection
