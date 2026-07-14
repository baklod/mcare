@extends('trainee.layouts.app', ['title' => 'My Modules | MCARE Trainee'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">My modules</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Learning materials</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Open only the lessons published for your batch or specifically assigned to you.</p>
    </header>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($modules as $module)
            @php
                $moduleProgress = $progressByModule->get($module->id);
            @endphp
            <article class="dashboard-card p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-purple-600">{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General module' }}</p>
                        <h2 class="mt-2 font-display text-xl font-black leading-tight text-slate-900">{{ $module->title }}</h2>
                    </div>
                    <span class="dashboard-pill {{ $moduleProgress?->status === 'completed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($moduleProgress ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-50 text-slate-600 ring-slate-100') }}">{{ $moduleProgress ? str($moduleProgress->status)->headline() : 'Not started' }}</span>
                </div>
                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                <p class="mt-3 text-xs font-semibold text-slate-400">Trainer: {{ $module->trainer?->name ?? 'MCARE Trainer' }}</p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-purple-600" style="width: {{ $moduleProgress?->progress_percent ?? 0 }}%"></div></div>
                <p class="mt-2 text-xs font-bold text-slate-500">{{ $moduleProgress?->progress_percent ?? 0 }}% recorded</p>
                <a href="{{ route('trainee.modules.show', $module) }}" class="secondary-action mt-5 w-full">Open protected viewer</a>
            </article>
        @empty
            <div class="dashboard-card p-10 text-center md:col-span-2 xl:col-span-3">
                <p class="font-display text-xl font-black text-slate-900">No modules yet</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Your trainer's published materials will appear here.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
