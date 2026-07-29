@extends('trainee.layouts.app', ['title' => 'Quizzes | MCARE Trainee'])

@section('content')
@php
    $quizCollection = method_exists($quizzes, 'getCollection') ? $quizzes->getCollection() : collect($quizzes);
    $availableCount = $quizCollection->filter(fn ($quiz) => $quiz->isOpenAt())->count();
@endphp

<div class="lms-page" data-quiz-library data-lms-role="trainee">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quizzes</p>
            <h1>Knowledge checks</h1>
            <p>See what is available, review your attempt history, and open each assessment when you are ready.</p>
        </div>
        <div class="lms-compact-progress">
            <strong>{{ $availableCount }}</strong>
            <span>available now</span>
        </div>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainee classroom sections">
        <a href="{{ route('trainee.stream') }}">Stream</a>
        <a href="{{ route('trainee.modules.index') }}">Classwork</a>
        <a href="{{ route('trainee.quizzes.index') }}" class="is-active" aria-current="page">Quizzes</a>
        <a href="{{ route('trainee.schedule') }}">Calendar</a>
    </nav>

    <section class="lms-trainee-quiz-grid" aria-label="Assigned quizzes">
        @forelse($quizzes as $quiz)
            @php
                $attempts = collect(data_get($attemptsByQuiz, $quiz->id, []));
                $latestAttempt = $attempts->sortByDesc('attempt_number')->first();
                $gradedAttempt = $attempts->where('status', 'graded')->sortByDesc('attempt_number')->first();
                $attemptsRemaining = max(0, (int) $quiz->attempt_limit - $attempts->count());
                $isUpcoming = $quiz->available_at && $quiz->available_at->isFuture();
                $isClosed = $quiz->due_at && $quiz->due_at->isPast();
                $statusLabel = $isUpcoming ? 'Upcoming' : ($isClosed ? 'Closed' : 'Available');
            @endphp
            <article class="lms-trainee-quiz-card" data-quiz-card>
                <div class="lms-quiz-card-top">
                    <span class="lms-quiz-icon"><x-dashboard-icon name="square-check" /></span>
                    <span class="lms-status-chip {{ $isUpcoming ? 'is-amber' : ($isClosed ? 'is-neutral' : 'is-green') }}">{{ $statusLabel }}</span>
                </div>
                <p class="lms-module-topic">{{ $quiz->batch?->name ?? 'Assigned class' }}</p>
                <h2>{{ $quiz->title }}</h2>
                <p>{{ str($quiz->instructions ?: 'Open the quiz to review its instructions.')->limit(150) }}</p>

                <dl class="lms-quiz-meta-grid">
                    <div><dt>Questions</dt><dd>{{ $quiz->questions_count ?? $quiz->questions?->count() ?? '-' }}</dd></div>
                    <div><dt>Time limit</dt><dd>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'None' }}</dd></div>
                    <div><dt>Due</dt><dd>{{ $quiz->due_at?->format('M d, g:i A') ?? 'No deadline' }}</dd></div>
                    <div><dt>Attempts left</dt><dd>{{ $attemptsRemaining }}</dd></div>
                </dl>

                @if($gradedAttempt)
                    <div class="lms-latest-result {{ $gradedAttempt->passed ? 'is-passed' : 'is-review' }}">
                        <span>Latest score</span>
                        <strong>{{ number_format((float) $gradedAttempt->score_percent, 1) }}%</strong>
                    </div>
                @elseif($latestAttempt?->status === 'in_progress')
                    <div class="lms-inline-alert is-info">
                        <strong>Attempt in progress</strong>
                        <span>Continue before its timer ends.</span>
                    </div>
                @endif

                <footer class="lms-card-footer">
                    <span class="lms-muted-note">{{ $attempts->count() }} of {{ $quiz->attempt_limit }} attempts used</span>
                    @if($latestAttempt?->status === 'in_progress')
                        <a href="{{ route('trainee.quiz-attempts.show', $latestAttempt) }}" class="primary-action">Continue</a>
                    @else
                        <a href="{{ route('trainee.quizzes.show', $quiz) }}" class="secondary-action">View details</a>
                    @endif
                </footer>
            </article>
        @empty
            <div class="lms-empty-state lms-grid-empty">
                <x-dashboard-icon name="square-check" />
                <h2>No quizzes assigned</h2>
                <p>Your trainer's published quizzes will appear here.</p>
            </div>
        @endforelse
    </section>

    @if(method_exists($quizzes, 'hasPages') && $quizzes->hasPages())
        <div class="lms-pagination">{{ $quizzes->links() }}</div>
    @endif
</div>
@endsection
