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

    @if(session('status'))
        <div class="lms-inline-alert is-success" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px;">
            <strong>Success:</strong> {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="lms-inline-alert is-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px;">
            <strong>Notice:</strong> {{ session('error') }}
        </div>
    @endif

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
            @if($quiz->requires_time_in)
                <section class="lms-side-card" style="border-top: 3px solid #7c3aed; margin-bottom: 16px;">
                    <h2 style="color: #581c87; font-size: 15px; font-weight: 700; margin-bottom: 8px;">
                        Activity Attendance
                    </h2>

                    @if($attendance)
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; margin-top: 6px;">
                            <div style="color: #166534; font-weight: 700; font-size: 13px;">
                                &#10003; Recorded as Present
                            </div>
                            <p style="font-size: 11px; color: #15803d; margin: 4px 0 0;">
                                Timed in on {{ $attendance->timed_in_at?->format('M d, Y g:i A') ?? 'Confirmed' }}
                            </p>
                        </div>
                    @elseif($canTimeIn)
                        <p style="font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.4;">
                            This session requires an attendance check-in. You can time-in now before starting or completing the activity.
                        </p>
                        <form method="POST" action="{{ route('trainee.quizzes.time-in', $quiz) }}" style="margin-top: 10px;">
                            @csrf
                            <button type="submit" class="primary-action lms-full-action" style="background: #7c3aed; border-color: #7c3aed;">
                                Record Time-In
                            </button>
                        </form>
                    @else
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px; margin-top: 6px;">
                            <div style="color: #991b1b; font-weight: 700; font-size: 13px;">
                                Time-In Closed / Missed
                            </div>
                            <p style="font-size: 11px; color: #b91c1c; margin: 4px 0 0;">
                                The deadline for this activity has passed.
                            </p>
                        </div>
                    @endif
                </section>
            @endif

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

    <x-classroom-comments :commentable="$quiz" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
</div>
@endsection
