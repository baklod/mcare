@extends('trainee.layouts.app', ['title' => 'Classwork | MCARE Trainee'])

@section('content')
@php
    $categories = collect($modules)->groupBy(function ($module) {
        return match($module->competency_category) {
            'core' => '1. Core Competencies (TESDA Caregiving NC II)',
            'common' => '2. Common Competencies',
            'basic' => '3. Basic Competencies',
            default => '4. Learning Materials & Modules',
        };
    })->sortKeys();

    $completedCount = collect($modules)->filter(
        fn ($module) => ($progressByModule->get($module->id)?->status === 'completed' || $progressByModule->get($module->id)?->competency_outcome === 'competent')
    )->count();
@endphp

<div class="lms-page" data-lms-classwork data-lms-role="trainee">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Classwork</p>
            <h1>Learning materials & modules</h1>
            <p>Study Caregiving NC II modules, review supplementary handouts, take assessments, and monitor your competency outcomes.</p>
        </div>
        <div class="lms-compact-progress" aria-label="{{ $completedCount }} of {{ collect($modules)->count() }} modules completed">
            <strong>{{ $completedCount }}/{{ collect($modules)->count() }}</strong>
            <span>completed</span>
        </div>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainee classroom sections">
        <a href="{{ route('trainee.stream') }}">Stream</a>
        <a href="{{ route('trainee.modules.index') }}" class="is-active" aria-current="page">Classwork</a>
        <a href="{{ route('trainee.schedule') }}">Calendar</a>
    </nav>

    <div class="lms-topic-list">
        @forelse($categories as $categoryName => $catModules)
            <section class="lms-topic-section">
                <header class="lms-topic-heading">
                    <h2>{{ $categoryName }}</h2>
                    <span>{{ $catModules->count() }} {{ str('unit')->plural($catModules->count()) }}</span>
                </header>
                <div class="lms-trainee-classwork-list">
                    @foreach($catModules->sortBy([['position', 'asc'], ['module_code', 'asc']]) as $module)
                        @php
                            $moduleProgress = $progressByModule->get($module->id);
                            $progressValue = (int) ($moduleProgress?->progress_percent ?? 0);
                            $isCompetent = $moduleProgress?->competency_outcome === 'competent';
                            $isCompleted = $moduleProgress?->status === 'completed' || $isCompetent;
                            $suppCount = count($module->supplementaryList());
                            $quizCount = $module->quizzes()->where('is_published', true)->count();
                        @endphp
                        <article class="lms-trainee-classwork-card">
                            <span class="lms-classwork-icon"><x-dashboard-icon name="book-open" /></span>
                            <div class="lms-classwork-main">
                                <div class="lms-classwork-title-line">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($module->module_code)
                                            <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                                {{ $module->module_code }}
                                            </span>
                                        @endif
                                        <h3 class="font-bold text-slate-900 text-base">
                                            <a href="{{ route('trainee.modules.show', $module) }}" class="hover:text-purple-700 transition">
                                                {{ $module->title }}
                                            </a>
                                        </h3>
                                    </div>
                                    <span class="lms-status-chip {{ $isCompetent ? 'is-green' : ($moduleProgress ? 'is-amber' : 'is-neutral') }}">
                                        {{ $isCompetent ? 'Competent (Passed)' : ($moduleProgress ? str($moduleProgress->status)->headline() : 'Not started') }}
                                    </span>
                                </div>

                                @if($module->topic)
                                    <p class="text-xs font-semibold text-purple-800">Learning Outcome: {{ $module->topic }}</p>
                                @endif

                                <p>{{ str($module->description)->limit(180) }}</p>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-user-avatar :user="$module->trainer" :name="$module->trainer?->name ?? 'MCARE Trainer'" class="grid h-6 w-6 place-items-center rounded-full bg-purple-100 text-[9px] font-black text-purple-800" />
                                        {{ $module->trainer?->name ?? 'MCARE Trainer' }}
                                    </span>
                                    <span>📅 Available {{ $module->available_at?->format('M d, Y') ?? 'now' }}</span>
                                    @if($module->due_at)<span>⏰ Due {{ $module->due_at->format('M d, g:i A') }}</span>@endif
                                    @if($suppCount > 0)
                                        <span class="font-semibold text-indigo-700">📎 {{ $suppCount }} attachments</span>
                                    @endif
                                    @if($quizCount > 0)
                                        <span class="font-semibold text-amber-700">📝 {{ $quizCount }} Assessment</span>
                                    @endif
                                </div>

                                <div class="lms-progress-track" aria-label="{{ $progressValue }} percent complete">
                                    <span style="width: {{ $progressValue }}%"></span>
                                </div>
                            </div>
                            <a href="{{ route('trainee.modules.show', $module) }}" class="primary-action text-xs py-2 px-4 shrink-0">
                                Open Module
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="lms-empty-state">
                <x-dashboard-icon name="book-open" />
                <h2>No classwork yet</h2>
                <p>Published learning materials will appear here when your trainer shares them.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
