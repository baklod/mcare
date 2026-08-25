@extends('trainer.layouts.app', ['title' => 'Certificates | MCARE Trainer'])
@section('content')
<div class="mx-auto max-w-7xl space-y-7">
    <header class="border-b border-stone-200 pb-6">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Certificates</p>
        <h1 class="mt-2 text-3xl font-bold text-stone-950">Completion eligibility</h1>
        <p class="mt-2 text-stone-600">Readiness is based on each trainee's trainer-validated required core modules and competency outcomes, not the batch end date. Final document issuance remains controlled by the admin.</p>
    </header>

    <div class="overflow-hidden border border-stone-200 bg-white">
        @forelse($trainees as $trainee)
            @php
                $completion = $eligibilityByTrainee->get($trainee->id);
                $eligible = (bool) data_get($completion, 'eligible', false);
                $completedModules = (int) data_get($completion, 'counts.completed_modules', 0);
                $requiredModules = (int) data_get($completion, 'counts.modules', 0);
            @endphp
            <div class="flex flex-col gap-4 border-b border-stone-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <x-user-avatar :user="$trainee->user" :name="trim($trainee->first_name.' '.$trainee->last_name)" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800" />
                    <div class="min-w-0">
                        <p class="truncate font-bold text-stone-950">{{ $trainee->first_name }} {{ $trainee->last_name }}</p>
                        <p class="text-sm text-stone-500">{{ $completedModules }} of {{ $requiredModules }} required core modules trainer-validated</p>
                    </div>
                </div>
                <span class="w-fit px-3 py-1 text-xs font-bold {{ $eligible ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">
                    {{ $eligible ? 'Eligible for admin document review' : 'Requirements in progress' }}
                </span>
            </div>
        @empty
            <p class="p-6 text-stone-600">No approved trainees found.</p>
        @endforelse
    </div>
</div>
@endsection
