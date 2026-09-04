@extends('admin.layouts.app', ['title' => 'Admin Operations Console | MCARE'])

@section('content')
    <style>
        .admin-dashboard .font-black {
            font-weight: 700;
        }

        .admin-dashboard [class~="rounded-[2rem]"],
        .admin-dashboard .rounded-3xl,
        .admin-dashboard .rounded-2xl,
        .admin-dashboard .rounded-xl {
            border-radius: 2px;
        }

        .admin-dashboard .rounded-full:not(.user-avatar):not(.dashboard-account-avatar) {
            border-radius: 2px;
        }

        .admin-dashboard .shadow-sm,
        .admin-dashboard .shadow-lg,
        .admin-dashboard .shadow-xl,
        .admin-dashboard .shadow-2xl {
            box-shadow: none;
        }

        .admin-dashboard [class*="bg-gradient-to"] {
            background-image: none;
        }

        .admin-training-chart {
            --chart-enrolled: #7c3aed;
            --chart-passing: #0d9488;
            --chart-grid: #e2e8f0;
            --chart-muted: #64748b;
        }

        .admin-training-chart-card {
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            background: #fff;
        }

        .admin-training-chart-plot {
            position: relative;
            width: 100%;
            height: 25rem;
        }

        .admin-training-chart-plot svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .admin-training-chart-grid {
            stroke: var(--chart-grid);
            stroke-width: 1;
            vector-effect: non-scaling-stroke;
        }

        .admin-training-chart-baseline {
            stroke: #cbd5e1;
            stroke-width: 1;
            vector-effect: non-scaling-stroke;
        }

        .admin-training-chart-line-enrolled,
        .admin-training-chart-line-passing {
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            vector-effect: non-scaling-stroke;
        }

        .admin-training-chart-line-enrolled {
            stroke: var(--chart-enrolled);
        }

        .admin-training-chart-line-passing {
            stroke: var(--chart-passing);
        }

        .admin-training-chart-dot {
            cursor: pointer;
            stroke: #fff;
            stroke-width: 2;
            transition: transform 0.15s ease;
            transform-origin: center;
            transform-box: fill-box;
        }

        .admin-training-chart-dot:hover {
            transform: scale(1.5);
        }

        .admin-training-chart-tooltip {
            position: absolute;
            z-index: 2;
            min-width: 8.75rem;
            padding: 0.5rem 0.7rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #0f172a;
            box-shadow: 0 8px 24px rgb(15 23 42 / 0.08);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
            transform: translate(-50%, calc(-100% - 0.65rem));
        }

        .admin-training-chart-tooltip.is-visible {
            opacity: 1;
        }

        .admin-training-chart-tooltip-label {
            margin: 0 0 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .admin-training-chart-tooltip-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin: 0.15rem 0 0;
            font-size: 0.75rem;
            color: #64748b;
        }

        .admin-training-chart-tooltip-row strong {
            color: #0f172a;
            font-weight: 600;
        }

        @media (min-width: 1280px) {
            .admin-action-queue {
                height: 0;
                min-height: 100%;
            }
        }
    </style>

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
                'label' => 'Pre-enlisted Applications',
                'icon' => 'clipboard-list',
                'value' => $stats['pending_applications'],
                'hint' => 'Awaiting document and payment review',
                'tone' => 'bg-purple-50 text-purple-700 ring-purple-100',
            ],
            [
                'label' => 'Documents to Verify',
                'icon' => 'file-text',
                'value' => $documentsToVerify,
                'hint' => 'Checklist review queue',
                'tone' => 'bg-sky-50 text-sky-700 ring-sky-100',
            ],
            [
                'label' => 'Payments Today',
                'icon' => 'credit-card',
                'value' => $paymentsToday,
                'hint' => ($paymentCounts['onsite_pending'] ?? 0).' on-site due',
                'tone' => 'bg-amber-50 text-amber-700 ring-amber-100',
            ],
            [
                'label' => 'Certificates Ready',
                'icon' => 'award',
                'value' => $certificatesReady,
                'hint' => 'Approved and paid',
                'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ],
        ];
    @endphp

    <section class="admin-dashboard space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($overviewCards as $card)
                <article class="border border-stone-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-black text-slate-950">{{ $card['value'] }}</p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">{{ $card['hint'] }}</p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center text-base ring-1 rounded-lg {{ $card['tone'] }}">
                            <x-dashboard-icon :name="$card['icon']" />
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]">
            @php
                $chartWidth = 640;
                $chartHeight = 300;
                $chartPad = ['l' => 28, 'r' => 12, 't' => 16, 'b' => 28];
                $chartInnerW = $chartWidth - $chartPad['l'] - $chartPad['r'];
                $chartInnerH = $chartHeight - $chartPad['t'] - $chartPad['b'];
                $rawMax = (int) collect($trainingTrend)->max(fn ($row) => max((int) $row['enrolled'], (int) $row['passing']));
                $chartMax = max(2, $rawMax);
                $chartCount = max(1, count($trainingTrend));
                $tickSteps = $chartMax <= 4 ? $chartMax : 4;
                $chartX = fn (int $index) => $chartPad['l'] + ($chartCount === 1 ? $chartInnerW / 2 : $index * ($chartInnerW / ($chartCount - 1)));
                $chartY = fn (int $value) => $chartPad['t'] + $chartInnerH - (($value / $chartMax) * $chartInnerH);

                $buildPts = fn (string $key) => collect($trainingTrend)->values()->map(fn ($row, $i) => [$chartX($i), $chartY((int) $row[$key])])->all();
                $enrolledPts = $buildPts('enrolled');
                $passingPts = $buildPts('passing');

                $smoothLine = function (array $pts): string {
                    $n = count($pts);
                    if ($n === 0) return '';
                    if ($n === 1) return sprintf('M%.1f,%.1f', $pts[0][0], $pts[0][1]);
                    $d = sprintf('M%.1f,%.1f', $pts[0][0], $pts[0][1]);
                    for ($i = 0; $i < $n - 1; $i++) {
                        $dx = ($pts[$i + 1][0] - $pts[$i][0]) / 3;
                        $d .= sprintf(' C%.1f,%.1f %.1f,%.1f %.1f,%.1f',
                            $pts[$i][0] + $dx, $pts[$i][1],
                            $pts[$i + 1][0] - $dx, $pts[$i + 1][1],
                            $pts[$i + 1][0], $pts[$i + 1][1]
                        );
                    }
                    return $d;
                };

                $baselineY = $chartY(0);
                $smoothArea = function (array $pts, string $line) use ($baselineY): string {
                    if (count($pts) < 2) return '';
                    return $line . sprintf(' L%.1f,%.1f L%.1f,%.1f Z',
                        $pts[count($pts) - 1][0], $baselineY, $pts[0][0], $baselineY);
                };

                $enrolledLine = $smoothLine($enrolledPts);
                $passingLine = $smoothLine($passingPts);
                $enrolledArea = $smoothArea($enrolledPts, $enrolledLine);
                $passingArea = $smoothArea($passingPts, $passingLine);
                $latestTrend = collect($trainingTrend)->last();
            @endphp

            <section class="admin-training-chart admin-training-chart-card self-start overflow-hidden rounded-[2rem] border border-slate-100 bg-white p-4" aria-labelledby="training-progress-title">
                <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 id="training-progress-title" class="text-sm font-semibold text-slate-950">Training progress</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $latestTrend['label'] ?? 'This month' }}:
                            {{ number_format((int) ($latestTrend['enrolled'] ?? 0)) }} active,
                            {{ number_format((int) ($latestTrend['passing'] ?? 0)) }} graduate
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-600">
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 bg-[#7c3aed]"></span>Active</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 bg-[#0d9488]"></span>Graduate</span>
                    </div>
                </div>
                <div class="admin-training-chart-plot" data-training-chart>
                    <div class="admin-training-chart-tooltip" data-training-chart-tooltip hidden></div>
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" role="img" aria-label="Line chart of enrolled and passing students">
                        <defs>
                            <linearGradient id="grad-enrolled" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#7c3aed" stop-opacity="0.18"/>
                                <stop offset="100%" stop-color="#7c3aed" stop-opacity="0.01"/>
                            </linearGradient>
                            <linearGradient id="grad-passing" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#0d9488" stop-opacity="0.18"/>
                                <stop offset="100%" stop-color="#0d9488" stop-opacity="0.01"/>
                            </linearGradient>
                        </defs>
                        @for ($step = $tickSteps; $step >= 0; $step--)
                            @php
                                $tick = (int) round($chartMax * ($step / $tickSteps));
                                $tickY = $chartY($tick);
                            @endphp
                            <line class="{{ $tick === 0 ? 'admin-training-chart-baseline' : 'admin-training-chart-grid' }}" x1="{{ $chartPad['l'] }}" y1="{{ $tickY }}" x2="{{ $chartWidth - $chartPad['r'] }}" y2="{{ $tickY }}"></line>
                            <text x="{{ $chartPad['l'] - 6 }}" y="{{ $tickY + 3 }}" text-anchor="end" font-size="8" fill="#64748b">{{ $tick }}</text>
                        @endfor
                        @if ($enrolledArea)
                            <path d="{{ $enrolledArea }}" fill="url(#grad-enrolled)" pointer-events="none"/>
                        @endif
                        @if ($passingArea)
                            <path d="{{ $passingArea }}" fill="url(#grad-passing)" pointer-events="none"/>
                        @endif
                        <path class="admin-training-chart-line-enrolled" d="{{ $enrolledLine }}"/>
                        <path class="admin-training-chart-line-passing" d="{{ $passingLine }}"/>
                        @foreach ($trainingTrend as $index => $row)
                            <circle class="admin-training-chart-dot" cx="{{ $chartX($index) }}" cy="{{ $chartY($row['enrolled']) }}" r="4" fill="#7c3aed" data-chart-label="{{ $row['label'] }}" data-chart-enrolled="{{ $row['enrolled'] }}" data-chart-passing="{{ $row['passing'] }}"></circle>
                            <circle class="admin-training-chart-dot" cx="{{ $chartX($index) }}" cy="{{ $chartY($row['passing']) }}" r="4" fill="#0d9488" data-chart-label="{{ $row['label'] }}" data-chart-enrolled="{{ $row['enrolled'] }}" data-chart-passing="{{ $row['passing'] }}"></circle>
                            <text x="{{ $chartX($index) }}" y="{{ $chartHeight - 6 }}" text-anchor="middle" font-size="8" fill="#64748b">{{ $row['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </section>

            <aside id="batch-schedules" class="rounded-xl border border-stone-200 bg-white p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Active Batch</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">
                            {{ $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'Schedule needed' }}
                        </h2>
                    </div>
                    <a href="{{ route('admin.batches.index') }}" class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700 hover:bg-purple-50">Manage</a>
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
                        {{ $activeBatch?->is_continuous_enrollment ? 'Continuous enrollment — no deadline' : ($activeBatch?->enrollment_ends_at?->format('M d, Y g:i A') ?? 'Set active batch deadline') }}
                    </p>
                </div>
            </aside>

            <section id="action-queue" class="admin-action-queue flex flex-col overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
                <div class="flex shrink-0 flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">Action Queue</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Applications needing admin review</h2>
                    </div>
                    <a href="{{ route('admin.enrollments.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-purple-200 bg-white px-4 py-2 text-sm font-black text-purple-700 hover:bg-purple-50">
                        Open Applications
                    </a>
                </div>

                <div class="min-h-0 flex-1 overflow-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="sticky top-0 bg-slate-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Applicant</th>
                                <th class="px-4 py-2.5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Documents</th>
                                <th class="px-4 py-2.5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Payment</th>
                                <th class="px-4 py-2.5 text-left text-xs font-black uppercase tracking-wide text-slate-500">Batch</th>
                                <th class="px-4 py-2.5 text-right text-xs font-black uppercase tracking-wide text-slate-500">Next Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($actionQueue as $application)
                                <tr class="hover:bg-purple-50/40">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <x-user-avatar :user="$application->user" :application="$application" :use-enrollment-photo="true" class="grid h-8 w-8 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800" />
                                            <div class="min-w-0"><p class="truncate text-sm font-black text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</p><p class="truncate text-xs text-slate-500">{{ $application->email }}</p></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-black ring-1 {{ $statusBadgeClasses[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                            {{ $application->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-black ring-1 {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                            {{ $application->paymentStatusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">
                                        <p class="font-bold text-slate-800">{{ $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned' }}</p>
                                        <p class="text-xs text-slate-500">{{ $application->schedule_preference ?: 'Schedule TBA' }}</p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ route('admin.enrollments.show', $application) }}" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-3 py-1.5 text-xs font-black text-white hover:bg-purple-700">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center">
                                        <p class="text-sm font-black text-slate-950">No urgent applications</p>
                                        <p class="mt-1 text-xs text-slate-500">New submitted and pre-enlistment applications will appear here.</p>
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
                    <div class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-700">PayMongo</p>
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
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-user-avatar :user="$application->user" :application="$application" :use-enrollment-photo="true" class="grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800" />
                                    <div class="min-w-0"><p class="font-black text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $application->payment_method === 'online' ? 'PayMongo checkout' : 'Pay on-site receipt' }}</p></div>
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

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section id="lms-modules" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">LMS Modules</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Learning access</h2>
                    </div>
                    <a href="{{ route('admin.learning.modules') }}" class="rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-black text-purple-700 hover:bg-purple-50">Modules</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($learningModules as $module)
                        <a href="{{ route('admin.learning.modules.preview', $module) }}" class="block rounded-3xl border border-slate-100 bg-slate-50 p-4 transition hover:border-purple-200 hover:bg-white">
                            <p class="font-black text-slate-950">{{ $module->title }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $module->learningAccessSummary() }}</p>
                        </a>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center">
                            <p class="font-black text-slate-950">No learning modules yet</p>
                            <p class="mt-2 text-sm text-slate-500">Published Caregiving NC II modules will appear here as trainers and admins add them.</p>
                        </div>
                    @endforelse
                </div>
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
    </section>
@endsection
