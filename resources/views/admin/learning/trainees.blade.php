@extends('admin.layouts.app', ['title' => 'Trainee Records | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Learning system · Trainees</p>
        <h1 class="mt-2 dashboard-section-title text-3xl">Approved trainee records</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Monitor approved learners by batch and AM/PM class before attendance, assessment, and progress records are added.</p>
    </header>
    <form method="GET" data-auto-filter class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search trainee</label><input name="search" value="{{ $filters['search'] ?? '' }}" class="form-field" placeholder="Name or email"></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label><select name="batch_id" class="form-field"><option value="">All batches</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label><select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select></div>
        <div class="flex gap-2 md:col-span-4"><button class="primary-action">Filter trainees</button><a href="{{ route('admin.learning.trainees') }}" class="secondary-action">Reset</a></div>
    </form>
    <div class="dashboard-table-wrap overflow-x-auto"><table class="dashboard-table min-w-[58rem]"><thead><tr><th>Trainee</th><th>Batch</th><th>Schedule</th><th>Payment</th><th>Training state</th><th>Record</th></tr></thead><tbody>
    @forelse($trainees as $trainee)
        <tr><td><p class="font-bold text-slate-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p><p class="mt-1 text-xs">{{ $trainee->email }}</p></td><td>{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned' }}</td><td><span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">{{ $trainee->schedule_preference }}</span></td><td>{{ $trainee->paymentStatusLabel() }}</td><td>{{ $trainee->batch?->trainingStateLabel() ?? 'No batch' }}</td><td><a href="{{ route('admin.enrollments.show', $trainee) }}" class="font-bold text-purple-700 hover:text-purple-900">Open record</a></td></tr>
    @empty<tr><td colspan="6" class="py-14 text-center">No approved trainees match these filters.</td></tr>@endforelse
    </tbody></table>@if($trainees->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $trainees->links() }}</div>@endif</div>
</section>
@endsection
