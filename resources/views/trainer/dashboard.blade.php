@extends('trainer.layouts.app')

@section('title', 'Teaching Day')

@section('content')
    @php
        $trainerDisplayName = trim(auth()->user()?->name ?? 'Trainer');
        $followUpCount = $learnerFollowUps->where('needs_action', true)->count();
        $moduleStatusClasses = [
            'Complete' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            'In progress' => 'bg-violet-50 text-violet-800 ring-violet-200',
            'Upcoming' => 'bg-stone-100 text-stone-700 ring-stone-200',
        ];
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
                    <i class="fa-regular fa-calendar text-violet-700" aria-hidden="true"></i>
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
                    @foreach ($teachingTimeline as $item)
                        @if ($item['state'] === 'current')
                            <li class="relative ml-6 border border-violet-200 bg-white p-5 sm:ml-8 sm:p-6">
                                <span class="absolute -left-[2.05rem] top-7 flex h-4 w-4 items-center justify-center rounded-full bg-violet-700 ring-4 ring-[#faf9f7]" aria-hidden="true"></span>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <time class="text-sm font-bold text-stone-950">{{ $item['time'] }}</time>
                                            <span class="inline-flex items-center gap-1.5 bg-violet-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-violet-800">
                                                <i class="fa-solid fa-circle-play" aria-hidden="true"></i> Live session
                                            </span>
                                        </div>
                                        <h3 class="mt-3 text-xl font-bold text-stone-950">{{ $item['title'] }}</h3>
                                        <p class="mt-2 text-sm text-stone-600">{{ $item['duration'] }} · {{ $item['training'] }}</p>
                                    </div>
                                    <span class="inline-flex w-fit items-center gap-2 border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800">
                                        <i class="fa-solid fa-signal" aria-hidden="true"></i> In progress
                                    </span>
                                </div>

                                <div class="mt-6 grid border-t border-stone-200 pt-5 sm:grid-cols-2">
                                    <div class="border-b border-stone-200 pb-5 sm:border-b-0 sm:border-r sm:pr-5">
                                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">Attendance</p>
                                        <p class="mt-2 text-2xl font-bold text-stone-950">{{ $stats['total_trainees'] ?? 0 }} <span class="text-sm font-medium text-stone-500">learners assigned</span></p>
                                        <a href="#learner-follow-up" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-violet-800 hover:text-violet-950">
                                            View learner follow-up <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                    <div class="pt-5 sm:pl-5 sm:pt-0">
                                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">Session room</p>
                                        <p class="mt-2 font-semibold text-stone-950">{{ $item['room'] }}</p>
                                        <p class="mt-1 text-sm text-stone-600">Keep the learner checklist open while you deliver.</p>
                                        <a href="#module-checklist" class="mt-3 inline-flex min-h-10 items-center justify-center bg-violet-700 px-4 text-sm font-bold text-white transition hover:bg-violet-800">
                                            Open session
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
                    @endforeach
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
                        <i class="fa-regular fa-circle-check text-2xl text-emerald-700" aria-hidden="true"></i>
                        <p class="mt-3 font-bold text-stone-950">No learners need follow-up yet.</p>
                        <p class="mt-1 text-sm leading-6 text-stone-600">As learner activity is recorded, any next steps will appear here.</p>
                    </div>
                @endif
            </aside>
        </section>

        <section id="module-checklist" class="border border-stone-200 bg-white">
            <div class="flex flex-col gap-4 border-b border-stone-200 p-5 sm:flex-row sm:items-end sm:justify-between sm:p-6">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-violet-700">Training delivery</p>
                    <h2 class="mt-2 text-xl font-bold text-stone-950">Module checklist</h2>
                    <p class="mt-1 text-sm text-stone-600">Track the modules you are responsible for today.</p>
                </div>
                <a id="resources" href="#teaching-timeline" class="inline-flex min-h-10 items-center gap-2 self-start text-sm font-bold text-violet-800 hover:text-violet-950 sm:self-auto">
                    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i> Back to timeline
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-left text-sm">
                    <thead class="border-b border-stone-200 bg-stone-50 text-xs font-bold uppercase tracking-[0.12em] text-stone-500">
                        <tr>
                            <th scope="col" class="px-5 py-4 sm:px-6">Module</th>
                            <th scope="col" class="px-5 py-4">Training</th>
                            <th scope="col" class="px-5 py-4">Progress</th>
                            <th scope="col" class="px-5 py-4 sm:px-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse ($modules as $module)
                            <tr class="text-stone-700">
                                <td class="px-5 py-4 font-bold text-stone-950 sm:px-6">{{ $module['title'] }}</td>
                                <td class="px-5 py-4">{{ $module['training'] }}</td>
                                <td class="px-5 py-4">{{ $module['progress'] }}%</td>
                                <td class="px-5 py-4 sm:px-6">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $moduleStatusClasses[$module['status']] ?? 'bg-stone-100 text-stone-700 ring-stone-200' }}">{{ $module['status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-stone-600 sm:px-6">Your assigned modules will appear here when delivery is scheduled.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
