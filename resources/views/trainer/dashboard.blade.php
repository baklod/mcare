@extends('trainer.layouts.app', ['title' => 'Trainer Dashboard | MCARE'])

@section('content')
    @php
        $firstName = explode(' ', auth()->user()?->name ?? 'Trainer Angel')[0];

        $statCards = [
            [
                'label' => 'Total Trainings',
                'abbr' => 'MT',
                'value' => $stats['total_trainings'],
                'hint' => 'Active LMS modules',
                'tone' => 'bg-purple-50 text-purple-700 ring-purple-100',
            ],
            [
                'label' => 'Total Trainees',
                'abbr' => 'TR',
                'value' => $stats['total_trainees'],
                'hint' => 'Assigned learners',
                'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ],
            [
                'label' => 'Sessions Today',
                'abbr' => 'ST',
                'value' => $stats['sessions_today'],
                'hint' => 'Upcoming classes',
                'tone' => 'bg-amber-50 text-amber-700 ring-amber-100',
            ],
            [
                'label' => 'Avg. Trainee Progress',
                'abbr' => 'AP',
                'value' => $stats['average_progress'].'%',
                'hint' => 'This month',
                'tone' => 'bg-sky-50 text-sky-700 ring-sky-100',
            ],
        ];

        $statusBadgeClasses = [
            'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'In Progress' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'Not Started' => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];

        $announcementToneClasses = [
            'purple' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        ];
    @endphp

    <section class="space-y-8">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-purple-600">Trainer workspace</p>
                <h1 class="mt-2 text-4xl font-black leading-tight text-slate-950 sm:text-5xl">
                    Good morning, {{ $firstName }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Prepare modules, monitor learner progress, manage sessions, and keep certificate readiness moving.
                </p>
            </div>
            <div class="rounded-full border border-purple-100 bg-white px-5 py-3 text-sm font-black text-purple-700 shadow-sm">
                Caregiving NC II Trainer
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($statCards as $card)
                <article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-xl shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-purple-100/70">
                    <div class="flex items-center justify-between gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl text-sm font-black ring-1 {{ $card['tone'] }}">{{ $card['abbr'] }}</span>
                        <span class="rounded-full border border-purple-100 bg-white px-3 py-1 text-xs font-black text-purple-700">View</span>
                    </div>
                    <p class="mt-5 text-xs font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-black text-slate-950">{{ $card['value'] }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-500">{{ $card['hint'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
            <section id="training-overview" class="overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
                <div class="p-6 sm:p-7">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-purple-600">My Training Overview</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-950">Current LMS delivery</h2>
                        </div>
                        <a href="#resources" class="inline-flex w-fit items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-2.5 text-sm font-black text-purple-700 hover:bg-purple-50">
                            View Training Details
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_300px]">
                        <article class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-purple-800 via-purple-700 to-purple-500 p-6 text-white shadow-xl shadow-purple-200">
                            <div class="pointer-events-none absolute -right-16 -top-12 h-48 w-48 rounded-full bg-white/10"></div>
                            <div class="pointer-events-none absolute bottom-4 right-8 h-24 w-24 rounded-full bg-white/10"></div>
                            <p class="text-xs font-black uppercase tracking-wide text-purple-100">Current training</p>
                            <h3 class="mt-4 max-w-sm text-2xl font-black leading-tight">{{ $currentModule['title'] }}</h3>
                            <span class="mt-4 inline-flex rounded-full bg-white/15 px-4 py-2 text-xs font-black text-white ring-1 ring-white/15">{{ $currentModule['status'] }}</span>
                            <div class="mt-12">
                                <div class="flex items-center justify-between text-xs font-bold text-purple-100">
                                    <span>Lesson {{ $currentModule['completed_lessons'] }} of {{ $currentModule['lessons'] }}</span>
                                    <span>{{ $currentModule['progress'] }}%</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/25">
                                    <div class="h-full rounded-full bg-white" style="width: {{ $currentModule['progress'] }}%"></div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-100 bg-slate-50 p-6">
                            <p class="text-sm font-black text-slate-950">Progress Summary</p>
                            <div class="mt-6 grid place-items-center">
                                <div class="grid h-44 w-44 place-items-center rounded-full" style="background: conic-gradient(#7c3aed {{ $averageProgress }}%, #ede9fe 0);">
                                    <div class="grid h-32 w-32 place-items-center rounded-full bg-white text-center shadow-inner">
                                        <div>
                                            <p class="text-3xl font-black text-slate-950">{{ $averageProgress }}%</p>
                                            <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">Average<br>Completion</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-700">Completed</span>
                                    <span class="font-black text-slate-950">{{ $progressRows->where('status', 'Completed')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-700">In Progress</span>
                                    <span class="font-black text-slate-950">{{ $progressRows->where('status', 'In Progress')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-700">Not Started</span>
                                    <span class="font-black text-slate-950">{{ $progressRows->where('status', 'Not Started')->count() }}</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <aside id="today-schedule" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Today's Schedule</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Class sessions</h2>
                    </div>
                    <a href="#trainer-reports" class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700 hover:bg-purple-50">Calendar</a>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ($todaySessions as $session)
                        <article class="grid grid-cols-[86px_1fr] overflow-hidden rounded-3xl border border-slate-100 bg-white">
                            <div class="grid place-items-center bg-purple-50 px-4 py-5 text-center">
                                <p class="text-sm font-black leading-5 text-purple-700">{{ $session['time'] }}</p>
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-950">{{ $session['title'] }}</p>
                                        <span class="mt-2 inline-flex rounded-full bg-purple-50 px-3 py-1 text-xs font-black text-purple-700 ring-1 ring-purple-100">{{ $session['type'] }}</span>
                                    </div>
                                    <span class="text-xs font-black text-slate-400">{{ $session['duration'] }}</span>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-500">{{ $session['batch'] }} | {{ $session['room'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </aside>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
            <section id="trainee-progress" class="overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
                <div class="flex flex-col gap-3 border-b border-slate-100 p-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Trainee Progress Snapshot</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Assigned learner monitoring</h2>
                    </div>
                    <a href="#trainer-reports" class="inline-flex w-fit rounded-full border border-purple-200 bg-white px-5 py-2.5 text-sm font-black text-purple-700 hover:bg-purple-50">View all</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Trainee</th>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Training</th>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Progress</th>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($progressRows as $row)
                                @php $progress = min(100, max(0, $row['progress'])); @endphp
                                <tr class="hover:bg-purple-50/40">
                                    <td class="px-6 py-5">
                                        <p class="font-black text-slate-950">{{ $row['name'] }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $row['email'] }}</p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-bold text-slate-700">{{ $row['training'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $row['schedule'] }}</p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex min-w-44 items-center gap-3">
                                            <span class="w-10 text-sm font-black text-slate-700">{{ $progress }}%</span>
                                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-purple-600" style="width: {{ $progress }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $statusBadgeClasses[$row['status']] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-14 text-center">
                                        <p class="text-lg font-black text-slate-950">No assigned trainees yet</p>
                                        <p class="mt-2 text-sm text-slate-500">Approved or pre-enlistment learners will appear here for trainer monitoring.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Announcements</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Trainer notices</h2>
                    </div>
                    <span class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700">View all</span>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ($announcements as $announcement)
                        <article class="grid grid-cols-[56px_1fr] gap-4">
                            <span class="grid h-14 w-14 place-items-center rounded-2xl text-xs font-black ring-1 {{ $announcementToneClasses[$announcement['tone']] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                {{ strtoupper(substr($announcement['title'], 0, 2)) }}
                            </span>
                            <div>
                                <p class="font-black text-slate-950">{{ $announcement['title'] }}</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">{{ $announcement['body'] }}</p>
                                <p class="mt-2 text-xs font-semibold text-slate-400">{{ $announcement['date'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </aside>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section id="resources" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60 xl:col-span-2">
                <p class="text-xs font-black uppercase tracking-wide text-purple-600">Resources and Content Security</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Module readiness checklist</h2>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ($modules as $module)
                        <article class="rounded-3xl border border-slate-100 bg-slate-50 p-5">
                            <p class="font-black leading-6 text-slate-950">{{ $module['title'] }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">{{ $module['description'] }}</p>
                            <div class="mt-5 h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-purple-600" style="width: {{ $module['progress'] }}%"></div>
                            </div>
                            <p class="mt-3 text-xs font-black uppercase tracking-wide text-purple-700">{{ $module['status'] }} | {{ $module['progress'] }}%</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="trainer-reports" class="rounded-[2rem] border border-purple-100 bg-purple-700 p-6 text-white shadow-xl shadow-purple-200">
                <p class="text-xs font-black uppercase tracking-wide text-purple-100">Trainer Reports</p>
                <h2 class="mt-2 text-2xl font-black">Teaching impact summary</h2>
                <p class="mt-4 text-sm leading-6 text-purple-100">
                    Use this space later for attendance, assessment marks, certificate endorsements, and module completion exports.
                </p>
                <div class="mt-8 grid grid-cols-2 gap-3">
                    <div class="rounded-3xl bg-white/15 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-black uppercase tracking-wide text-purple-100">Ready</p>
                        <p class="mt-2 text-3xl font-black">{{ $progressRows->where('status', 'Completed')->count() }}</p>
                    </div>
                    <div class="rounded-3xl bg-white/15 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-black uppercase tracking-wide text-purple-100">Monitoring</p>
                        <p class="mt-2 text-3xl font-black">{{ $progressRows->where('status', 'In Progress')->count() }}</p>
                    </div>
                </div>
            </section>
        </div>
    </section>
@endsection
