@extends('trainee.layouts.app', ['title' => 'Take Quiz | MCARE Trainee'])

@section('content')
@php
    $questionCount = $quiz->questions->count();
@endphp

<div class="lms-page lms-quiz-taking-page" data-quiz-attempt data-remaining-seconds="{{ $remainingSeconds === null ? 'unlimited' : max(0, (int) $remainingSeconds) }}">
    <header class="lms-quiz-taking-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Attempt {{ $attempt->attempt_number }}</p>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $questionCount }} {{ str('question')->plural($questionCount) }} - Answer every item before submitting.</p>
        </div>
        <div class="lms-quiz-timer" data-quiz-timer role="timer" aria-live="polite">
            <span>Time remaining</span>
            <strong data-quiz-timer-value>{{ $remainingSeconds === null ? 'No limit' : '--:--' }}</strong>
        </div>
    </header>

    @if($errors->any())
        <div class="lms-inline-alert is-danger" role="alert">
            <strong>Your answers were not submitted.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('trainee.quiz-attempts.submit', $attempt) }}" enctype="multipart/form-data" class="lms-attempt-form" data-quiz-attempt-form data-confirm="Submit this quiz now? You will not be able to change this attempt after submission.">
        @csrf
        <nav class="lms-question-jump" aria-label="Jump to a quiz question">
            @foreach($quiz->questions as $questionIndex => $question)
                <a href="#quiz-question-{{ $question->id }}" data-question-jump>{{ $questionIndex + 1 }}</a>
            @endforeach
        </nav>

        <div class="lms-take-question-list">
            @foreach($quiz->questions as $questionIndex => $question)
                <fieldset class="lms-take-question" id="quiz-question-{{ $question->id }}" data-answer-question>
                    <legend>
                        <span>Question {{ $questionIndex + 1 }} of {{ $questionCount }}</span>
                        <strong>{{ $question->prompt }}</strong>
                        <small>{{ number_format((float) $question->points, 1) }} {{ str('point')->plural((float) $question->points) }}</small>
                    </legend>

                    @if($question->isFileUpload())
                        <div class="lms-activity-upload rounded-2xl border-2 border-dashed border-purple-200 bg-purple-50/40 p-5 text-center transition hover:border-purple-300 hover:bg-purple-50">
                            <x-dashboard-icon name="upload-cloud" class="mx-auto h-8 w-8 text-purple-600" />
                            <p class="mt-2 text-sm font-bold text-slate-900">Upload your completed activity document</p>
                            <p class="mt-1 text-xs text-slate-500">Accepted formats: <strong>Word Document (.docx, .doc), PDF (.pdf), or image (.png, .jpg)</strong> (Max 20MB, No .zip)</p>
                            <div class="mt-4 flex justify-center">
                                <input type="file" name="file_answers[{{ $question->id }}]" accept=".docx,.doc,.pdf,.png,.jpg,.jpeg" class="lms-activity-file-input block w-full max-w-md text-xs text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-purple-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white file:transition hover:file:bg-purple-700 cursor-pointer">
                            </div>
                        </div>
                    @elseif($question->isEnumeration())
                        <div class="space-y-3">
                            <textarea name="text_answers[{{ $question->id }}]" rows="4" maxlength="5000" class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs text-slate-800 focus:border-purple-300 focus:ring-4 focus:ring-purple-100" placeholder="Type your enumeration or written answers here...">{{ old('text_answers.'.$question->id) }}</textarea>
                            <div class="lms-activity-upload rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs">
                                <span class="font-bold text-slate-700">Or attach your activity document (.docx, .doc, .pdf, images):</span>
                                <input type="file" name="file_answers[{{ $question->id }}]" accept=".docx,.doc,.pdf,.png,.jpg,.jpeg" class="lms-activity-file-input mt-2 block w-full text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-purple-100 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-purple-800 hover:file:bg-purple-200">
                            </div>
                        </div>
                    @else
                        <div class="lms-answer-options">
                            @foreach($question->options as $optionIndex => $option)
                                <label class="lms-answer-option">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $optionIndex }}" @checked((string) old('answers.'.$question->id) === (string) $optionIndex)>
                                    <span class="lms-answer-letter">{{ chr(65 + $optionIndex) }}</span>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
            @endforeach
        </div>

        <footer class="lms-sticky-submit">
            <span class="lms-answer-progress" data-answer-progress>0 of {{ $questionCount }} answered</span>
            <button class="primary-action" data-submit-quiz>Review and submit</button>
        </footer>
    </form>
</div>
@endsection
