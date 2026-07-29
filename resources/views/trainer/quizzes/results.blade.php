@extends('trainer.layouts.app', ['title' => 'Quiz Results | MCARE Trainer'])

@section('content')
@php
    $submittedAttempts = collect($attempts->items());
    $averageScore = $submittedAttempts->whereNotNull('score_percent')->avg('score_percent');
    $passedCount = $submittedAttempts->where('passed', true)->count();
    $audienceLabel = $quiz->targetTrainee
        ? trim($quiz->targetTrainee->first_name.' '.$quiz->targetTrainee->last_name)
        : ($quiz->batch ? $quiz->batch->name.' '.$quiz->batch->year : 'Assigned class');
@endphp

<div class="lms-page" data-quiz-results>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz results</p>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $audienceLabel }} - {{ $quiz->questions->count() }} questions - Passing score {{ number_format((float) $quiz->passing_score_percent) }}%</p>
        </div>
        <div class="lms-header-actions">
            <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="secondary-action">Edit quiz</a>
            <a href="{{ route('trainer.assessments') }}" class="secondary-action">Back to quizzes</a>
        </div>
    </header>

    <section class="lms-metric-grid" aria-label="Quiz result summary">
        <article class="lms-metric-card">
            <span>Submissions</span>
            <strong>{{ $attempts->total() }}</strong>
            <small>Graded attempts</small>
        </article>
        <article class="lms-metric-card">
            <span>Average score</span>
            <strong>{{ $averageScore !== null ? number_format($averageScore, 1).'%' : '-' }}</strong>
            <small>Current page</small>
        </article>
        <article class="lms-metric-card">
            <span>Passed</span>
            <strong>{{ $passedCount }}</strong>
            <small>Current page</small>
        </article>
    </section>

    <section class="lms-results-panel" aria-labelledby="learner-results-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Learner work</p>
                <h2 id="learner-results-title">Submitted attempts</h2>
            </div>
        </div>

        <div class="lms-responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Learner</th>
                        <th>Attempt</th>
                        <th>Submitted</th>
                        <th>Points</th>
                        <th>Score</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $attempt)
                        @php
                            $learner = $attempt->application;
                            $learnerName = $learner
                                ? trim($learner->first_name.' '.$learner->last_name)
                                : ($attempt->application?->user?->name ?? 'Trainee');
                        @endphp
                        <tr>
                            <td data-label="Learner">
                                <strong>{{ $learnerName }}</strong>
                                <small>{{ $learner?->email ?? $learner?->user?->email }}</small>
                            </td>
                            <td data-label="Attempt">#{{ $attempt->attempt_number }}</td>
                            <td data-label="Submitted">{{ $attempt->submitted_at?->format('M d, Y g:i A') ?? '-' }}</td>
                            <td data-label="Points">{{ number_format((float) $attempt->earned_points, 1) }} / {{ number_format((float) $attempt->total_points, 1) }}</td>
                            <td data-label="Score"><strong>{{ number_format((float) $attempt->score_percent, 1) }}%</strong></td>
                            <td data-label="Result">
                                <span class="lms-status-chip {{ $attempt->passed ? 'is-green' : 'is-red' }}">
                                    {{ $attempt->passed ? 'Passed' : 'Needs review' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="lms-empty-state">
                                    <x-dashboard-icon name="clipboard-list" />
                                    <h2>No submissions yet</h2>
                                    <p>Scores will appear here after trainees submit the quiz.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attempts->hasPages())
            <div class="lms-pagination">{{ $attempts->links() }}</div>
        @endif
    </section>
</div>
@endsection
