@extends('trainee.layouts.app', ['title' => 'Graduate Home | MCARE'])

@section('content')
<section class="space-y-7">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Graduate account</p>
        <div class="mt-3 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div><h1 class="dashboard-section-title">Welcome back, {{ $application->first_name }}.</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Your MCARE training history, completion records, and graduate opportunities stay in the same account.</p></div>
            <a href="{{ route('trainee.career-hub') }}" class="primary-action inline-flex items-center gap-2 self-start"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Open Career Hub</a>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="dashboard-panel"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Training status</p><p class="mt-2 text-xl font-black text-slate-950">{{ $application->learningStatusLabel() }}</p><p class="mt-1 text-sm text-slate-500">Your account keeps its training record.</p></article>
        <article class="dashboard-panel"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Module history</p><p class="mt-2 text-xl font-black text-slate-950">{{ $stats['modules'] }} modules</p><p class="mt-1 text-sm text-slate-500">{{ $stats['progress'] }}% recorded completion</p></article>
        <article class="dashboard-panel"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Documents</p><p class="mt-2 text-xl font-black text-slate-950">{{ $stats['documents'] }} files</p><a href="{{ route('trainee.documents') }}" class="mt-1 inline-flex text-sm font-bold text-purple-700 hover:text-purple-900">View certificates and records</a></article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="dashboard-panel" aria-labelledby="graduate-history-title">
            <div class="flex items-center justify-between gap-3"><div><p class="dashboard-section-kicker">Completed training</p><h2 id="graduate-history-title" class="mt-1 text-2xl font-black text-slate-950">Your MCARE history</h2></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Verified graduate</span></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Program</p><p class="mt-1 font-bold text-slate-950">{{ $application->program }}</p></div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Batch</p><p class="mt-1 font-bold text-slate-950">{{ $batch ? $batch->name.' '.$batch->year : 'Recorded training batch' }}</p></div>
            </div>
            <p class="mt-5 text-sm leading-6 text-slate-600">Payments, quizzes, and active learning controls are closed after graduation. Your records remain available under Documents.</p>
        </section>
        <section class="dashboard-panel" aria-labelledby="graduate-jobs-title">
            <div class="flex items-center justify-between gap-3"><h2 id="graduate-jobs-title" class="text-xl font-black text-slate-950">Latest opportunities</h2><a href="{{ route('trainee.career-hub') }}" class="text-sm font-bold text-purple-700">View all</a></div>
            <div class="mt-4 space-y-3">
                @forelse($careerJobs as $job)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="font-bold text-slate-950">Start {{ $job->estimated_start_date->format('M d, Y') }}</p><p class="mt-1 text-sm text-slate-600">{{ $job->patientGenderLabel() }} · {{ $job->mobilityStatusLabel() }}</p></div>
                @empty
                    <p class="text-sm leading-6 text-slate-500">No open caregiving duties yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
@endsection
