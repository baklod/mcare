@extends('trainee.layouts.app', ['title' => 'Quiz Result | MCARE Trainee'])

@section('content')
@php
    $answers = collect($attempt->answers ?? []);
    $answerReviewAvailable = $answerReviewAvailable ?? false;
    $moduleReturnUrl = $quiz->training_module_id
        ? route('trainee.modules.show', $quiz->training_module_id).'#assessments'
        : route('trainee.modules.index');
@endphp

<div class="lms-page" data-quiz-result>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Attempt {{ $attempt->attempt_number }} result</p>
            <h1>{{ $quiz->title }}</h1>
            <p>Submitted {{ $attempt->submitted_at?->format('M d, Y g:i A') }}</p>
        </div>
        <a href="{{ $moduleReturnUrl }}" class="secondary-action">Back to module</a>
    </header>

    <section class="lms-result-hero {{ $attempt->passed ? 'is-passed' : 'is-review' }}">
        <div class="lms-result-score">
            <span>Your score</span>
            <strong>{{ number_format((float) $attempt->score_percent, 1) }}%</strong>
            <small>{{ number_format((float) $attempt->earned_points, 1) }} of {{ number_format((float) $attempt->total_points, 1) }} points</small>
        </div>
        <div>
            <p class="lms-eyebrow">{{ $attempt->passed ? 'Passed' : 'Keep learning' }}</p>
            <h2>{{ $attempt->passed ? 'Great work - you reached the passing score.' : 'Your score has been recorded.' }}</h2>
            <p>The passing score for this quiz is {{ number_format((float) $quiz->passing_score_percent) }}%.</p>
        </div>
    </section>

    <section class="lms-review-list" aria-labelledby="answer-review-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Answer review</p>
                <h2 id="answer-review-title">{{ $answerReviewAvailable ? 'Questions and answers' : 'Your submitted answers' }}</h2>
                @unless($answerReviewAvailable)
                    <p>Correctness and answer keys unlock after the deadline or your final allowed attempt.</p>
                @endunless
            </div>
        </div>

        @foreach($quiz->questions as $questionIndex => $question)
            @php
                $selectedIndex = $answers->get((string) $question->id, $answers->get($question->id));
                $selectedOption = is_numeric($selectedIndex) ? data_get($question->options, (int) $selectedIndex) : null;
                $correctOption = $answerReviewAvailable
                    ? data_get($question->options, (int) $question->correct_option)
                    : null;
                $isCorrect = $answerReviewAvailable
                    && (int) $selectedIndex === (int) $question->correct_option
                    && $selectedIndex !== null;
            @endphp
            <article class="lms-review-card {{ $answerReviewAvailable ? ($isCorrect ? 'is-correct' : 'is-incorrect') : '' }}">
                <header>
                    <span>Question {{ $questionIndex + 1 }}</span>
                    @if($answerReviewAvailable)
                        <span class="lms-status-chip {{ $isCorrect ? 'is-green' : 'is-red' }}">{{ $isCorrect ? 'Correct' : 'Incorrect' }}</span>
                    @else
                        <span class="lms-status-chip is-neutral">Recorded</span>
                    @endif
                </header>
                <h3>{{ $question->prompt }}</h3>
                <dl>
                    <div><dt>Your answer</dt><dd>{{ $selectedOption ?? 'No answer' }}</dd></div>
                    @if($answerReviewAvailable && ! $isCorrect)
                        <div><dt>Correct answer</dt><dd>{{ $correctOption }}</dd></div>
                    @endif
                </dl>
            </article>
        @endforeach
    </section>
</div>
@endsection
