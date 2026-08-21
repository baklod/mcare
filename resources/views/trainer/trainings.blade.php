@extends('trainer.layouts.app', ['title' => 'My Trainings | MCARE Trainer'])

@section('content')
<div class="mx-auto max-w-7xl space-y-7">
    <header class="border-b border-stone-200 pb-6">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">My trainings</p>
        <h1 class="mt-2 text-3xl font-bold text-stone-950">Batch training overview</h1>
        <p class="mt-2 text-stone-600">The same enrollment and training windows maintained by the admin, grouped by batch.</p>
    </header>
    @if($assignedBatch)
        <div class="border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950">
            <strong>Current assignment:</strong> {{ $assignedBatch->name }} {{ $assignedBatch->year }}.
            Teaching tools, rosters, schedules, classwork, and competency records are scoped to this batch.
        </div>
    @else
        <div class="border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <strong>No current batch is assigned.</strong> Ask an administrator to assign one before managing learners or publishing classwork.
        </div>
    @endif
    <div class="grid gap-5 lg:grid-cols-2">
        @forelse ($batches as $batch)
            <article class="border border-stone-200 bg-white p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="text-xl font-bold text-stone-950">{{ $batch->name }} {{ $batch->year }}</h2><p class="mt-1 text-sm text-stone-600">{{ $batch->trainingStateLabel() }}</p></div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <span class="px-3 py-1 text-xs font-bold {{ $batch->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">{{ $batch->is_active ? 'Active' : 'Inactive' }}</span>
                        @if($assignedBatch?->id === $batch->id)
                            <span class="px-3 py-1 text-xs font-bold bg-violet-100 text-violet-800">Assigned to you</span>
                        @elseif($batch->trainer)
                            <span class="px-3 py-1 text-xs font-bold bg-stone-100 text-stone-600">Assigned to {{ $batch->trainer->name }}</span>
                        @else
                            <span class="px-3 py-1 text-xs font-bold bg-amber-50 text-amber-800">Unassigned</span>
                        @endif
                    </div>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">Training window</dt><dd class="mt-2 font-semibold text-stone-900">{{ $batch->training_starts_at?->format('M d, Y') ?? 'Not set' }} — {{ $batch->training_ends_at?->format('M d, Y') ?? 'Open-ended' }}</dd></div>
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">Learners / modules</dt><dd class="mt-2 font-semibold text-stone-900">{{ $batch->applications_count }} learners · {{ $batch->modules_count }} modules</dd></div>
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">AM</dt><dd class="mt-2 font-semibold text-stone-900">{{ $batch->scheduleLabelFor('AM') }}</dd><p class="mt-1 text-sm text-stone-600">{{ $batch->roomFor('AM') ?: 'Room not set' }}</p></div>
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">PM</dt><dd class="mt-2 font-semibold text-stone-900">{{ $batch->scheduleLabelFor('PM') }}</dd><p class="mt-1 text-sm text-stone-600">{{ $batch->roomFor('PM') ?: 'Room not set' }}</p></div>
                </dl>
            </article>
        @empty
            <p class="border border-stone-200 bg-white p-6 text-stone-600">No training batch has been scheduled by the admin yet.</p>
        @endforelse
    </div>
</div>
@endsection
