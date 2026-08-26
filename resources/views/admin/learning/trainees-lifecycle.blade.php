@extends('admin.layouts.app', ['title' => 'Trainee Records | MCARE Admin'])

@section('content')
    @php
        $statusStyles = [
            \App\Models\EnrollmentApplication::LEARNING_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::LEARNING_PAUSED => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::LEARNING_GRADUATED => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::LEARNING_WITHDRAWN => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $paymentStyles = [
            \App\Models\EnrollmentApplication::PAYMENT_PAID => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::PAYMENT_ONSITE_PENDING => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::PAYMENT_ONLINE_PENDING => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::PAYMENT_EXPIRED => 'bg-red-50 text-red-800 ring-red-200',
            \App\Models\EnrollmentApplication::PAYMENT_NOT_SELECTED => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $hasActiveFilters = collect($filters)->contains(fn ($value) => filled($value));
    @endphp

    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Learning system · Trainees</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Trainee lifecycle records</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Use the summary roster first, then open one trainee to review payment, module completion, assessment readiness, and lifecycle actions.</p>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($learningStatuses as $value => $label)
                <a href="{{ route('admin.learning.trainees', ['learning_status' => $value]) }}" class="dashboard-stat min-h-0 p-4 {{ ($filters['learning_status'] ?? '') === $value ? 'border-purple-300 ring-2 ring-purple-100' : '' }}">
                    <div>
                        <p class="dashboard-stat-label">{{ $label }}</p>
                        <p class="dashboard-stat-value text-2xl">{{ $statusCounts[$value] ?? 0 }}</p>
                    </div>
                    <span class="dashboard-pill {{ $statusStyles[$value] }}">{{ str($value)->substr(0, 2)->upper() }}</span>
                </a>
            @endforeach
        </div>

        <details class="dashboard-panel trainee-filter-panel" @if($hasActiveFilters) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 outline-none">
                <div>
                    <p class="dashboard-section-kicker">Roster filters</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Search or narrow the summarized trainee list</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-purple-700">
                    <x-dashboard-icon name="chevron-down" class="trainee-accordion-chevron h-4 w-4 transition-transform" />
                </span>
            </summary>

            <form method="GET" data-auto-filter class="mt-5 grid gap-4 border-t border-slate-200 pt-5 md:grid-cols-2 xl:grid-cols-8">
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search trainee</label>
                    <input name="search" value="{{ $filters['search'] ?? '' }}" class="form-field" placeholder="Name or email">
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
                    <select name="batch_id" class="form-field">
                        <option value="">All batches</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected((int) ($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }} · {{ $batch->approved_trainees_count }} trainees</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
                    <select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Learner status</label>
                    <select name="learning_status" class="form-field"><option value="">All statuses</option>@foreach($learningStatuses as $value => $label)<option value="{{ $value }}" @selected(($filters['learning_status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch progress</label>
                    <select name="training_state" class="form-field"><option value="">Any progress</option><option value="not_started" @selected(($filters['training_state'] ?? '') === 'not_started')>Not started</option><option value="in_progress" @selected(($filters['training_state'] ?? '') === 'in_progress')>In progress</option><option value="completed" @selected(($filters['training_state'] ?? '') === 'completed')>Completed</option></select>
                </div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Joined from</label><input name="joined_from" type="date" value="{{ $filters['joined_from'] ?? '' }}" class="form-field"></div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Joined to</label><input name="joined_to" type="date" value="{{ $filters['joined_to'] ?? '' }}" class="form-field"></div>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-6"><button class="primary-action">Filter trainees</button><a href="{{ route('admin.learning.trainees') }}" class="secondary-action">Reset</a><a href="{{ route('admin.learning.trainees.export', request()->query()) }}" class="secondary-action">Export Excel CSV</a></div>
            </form>
        </details>

        <div class="space-y-3" data-trainee-accordion>
            @forelse ($trainees as $trainee)
                @php
                    $summary = $traineeSummaries->get($trainee->id, [
                        'total_modules' => 0,
                        'completed_modules' => 0,
                        'in_progress_modules' => 0,
                        'progress_percent' => 0,
                        'last_activity' => null,
                        'assessment_ready' => false,
                    ]);
                    $paymentMethod = match ($trainee->payment_method) {
                        'onsite' => 'On-site payment',
                        'online' => 'Online payment',
                        default => 'Not selected',
                    };
                    $paymentReference = $trainee->payment_receipt_number
                        ?: $trainee->paymongo_checkout_reference
                        ?: $trainee->payment_reference;
                    $assessmentLabel = $summary['assessment_ready']
                        ? 'Ready for trainer assessment'
                        : ($summary['total_modules'] > 0 ? 'Learning requirements pending' : 'Waiting for published modules');
                @endphp

                <details class="trainee-accordion-card overflow-hidden rounded-xl border border-slate-200 bg-white" data-trainee-card>
                    <summary class="grid cursor-pointer list-none gap-4 p-5 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-purple-500 lg:grid-cols-[minmax(16rem,1.2fr)_minmax(25rem,1fr)_auto] lg:items-center">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-user-avatar :user="$trainee->user" :application="$trainee" :use-enrollment-photo="true" class="grid h-12 w-12 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800" />
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-lg font-bold text-slate-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                    <span class="dashboard-pill {{ $statusStyles[$trainee->learning_status] ?? $statusStyles[\App\Models\EnrollmentApplication::LEARNING_ACTIVE] }}">{{ $trainee->learningStatusLabel() }}</span>
                                </div>
                                <p class="mt-1 truncate text-sm text-slate-500">{{ $trainee->email }}</p>
                                <p class="mt-2 text-xs font-semibold text-slate-600">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch' }} · {{ $trainee->schedule_preference ?: 'Schedule pending' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Payment</p>
                                <p class="mt-1 truncate text-xs font-bold text-slate-800">{{ $trainee->paymentStatusLabel() }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Modules completed</p>
                                <p class="mt-1 text-xs font-bold text-slate-800">{{ $summary['completed_modules'] }} of {{ $summary['total_modules'] }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Assessment</p>
                                <p class="mt-1 text-xs font-bold {{ $summary['assessment_ready'] ? 'text-emerald-700' : 'text-amber-800' }}">{{ $summary['assessment_ready'] ? 'Ready' : 'Pending' }}</p>
                            </div>
                        </div>

                        <span class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-wide text-purple-700 lg:justify-end">
                            <span>View details</span>
                            <span class="grid h-10 w-10 place-items-center rounded-lg border border-purple-200 bg-purple-50">
                                <x-dashboard-icon name="chevron-down" class="trainee-accordion-chevron h-4 w-4 transition-transform" />
                            </span>
                        </span>
                    </summary>

                    <div class="border-t border-slate-200 bg-slate-50/60 p-5 sm:p-6">
                        <div class="grid gap-4 xl:grid-cols-3">
                            <section class="rounded-xl border border-slate-200 bg-white p-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-purple-50 text-purple-700"><x-dashboard-icon name="credit-card" class="h-5 w-5" /></span>
                                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Payment</p><p class="font-bold text-slate-950">{{ $paymentMethod }}</p></div>
                                </div>
                                <dl class="mt-5 space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Status</dt><dd><span class="dashboard-pill {{ $paymentStyles[$trainee->payment_status] ?? $paymentStyles[\App\Models\EnrollmentApplication::PAYMENT_NOT_SELECTED] }}">{{ $trainee->paymentStatusLabel() }}</span></dd></div>
                                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Amount</dt><dd class="font-semibold text-slate-800">{{ $trainee->payment_amount ? ($trainee->payment_currency ?: 'PHP').' '.number_format((float) $trainee->payment_amount, 2) : 'Not recorded' }}</dd></div>
                                    <div><dt class="text-slate-500">Reference</dt><dd class="mt-1 break-all font-semibold text-slate-800">{{ $paymentReference ?: 'Not available' }}</dd></div>
                                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Verified</dt><dd class="font-semibold text-slate-800">{{ $trainee->payment_verified_at?->format('M d, Y g:i A') ?? 'Not verified' }}</dd></div>
                                </dl>
                            </section>

                            <section class="rounded-xl border border-slate-200 bg-white p-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-sky-50 text-sky-700"><x-dashboard-icon name="book-open" class="h-5 w-5" /></span>
                                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Modules completed</p><p class="font-bold text-slate-950">{{ $summary['completed_modules'] }} of {{ $summary['total_modules'] }} published modules</p></div>
                                </div>
                                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Module progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $summary['progress_percent'] }}">
                                    <div class="h-full rounded-full bg-purple-600" style="width: {{ $summary['progress_percent'] }}%"></div>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-xs font-semibold text-slate-500"><span>{{ $summary['progress_percent'] }}% overall progress</span><span>{{ $summary['in_progress_modules'] }} in progress</span></div>
                                <p class="mt-5 text-sm text-slate-600">Last module activity: <span class="font-semibold text-slate-800">{{ $summary['last_activity']?->format('M d, Y g:i A') ?? 'No module opened yet' }}</span></p>
                            </section>

                            <section class="rounded-xl border border-slate-200 bg-white p-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg {{ $summary['assessment_ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}"><x-dashboard-icon name="square-check" class="h-5 w-5" /></span>
                                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Assessment readiness</p><p class="font-bold text-slate-950">{{ $assessmentLabel }}</p></div>
                                </div>
                                <p class="mt-5 text-sm leading-6 text-slate-600">{{ $summary['assessment_ready'] ? 'All currently published modules are complete. The trainer can proceed once assessment recording is available.' : 'Complete the remaining learning requirements before trainer assessment.' }}</p>
                                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-xs font-bold text-slate-700">Assessment result: Not recorded yet</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">A real score or competency result will appear here after the assessment backend is implemented.</p>
                                </div>
                            </section>
                        </div>

                        <div class="mt-5 grid gap-5 border-t border-slate-200 pt-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                            <div>
                                <p class="text-sm font-bold text-slate-950">Lifecycle controls</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Pause, resume, graduate, or record an administrative status note for this trainee.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_ACTIVE)
                                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="paused"><button class="min-h-10 rounded-lg border border-amber-200 bg-amber-50 px-4 text-xs font-bold text-amber-900 hover:bg-amber-100">Pause</button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="active"><button class="min-h-10 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Resume</button></form>
                                    @endif
                                    @if ($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
                                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="learning_status" value="graduated">
                                            <button class="min-h-10 rounded-lg border border-purple-200 bg-purple-50 px-4 text-xs font-bold text-purple-800 hover:bg-purple-100" data-confirm="Graduate {{ $trainee->first_name }} {{ $trainee->last_name }}? If requirements were completed offline or on-site, competencies will be marked Competent and COTC will be available online.">Graduate</button>
                                        </form>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}" class="mt-3 grid max-w-3xl gap-2 sm:grid-cols-[12rem_minmax(14rem,1fr)_auto]">
                                    @csrf @method('PATCH')
                                    <select name="learning_status" class="form-field text-sm">@foreach($learningStatuses as $value => $label)<option value="{{ $value }}" @selected($trainee->learning_status === $value)>{{ $label }}</option>@endforeach</select>
                                    <input name="learning_status_notes" value="{{ $trainee->learning_status_notes }}" class="form-field text-sm" placeholder="Optional status note">
                                    <button class="secondary-action">Save status</button>
                                </form>
                            </div>
                            <a href="{{ route('admin.enrollments.show', $trainee) }}" class="primary-action inline-flex items-center justify-center">Open full trainee record</a>
                        </div>
                    </div>
                </details>
            @empty
                <div class="dashboard-panel py-14 text-center"><p class="text-lg font-bold text-slate-950">No trainees found</p><p class="mt-2 text-sm text-slate-500">No approved trainees match the current filters.</p></div>
            @endforelse
        </div>

        @if ($trainees->hasPages())
            <div class="dashboard-panel py-4">{{ $trainees->links() }}</div>
        @endif
    </section>
@endsection
