@extends('trainee.layouts.app', ['title' => 'Classwork | MCARE Trainee'])

@section('content')
@php
    $topics = collect($modules)->groupBy(fn ($module) => $module->topic ?: 'Learning materials');
    $completedCount = collect($modules)->filter(
        fn ($module) => $progressByModule->get($module->id)?->status === 'completed'
    )->count();
@endphp

<div class="lms-page" data-lms-classwork data-lms-role="trainee">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Classwork</p>
            <h1>Learning materials</h1>
            <p>Review modules in their assigned order and keep your completion record up to date.</p>
        </div>
        <div class="lms-compact-progress" aria-label="{{ $completedCount }} of {{ collect($modules)->count() }} modules completed">
            <strong>{{ $completedCount }}/{{ collect($modules)->count() }}</strong>
            <span>completed</span>
        </div>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainee classroom sections">
        <a href="{{ route('trainee.stream') }}">Stream</a>
        <a href="{{ route('trainee.modules.index') }}" class="is-active" aria-current="page">Classwork</a>
        <a href="{{ route('trainee.quizzes.index') }}">Quizzes</a>
        <a href="{{ route('trainee.schedule') }}">Calendar</a>
    </nav>

    <div class="lms-topic-list">
        @forelse($topics as $topic => $topicModules)
            <section class="lms-topic-section">
                <header class="lms-topic-heading">
                    <h2>{{ $topic }}</h2>
                    <span>{{ $topicModules->count() }} {{ str('item')->plural($topicModules->count()) }}</span>
                </header>
                <div class="lms-trainee-classwork-list">
                    @foreach($topicModules->sortBy([['position', 'asc'], ['available_at', 'asc']]) as $module)
                        @php
                            $moduleProgress = $progressByModule->get($module->id);
                            $progressValue = (int) ($moduleProgress?->progress_percent ?? 0);
                            $isCompleted = $moduleProgress?->status === 'completed';
                        @endphp
                        <article class="lms-trainee-classwork-card">
                            <span class="lms-classwork-icon"><x-dashboard-icon name="book-open" /></span>
                            <div class="lms-classwork-main">
                                <div class="lms-classwork-title-line">
                                    <h3>{{ $module->title }}</h3>
                                    <span class="lms-status-chip {{ $isCompleted ? 'is-green' : ($moduleProgress ? 'is-amber' : 'is-neutral') }}">
                                        {{ $moduleProgress ? str($moduleProgress->status)->headline() : 'Not started' }}
                                    </span>
                                </div>
                                <p>{{ str($module->description)->limit(180) }}</p>
                                <div class="lms-classwork-meta">
                                    <span>{{ $module->trainer?->name ?? 'MCARE Trainer' }}</span>
                                    <span>Available {{ $module->available_at?->format('M d, Y') ?? 'now' }}</span>
                                    @if($module->due_at)<span>Due {{ $module->due_at->format('M d, g:i A') }}</span>@endif
                                </div>
                                <div class="lms-progress-track" aria-label="{{ $progressValue }} percent complete">
                                    <span style="width: {{ $progressValue }}%"></span>
                                </div>
                            </div>
                            <a href="{{ route('trainee.modules.show', $module) }}" class="secondary-action">Open</a>
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
