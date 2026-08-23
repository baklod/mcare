@extends('trainee.layouts.app', ['title' => 'Quiz Details | MCARE Trainee'])

@section('content')
@php
    $attemptCollection = collect($attempts);
    $inProgressAttempt = $attemptCollection->firstWhere('status', 'in_progress');
    $attemptsRemaining = max(0, (int) $quiz->attempt_limit - $attemptCollection->count());
    $isUpcoming = $quiz->available_at && $quiz->available_at->isFuture();
    $isClosed = $quiz->due_at && $quiz->due_at->isPast();
    $moduleReturnUrl = $quiz->training_module_id
        ? route('trainee.modules.show', $quiz->training_module_id).'#assessments'
        : route('trainee.modules.index');
@endphp

<div class="lms-page" data-quiz-detail>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz details</p>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $quiz->batch?->name ?? 'Assigned class' }} - Posted by {{ $quiz->trainer?->name ?? 'MCARE Trainer' }}</p>
        </div>
        <a href="{{ $moduleReturnUrl }}" class="secondary-action">Back to module</a>
    </header>

    <div class="lms-detail-layout">
        <main class="lms-detail-main">
            <section class="lms-detail-card">
                <div class="lms-detail-icon"><x-dashboard-icon name="clipboard-list" /></div>
                <div>
                    <p class="lms-eyebrow">Instructions</p>
                    <h2>Before you begin</h2>
                    <div class="lms-rich-copy">
                        {!! nl2br(e($quiz->instructions ?: 'Answer every question, review your choices, and submit before the timer ends.')) !!}
                    </div>
                </div>
            </section>

            <section class="lms-results-panel" aria-labelledby="attempt-history-title">
                <div class="lms-section-heading">
                    <div>
                        <p class="lms-eyebrow">Your work</p>
                        <h2 id="attempt-history-title">Attempt history</h2>
                    </div>
                </div>
                <div class="lms-attempt-list">
                    @forelse($attemptCollection->sortByDesc('attempt_number') as $attempt)
                        <article class="lms-attempt-row">
                            <span class="lms-attempt-number">#{{ $attempt->attempt_number }}</span>
                            <div>
                                <strong>{{ $attempt->status === 'graded' ? 'Submitted' : 'In progress' }}</strong>
                                <small>{{ ($attempt->submitted_at ?? $attempt->started_at)?->format('M d, Y g:i A') }}</small>
                            </div>
                            @if($attempt->status === 'graded')
                                <span class="lms-score-badge {{ $attempt->passed ? 'is-passed' : 'is-review' }}">{{ number_format((float) $attempt->score_percent, 1) }}%</span>
                                <a href="{{ route('trainee.quiz-attempts.result', $attempt) }}" class="lms-text-action">View result</a>
                            @else
                                <a href="{{ route('trainee.quiz-attempts.show', $attempt) }}" class="primary-action">Continue</a>
                            @endif
                        </article>
                    @empty
                        <p class="lms-empty-copy">You have not started this quiz yet.</p>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="lms-detail-sidebar">
            <section class="lms-side-card">
                <h2>Quiz summary</h2>
                <dl class="lms-summary-list">
                    <div><dt>Questions</dt><dd>{{ $quiz->questions->count() }}</dd></div>
                    <div><dt>Time limit</dt><dd>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutes' : 'No limit' }}</dd></div>
                    <div><dt>Attempts</dt><dd>{{ $attemptsRemaining }} remaining</dd></div>
                    <div><dt>Passing score</dt><dd>{{ number_format((float) $quiz->passing_score_percent) }}%</dd></div>
                    <div><dt>Available</dt><dd>{{ $quiz->available_at?->format('M d, Y g:i A') ?? 'Now' }}</dd></div>
                    <div><dt>Due</dt><dd>{{ $quiz->due_at?->format('M d, Y g:i A') ?? 'No deadline' }}</dd></div>
                </dl>

                @if($inProgressAttempt)
                    <a href="{{ route('trainee.quiz-attempts.show', $inProgressAttempt) }}" class="primary-action lms-full-action">Continue attempt</a>
                @elseif($canStart)
                    <form method="POST" action="{{ route('trainee.quizzes.start', $quiz) }}" data-confirm="Start this quiz now? The timer begins as soon as the attempt opens.">
                        @csrf
                        <button class="primary-action lms-full-action">Start quiz</button>
                    </form>
                @else
                    <button class="primary-action lms-full-action" disabled>
                        {{ $isUpcoming ? 'Not available yet' : ($isClosed ? 'Quiz closed' : 'No attempts left') }}
                    </button>
                @endif
                <p class="lms-side-note">Your timer continues if you close or refresh the page.</p>
            </section>
        </aside>
    </div>
</div>
@endsection
