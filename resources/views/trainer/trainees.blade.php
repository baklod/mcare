@extends('trainer.layouts.app', ['title' => 'Trainees | MCARE Trainer'])

@section('content')
<div class="mx-auto max-w-7xl space-y-7">
    <header class="border-b border-stone-200 pb-6"><p class="dashboard-section-kicker">Trainees</p><h1 class="dashboard-section-title mt-2 text-3xl">Approved learner roster</h1><p class="mt-2 text-stone-600">Filter a batch or class, then export the visible roster and module summary to an Excel-compatible CSV.</p></header>
    <form method="GET" action="{{ route('trainer.trainees') }}" class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search</label><input name="search" value="{{ $search }}" class="form-field" placeholder="Search name or email"></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label><select name="batch_id" class="form-field"><option value="">All batches</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected($batchId === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label><select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" @selected($schedule === 'AM')>AM</option><option value="PM" @selected($schedule === 'PM')>PM</option></select></div>
        <div class="flex flex-wrap gap-2 md:col-span-4"><button class="primary-action">Apply filters</button><a href="{{ route('trainer.trainees') }}" class="secondary-action">Reset</a><a href="{{ route('trainer.trainees.export', request()->query()) }}" class="secondary-action">Export trainee summary</a></div>
    </form>
    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table min-w-[58rem]"><thead><tr><th>Trainee</th><th>Batch</th><th>Schedule</th><th>Module progress</th><th>Payment</th><th>Training state</th></tr></thead><tbody>
            @forelse($trainees as $trainee)<tr><td><p class="font-bold text-stone-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p><p class="text-stone-500">{{ $trainee->email }}</p></td><td>{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned' }}</td><td>{{ $trainee->schedule_preference }}</td><td><span class="font-bold text-emerald-700">{{ $trainee->moduleProgress->where('status', 'completed')->count() }} complete</span><p class="text-xs text-stone-500">{{ $trainee->moduleProgress->where('status', 'in_progress')->count() }} in progress</p></td><td>{{ $trainee->paymentStatusLabel() }}</td><td>{{ $trainee->batch?->trainingStateLabel() ?? 'No batch' }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-10 text-center text-stone-600">No approved trainees found.</td></tr>@endforelse
        </tbody></table>
    </div>
    {{ $trainees->links() }}
</div>
@endsection
