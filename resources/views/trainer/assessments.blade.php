@extends('trainer.layouts.app', ['title' => 'Quizzes | MCARE Trainer'])

@section('content')
@php
    $stats = array_merge([
        'total' => method_exists($quizzes, 'total') ? $quizzes->total() : collect($quizzes)->count(),
        'published' => collect($quizzes)->where('is_published', true)->count(),
        'submissions' => collect($quizzes)->sum('attempts_count'),
        'to_review' => 0,
    ], $quizStats ?? []);
@endphp

<div class="lms-page" data-quiz-library>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">MCARE Classroom</p>
            <h1>Quizzes and assessments</h1>
            <p>Create mobile-friendly checks for understanding, publish them to a class or learner, and review results from one place.</p>
        </div>
        <a href="{{ route('trainer.quizzes.create') }}" class="primary-action">Create quiz</a>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainer classroom sections">
        @if(\Illuminate\Support\Facades\Route::has('trainer.stream'))<a href="{{ route('trainer.stream') }}">Stream</a>@endif
        <a href="{{ route('trainer.resources') }}">Classwork</a>
        <a href="{{ route('trainer.trainees') }}">People</a>
        <a href="{{ route('trainer.assessments') }}" class="is-active" aria-current="page">Quizzes</a>
    </nav>

    <section class="lms-metric-grid" aria-label="Quiz summary">
        <article class="lms-metric-card"><span>Total quizzes</span><strong>{{ $stats['total'] }}</strong><small>Draft and published</small></article>
        <article class="lms-metric-card"><span>Published</span><strong>{{ $stats['published'] }}</strong><small>Visible to trainees</small></article>
        <article class="lms-metric-card"><span>Submissions</span><strong>{{ $stats['submissions'] }}</strong><small>Graded automatically</small></article>
        <article class="lms-metric-card"><span>To review</span><strong>{{ $stats['to_review'] }}</strong><small>Needs attention</small></article>
    </section>

    <section aria-labelledby="quiz-list-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Assessment library</p>
                <h2 id="quiz-list-title">Your quizzes</h2>
            </div>
            <a href="{{ route('trainer.quizzes.create') }}" class="secondary-action">New quiz</a>
        </div>

        <div class="lms-quiz-grid">
            @forelse($quizzes as $quiz)
                @php
                    $questionCount = $quiz->questions_count ?? $quiz->questions?->count() ?? 0;
                    $attemptCount = $quiz->attempts_count ?? $quiz->attempts?->count() ?? 0;
                    $targetName = $quiz->targetTrainee
                        ? trim($quiz->targetTrainee->first_name.' '.$quiz->targetTrainee->last_name)
                        : null;
                @endphp
                <article class="lms-quiz-card" data-quiz-card>
                    <header>
                        <span class="lms-quiz-icon"><x-dashboard-icon name="square-check" /></span>
                        <span class="lms-status-chip {{ $quiz->is_published ? 'is-green' : 'is-neutral' }}">{{ $quiz->is_published ? 'Published' : 'Draft' }}</span>
                    </header>
                    <div class="lms-quiz-card-body">
                        <p class="lms-module-topic">{{ $quiz->batch ? $quiz->batch->name.' '.$quiz->batch->year : 'General class' }}</p>
                        <h2>{{ $quiz->title }}</h2>
                        <p>{{ filled($quiz->instructions) ? str($quiz->instructions)->limit(150) : 'No additional instructions.' }}</p>
                    </div>
                    <dl class="lms-quiz-facts">
                        <div><dt>Questions</dt><dd>{{ $questionCount }}</dd></div>
                        <div><dt>Time limit</dt><dd>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'None' }}</dd></div>
                        <div><dt>Attempts</dt><dd>{{ $quiz->attempt_limit ?? 1 }}</dd></div>
                        <div><dt>Passing score</dt><dd>{{ $quiz->passing_score_percent ?? 75 }}%</dd></div>
                    </dl>
                    <div class="lms-quiz-audience">
                        <x-dashboard-icon name="users" />
                        <span>{{ $targetName ?: ($quiz->batch ? 'Entire class' : 'Assigned learners') }}</span>
                    </div>
                    <div class="lms-quiz-deadline">
                        <span>Due</span>
                        <strong>{{ $quiz->due_at?->format('M d, Y g:i A') ?? 'No due date' }}</strong>
                    </div>
                    <footer class="lms-card-footer">
                        <a href="{{ route('trainer.quizzes.results', $quiz) }}" class="lms-text-action">{{ $attemptCount }} {{ \Illuminate\Support\Str::plural('submission', $attemptCount) }}</a>
                        <div class="lms-card-actions">
                            <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="lms-text-action">Edit</a>
                            <form method="POST" action="{{ route('trainer.quizzes.publication', $quiz) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_published" value="{{ $quiz->is_published ? 0 : 1 }}">
                                <button class="lms-text-action">{{ $quiz->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <form method="POST" action="{{ route('trainer.quizzes.destroy', $quiz) }}" data-confirm="Delete '{{ $quiz->title }}' and all of its saved attempts?">
                                @csrf
                                @method('DELETE')
                                <button class="lms-text-action is-danger">Delete</button>
                            </form>
                        </div>
                    </footer>
                </article>
            @empty
                <div class="lms-empty-state lms-grid-empty">
                    <x-dashboard-icon name="square-check" />
                    <h2>No quizzes yet</h2>
                    <p>Start with a short knowledge check using multiple-choice or true-or-false questions.</p>
                    <a href="{{ route('trainer.quizzes.create') }}" class="primary-action">Create first quiz</a>
                </div>
            @endforelse
        </div>

        @if(method_exists($quizzes, 'hasPages') && $quizzes->hasPages())
            <div class="lms-pagination">{{ $quizzes->links() }}</div>
        @endif
    </section>
</div>
@endsection
