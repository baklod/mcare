@extends('admin.layouts.app', ['title' => 'Admin Operations Console | MCARE'])

@section('content')
    @php
        $statusBadgeClasses = [
            'profile_submitted' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'pre_enlistment' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $paymentBadgeClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $overviewCards = [
            [
                'label' => 'Pending Applications',
                'abbr' => 'PA',
                'value' => $stats['pending_applications'],
                'hint' => $stats['pre_enlistment'].' in pre-enlistment',
                'tone' => 'bg-purple-50 text-purple-700 ring-purple-100',
            ],
            [
                'label' => 'Documents to Verify',
                'abbr' => 'DV',
                'value' => $documentsToVerify,
                'hint' => 'Checklist review queue',
                'tone' => 'bg-sky-50 text-sky-700 ring-sky-100',
            ],
            [
                'label' => 'Payments Today',
                'abbr' => 'PT',
                'value' => $paymentsToday,
                'hint' => ($paymentCounts['onsite_pending'] ?? 0).' on-site due',
                'tone' => 'bg-amber-50 text-amber-700 ring-amber-100',
            ],
            [
                'label' => 'Certificates Ready',
                'abbr' => 'CR',
                'value' => $certificatesReady,
                'hint' => 'Approved and paid',
                'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ],
        ];
    @endphp

    <section class="space-y-8">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
            <div class="overflow-hidden rounded-[2rem] border border-purple-100 bg-white shadow-xl shadow-purple-100/40">
                <div class="relative isolate p-7 sm:p-8">
                    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-40 bg-gradient-to-b from-purple-100/70 via-purple-50/50 to-transparent"></div>
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <span class="inline-flex rounded-full bg-purple-50 px-4 py-2 text-xs font-black uppercase tracking-wide text-purple-700 ring-1 ring-purple-100">
                                Mission Care Training Center
                            </span>
                            <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight text-slate-950 sm:text-5xl">
                                Admin Operations Console
                            </h1>
                            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                                Monitor enrollment decisions, document verification, payments, batch schedules, certificate readiness, LMS progress, and admin security activity from one place.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:min-w-80">
                            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-purple-200 transition hover:bg-purple-700">
                                Review Queue
                            </a>
                            <a href="{{ route('admin.schedules.index') }}" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-3 text-sm font-black text-purple-700 transition hover:bg-purple-50">
                                Edit Schedule
                            </a>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($overviewCards as $card)
                            <article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                                        <p class="mt-3 text-3xl font-black text-slate-950">{{ $card['value'] }}</p>
                                        <p class="mt-2 text-xs font-semibold text-slate-500">{{ $card['hint'] }}</p>
                                    </div>
                                    <span class="grid h-11 w-11 place-items-center rounded-2xl text-sm font-black ring-1 {{ $card['tone'] }}">
                                        {{ $card['abbr'] }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>

            <aside id="batch-schedules" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Active Batch</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">
                            {{ $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'Schedule needed' }}
                        </h2>
                    </div>
                    <a href="{{ route('admin.schedules.index') }}" class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700 hover:bg-purple-50">Manage</a>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">AM Class</p>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-purple-700 ring-1 ring-purple-100">{{ $activeBatch?->am_days ?: 'MWF' }}</span>
                        </div>
                        <p class="mt-3 text-sm font-black leading-6 text-slate-900">{{ $activeBatch?->scheduleLabelFor('AM') ?? 'MWF | 8:00 AM - 12:00 PM' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $activeBatch?->am_room ?: 'Room 201 / Skills Lab' }}</p>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">PM Class</p>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-purple-700 ring-1 ring-purple-100">{{ $activeBatch?->pm_days ?: 'TTS' }}</span>
                        </div>
                        <p class="mt-3 text-sm font-black leading-6 text-slate-900">{{ $activeBatch?->scheduleLabelFor('PM') ?? 'TTS | 1:00 PM - 5:00 PM' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $activeBatch?->pm_room ?: 'Room 202 / Lecture Room' }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-3xl border border-purple-100 bg-purple-50 p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment Deadline</p>
                    <p class="mt-2 text-sm font-black text-slate-950">
                        {{ $activeBatch?->enrollment_ends_at?->format('M d, Y g:i A') ?? 'Set active batch deadline' }}
                    </p>
                </div>
            </aside>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]">
            <section id="action-queue" class="overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
                <div class="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Action Queue</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Applications needing admin review</h2>
                    </div>
                    <a href="{{ route('admin.enrollments.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-2.5 text-sm font-black text-purple-700 hover:bg-purple-50">
                        Open Applications
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Applicant</th>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Documents</th>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Payment</th>
                                <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-wide text-slate-500">Batch</th>
                                <th class="px-6 py-4 text-right text-xs font-black uppercase tracking-wide text-slate-500">Next Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($actionQueue as $application)
                                <tr class="hover:bg-purple-50/40">
                                    <td class="px-6 py-5">
                                        <p class="font-black text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $application->email }}</p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $statusBadgeClasses[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                            {{ $application->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                            {{ $application->paymentStatusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-sm text-slate-600">
                                        <p class="font-bold text-slate-800">{{ $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $application->schedule_preference ?: 'Schedule TBA' }}</p>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <a href="{{ route('admin.enrollments.show', $application) }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-4 py-2 text-sm font-black text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-14 text-center">
                                        <p class="text-lg font-black text-slate-950">No urgent applications</p>
                                        <p class="mt-2 text-sm text-slate-500">New submitted and pre-enlistment applications will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside id="payment-queue" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Payment Queue</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Online and on-site</h2>
                    </div>
                    <a href="{{ route('admin.payment-schedules.index') }}" class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700 hover:bg-purple-50">View</a>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-3xl bg-purple-50 p-4 ring-1 ring-purple-100">
                        <p class="text-xs font-black uppercase tracking-wide text-purple-700">PayMongo</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $paymentCounts['online_pending'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-3xl bg-amber-50 p-4 ring-1 ring-amber-100">
                        <p class="text-xs font-black uppercase tracking-wide text-amber-700">On-site</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $paymentCounts['onsite_pending'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="mt-5 divide-y divide-slate-100">
                    @forelse ($paymentQueue as $application)
                        <article class="py-4 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $application->payment_method === 'online' ? 'PayMongo checkout' : 'Pay on-site receipt' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black ring-1 {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                    {{ $application->paymentStatusLabel() }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                Due {{ $application->effectivePaymentDeadline()?->format('M d, g:i A') ?? 'after admin schedule' }}
                            </p>
                        </article>
                    @empty
                        <div class="py-10 text-center">
                            <p class="font-black text-slate-950">No pending payment actions</p>
                            <p class="mt-2 text-sm text-slate-500">Online and on-site queues will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section id="lms-modules" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <p class="text-xs font-black uppercase tracking-wide text-purple-600">LMS Modules</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Learning access</h2>
                <div class="mt-5 space-y-3">
                    @foreach ([
                        ['title' => 'Caregiving NC II Orientation', 'state' => 'Ready to unlock'],
                        ['title' => 'Basic Life Support', 'state' => 'Progress tracking needed'],
                        ['title' => 'Secure PDF Viewer', 'state' => 'Server enforcement later'],
                    ] as $module)
                        <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                            <p class="font-black text-slate-950">{{ $module['title'] }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $module['state'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="certificates" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <p class="text-xs font-black uppercase tracking-wide text-purple-600">Certificates</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Eligibility signal</h2>
                <div class="mt-5 rounded-3xl bg-emerald-50 p-5 ring-1 ring-emerald-100">
                    <p class="text-5xl font-black text-slate-950">{{ $certificatesReady }}</p>
                    <p class="mt-2 text-sm font-bold text-emerald-700">Approved and paid trainees</p>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-500">
                    This dashboard prepares the future Browsershot certificate flow by identifying trainees who meet the current approval and payment requirements.
                </p>
            </section>

            <section id="admin-logs" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Security Logs</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Recent admin actions</h2>
                    </div>
                    <a href="{{ route('admin.logs.index') }}" class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700 hover:bg-purple-50">Logs</a>
                </div>

                <div class="mt-5 divide-y divide-slate-100">
                    @forelse ($recentLogs as $log)
                        <article class="py-4 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-950">{{ $log->action }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $log->user?->name ?? 'System / unknown' }}</p>
                                </div>
                                <p class="shrink-0 text-xs font-bold text-slate-400">{{ $log->created_at?->diffForHumans() }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="py-10 text-center">
                            <p class="font-black text-slate-950">No logs yet</p>
                            <p class="mt-2 text-sm text-slate-500">Admin login and review events will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section id="reports" class="rounded-[2rem] border border-purple-100 bg-white p-6 shadow-xl shadow-purple-100/40">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px] lg:items-center">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-purple-600">Reports and Alumni Jobs</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Capstone workflow coverage</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        The dashboard now reserves clear admin space for reports, alumni outcomes, certificate generation, and LMS tracking while the backend modules are finalized.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Total Applications</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $stats['total_applications'] }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Approved</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ $stats['approved'] }}</p>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection
