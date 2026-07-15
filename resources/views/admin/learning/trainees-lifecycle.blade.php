@extends('admin.layouts.app', ['title' => 'Trainee Records | MCARE Admin'])

@section('content')
    @php
        $statusStyles = [
            \App\Models\EnrollmentApplication::LEARNING_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::LEARNING_PAUSED => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::LEARNING_GRADUATED => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::LEARNING_WITHDRAWN => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
    @endphp

    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Learning system · Trainees</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Trainee lifecycle records</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Keep active, paused, graduated, and withdrawn learners separate while tracking when each trainee joined a batch.</p>
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

        <form method="GET" data-auto-filter class="dashboard-panel grid gap-4 md:grid-cols-2 xl:grid-cols-8">
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

        <div class="dashboard-table-wrap overflow-x-auto">
            <table class="dashboard-table min-w-[86rem]">
                <thead><tr><th>Trainee</th><th>Batch / class</th><th>Joined</th><th>Payment</th><th>Batch progress</th><th>Learner status</th><th>Quick actions</th><th>Record</th></tr></thead>
                <tbody>
                    @forelse ($trainees as $trainee)
                        <tr>
                            <td><p class="font-bold text-slate-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p><p class="mt-1 text-xs">{{ $trainee->email }}</p></td>
                            <td><p class="font-semibold text-slate-800">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned' }}</p><span class="dashboard-pill mt-2 bg-purple-50 text-purple-700 ring-purple-100">{{ $trainee->schedule_preference }}</span></td>
                            <td><p class="font-semibold text-slate-800">{{ $trainee->reviewed_at?->format('M d, Y') ?? 'Not recorded' }}</p><p class="mt-1 text-xs text-slate-500">Approval date</p></td>
                            <td>{{ $trainee->paymentStatusLabel() }}</td>
                            <td>{{ $trainee->batch?->trainingStateLabel() ?? 'No batch' }}</td>
                            <td>
                                <span class="dashboard-pill {{ $statusStyles[$trainee->learning_status] ?? $statusStyles[\App\Models\EnrollmentApplication::LEARNING_ACTIVE] }}">{{ $trainee->learningStatusLabel() }}</span>
                                @if ($trainee->learning_status_changed_at)<p class="mt-2 text-xs text-slate-500">Updated {{ $trainee->learning_status_changed_at->diffForHumans() }}</p>@endif
                                @if ($trainee->learning_status_notes)<p class="mt-1 max-w-48 text-xs leading-5 text-slate-600">{{ $trainee->learning_status_notes }}</p>@endif
                            </td>
                            <td>
                                <div class="flex min-w-64 flex-wrap gap-2">
                                    @if ($trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_ACTIVE)
                                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="paused"><button class="min-h-9 rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-bold text-amber-900 hover:bg-amber-100">Pause</button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="active"><button class="min-h-9 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Resume</button></form>
                                    @endif
                                    @if ($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
                                        <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}">@csrf @method('PATCH')<input type="hidden" name="learning_status" value="graduated"><button class="min-h-9 rounded-lg border border-purple-200 bg-purple-50 px-3 text-xs font-bold text-purple-800 hover:bg-purple-100">Graduate</button></form>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.learning.trainees.status', $trainee) }}" class="mt-3 min-w-64 space-y-2 border-t border-slate-200 pt-3">
                                    @csrf @method('PATCH')
                                    <div class="flex gap-2"><select name="learning_status" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-xs font-semibold">@foreach($learningStatuses as $value => $label)<option value="{{ $value }}" @selected($trainee->learning_status === $value)>{{ $label }}</option>@endforeach</select><button class="rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700 hover:border-purple-300">Save</button></div>
                                    <input name="learning_status_notes" value="{{ $trainee->learning_status_notes }}" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs" placeholder="Optional status note">
                                </form>
                            </td>
                            <td><a href="{{ route('admin.enrollments.show', $trainee) }}" class="font-bold text-purple-700 hover:text-purple-900">Open record</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-14 text-center">No approved trainees match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($trainees->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $trainees->links() }}</div>@endif
        </div>
    </section>
@endsection
