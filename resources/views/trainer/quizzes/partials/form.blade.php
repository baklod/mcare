@php
    $editing = ($quiz?->exists ?? false);
    $existingQuestions = $quiz?->questions?->map(function ($question) {
        return [
            'id' => $question->id,
            'type' => $question->type,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'correct_option' => $question->correct_option,
            'options' => collect($question->options ?? [])->values()->all(),
        ];
    })->values()->all() ?? [];
    $questionRows = old('questions', $existingQuestions);
    if (count($questionRows) === 0) {
        $questionRows = [[
            'id' => null,
            'type' => 'multiple_choice',
            'prompt' => '',
            'points' => 1,
            'correct_option' => 0,
            'options' => ['', '', '', ''],
        ]];
    }
    $isPrivate = filled(old('target_enrollment_application_id', $quiz?->target_enrollment_application_id));
    $moduleReturnUrl = $quiz?->training_module_id
        ? route('trainer.modules.show', $quiz->training_module_id).'#assessments'
        : route('trainer.resources');
@endphp

<div class="lms-page" data-quiz-builder>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz builder</p>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageDescription }}</p>
        </div>
        <a href="{{ $moduleReturnUrl }}" class="secondary-action">Back to module</a>
    </header>

    @if($errors->any())
        <div class="lms-inline-alert is-danger" role="alert">
            <strong>The quiz was not saved.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="lms-builder-form">
        @csrf
        @if($formMethod !== 'POST') @method($formMethod) @endif

        <section class="lms-builder-section" aria-labelledby="quiz-details-heading">
            <div class="lms-builder-section-heading">
                <span>1</span>
                <div><h2 id="quiz-details-heading">Quiz details</h2><p>Name the quiz and explain what trainees should do.</p></div>
            </div>
            <div class="lms-form-grid">
                <div class="lms-field lms-field-wide">
                    <label for="quiz-title">Title</label>
                <input id="quiz-title" name="title" value="{{ old('title', $quiz?->title) }}" required maxlength="160" placeholder="Example: Infection Control Knowledge Check">
                    @error('title')<p class="lms-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="lms-field lms-field-wide">
                    <label for="quiz-module">Learning Module</label>
                    <select id="quiz-module" name="training_module_id" class="form-field" required>
                        <option value="">Choose a learning module</option>
                        @foreach(($modules ?? []) as $mod)
                            <option value="{{ $mod->id }}" @selected((string) old('training_module_id', $quiz?->training_module_id) === (string) $mod->id)>
                                {{ $mod->module_code ? '['.$mod->module_code.'] ' : '' }}{{ $mod->title }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-xs text-slate-500">Every assessment belongs to one learning module.</small>
                    @error('training_module_id')<p class="lms-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="lms-builder-section" aria-labelledby="quiz-audience-heading">
            <div class="lms-builder-section-heading">
                <span>2</span>
                <div><h2 id="quiz-audience-heading">Class and availability</h2><p>Choose who receives the quiz and when it can be taken.</p></div>
            </div>
            <div class="lms-form-grid">
                <fieldset class="lms-audience-fieldset lms-field-wide" data-audience-scope>
                    <legend>Assign to</legend>
                    <div class="lms-choice-grid">
                        <label class="lms-choice-card">
                            <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control @checked(! $isPrivate)> Entire class</span>
                            <span class="lms-choice-help">Every active trainee in one batch</span>
                            <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                                <option value="">Choose class</option>
                                @foreach($batches as $batch)
                                    <option value="{{ $batch->id }}" @selected((string) old('training_batch_id', $quiz?->training_batch_id) === (string) $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="lms-choice-card">
                            <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control @checked($isPrivate)> Specific trainee</span>
                            <span class="lms-choice-help">Private assessment or retake</span>
                            <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                                <option value="">Choose approved trainee</option>
                                @foreach($trainees as $trainee)
                                    <option value="{{ $trainee->id }}" @selected((string) old('target_enrollment_application_id', $quiz?->target_enrollment_application_id) === (string) $trainee->id)>{{ $trainee->last_name }}, {{ $trainee->first_name }} - {{ $trainee->batch?->name ?? 'No class' }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </fieldset>
                <div class="lms-field"><label for="quiz-available-at">Available from</label><input id="quiz-available-at" type="datetime-local" name="available_at" value="{{ old('available_at', $quiz?->available_at?->format('Y-m-d\TH:i')) }}"></div>
                <div class="lms-field"><label for="quiz-due-at">Due date</label><input id="quiz-due-at" type="datetime-local" name="due_at" value="{{ old('due_at', $quiz?->due_at?->format('Y-m-d\TH:i')) }}"></div>
                <div class="lms-field"><label for="quiz-time-limit">Time limit (minutes)</label><input id="quiz-time-limit" type="number" name="time_limit_minutes" value="{{ old('time_limit_minutes', $quiz?->time_limit_minutes ?? 30) }}" min="1" max="240"></div>
                <div class="lms-field"><label for="quiz-attempt-limit">Allowed attempts</label><input id="quiz-attempt-limit" type="number" name="attempt_limit" value="{{ old('attempt_limit', $quiz?->attempt_limit ?? 1) }}" min="1" max="5"></div>
                <div class="lms-field"><label for="quiz-passing-score">Passing score (%)</label><input id="quiz-passing-score" type="number" name="passing_score_percent" value="{{ old('passing_score_percent', $quiz?->passing_score_percent ?? 75) }}" min="1" max="100"></div>
                <div class="lms-form-options">
                    <label class="lms-check"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $quiz?->is_published ?? false))><span>Publish after saving</span></label>
                </div>
            </div>
        </section>

        <section class="lms-builder-section" aria-labelledby="quiz-questions-heading">
            <div class="lms-builder-section-heading">
                <span>3</span>
                <div><h2 id="quiz-questions-heading">Questions</h2><p>Add multiple-choice or true-or-false questions and choose the correct answer for each one.</p></div>
            </div>

            <div class="lms-question-list" data-quiz-question-list>
                @foreach($questionRows as $questionIndex => $question)
                    @php
                        $questionType = data_get($question, 'type', 'multiple_choice');
                        $questionOptions = array_values(data_get($question, 'options', []));
                        if ($questionType === 'true_false') $questionOptions = ['True', 'False'];
                        while (count($questionOptions) < ($questionType === 'true_false' ? 2 : 4)) $questionOptions[] = '';
                    @endphp
                    <article class="lms-question-card" data-quiz-question data-question-index="{{ $questionIndex }}">
                        <header>
                            <span class="lms-question-number">Question {{ $questionIndex + 1 }}</span>
                            <button type="button" class="lms-text-action is-danger" data-remove-question>Remove</button>
                        </header>
                        <input type="hidden" name="questions[{{ $questionIndex }}][id]" value="{{ data_get($question, 'id') }}">
                        <div class="lms-form-grid">
                            <div class="lms-field">
                                <label for="question-{{ $questionIndex }}-type">Question type</label>
                                <select id="question-{{ $questionIndex }}-type" name="questions[{{ $questionIndex }}][type]" data-question-type>
                                    <option value="multiple_choice" @selected($questionType === 'multiple_choice')>Multiple choice</option>
                                    <option value="true_false" @selected($questionType === 'true_false')>True or false</option>
                                </select>
                            </div>
                            <div class="lms-field">
                                <label for="question-{{ $questionIndex }}-points">Points</label>
                                <input id="question-{{ $questionIndex }}-points" type="number" name="questions[{{ $questionIndex }}][points]" value="{{ data_get($question, 'points', 1) }}" min="1" max="100" required>
                            </div>
                            <div class="lms-field lms-field-wide">
                                <label for="question-{{ $questionIndex }}-prompt">Question</label>
                                <textarea id="question-{{ $questionIndex }}-prompt" name="questions[{{ $questionIndex }}][prompt]" rows="3" maxlength="1200" required>{{ data_get($question, 'prompt') }}</textarea>
                            </div>
                        </div>
                        <fieldset class="lms-option-fieldset">
                            <legend>Answer options</legend>
                            <div class="lms-option-list" data-quiz-option-list>
                                @foreach($questionOptions as $optionIndex => $option)
                                    <div class="lms-option-row" data-quiz-option>
                                        <span class="lms-option-letter" aria-hidden="true">{{ chr(65 + $optionIndex) }}</span>
                                        <label class="sr-only" for="question-{{ $questionIndex }}-option-{{ $optionIndex }}">Option {{ $optionIndex + 1 }}</label>
                                        <input id="question-{{ $questionIndex }}-option-{{ $optionIndex }}" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}]" value="{{ $option }}" required @readonly($questionType === 'true_false')>
                                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option {{ $optionIndex + 1 }}">x</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="lms-text-action" data-add-option @if($questionType === 'true_false') hidden @endif>Add option</button>
                        </fieldset>
                        <div class="lms-field lms-correct-answer">
                            <label for="question-{{ $questionIndex }}-correct">Correct answer</label>
                            <select id="question-{{ $questionIndex }}-correct" name="questions[{{ $questionIndex }}][correct_option]" data-correct-option>
                                @foreach($questionOptions as $optionIndex => $option)
                                    <option value="{{ $optionIndex }}" @selected((int) data_get($question, 'correct_option', 0) === $optionIndex)>Option {{ chr(65 + $optionIndex) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </article>
                @endforeach
            </div>

            <button type="button" class="lms-add-question" data-add-question>
                <span aria-hidden="true">+</span>
                <span><strong>Add question</strong><small>Multiple choice or true/false</small></span>
            </button>
        </section>

        <div class="lms-sticky-submit">
            <a href="{{ $moduleReturnUrl }}" class="secondary-action">Cancel</a>
            <button class="primary-action">{{ $submitLabel }}</button>
        </div>
    </form>

    <template data-quiz-question-template>
        <article class="lms-question-card" data-quiz-question data-question-index="__INDEX__">
            <header><span class="lms-question-number">Question __NUMBER__</span><button type="button" class="lms-text-action is-danger" data-remove-question>Remove</button></header>
            <input type="hidden" name="questions[__INDEX__][id]" value="">
            <div class="lms-form-grid">
                <div class="lms-field"><label for="question-__INDEX__-type">Question type</label><select id="question-__INDEX__-type" name="questions[__INDEX__][type]" data-question-type><option value="multiple_choice">Multiple choice</option><option value="true_false">True or false</option></select></div>
                <div class="lms-field"><label for="question-__INDEX__-points">Points</label><input id="question-__INDEX__-points" type="number" name="questions[__INDEX__][points]" value="1" min="1" max="100" required></div>
                <div class="lms-field lms-field-wide"><label for="question-__INDEX__-prompt">Question</label><textarea id="question-__INDEX__-prompt" name="questions[__INDEX__][prompt]" rows="3" maxlength="1200" required></textarea></div>
            </div>
            <fieldset class="lms-option-fieldset">
                <legend>Answer options</legend>
                <div class="lms-option-list" data-quiz-option-list>
                    @foreach(range(0, 3) as $optionIndex)
                        <div class="lms-option-row" data-quiz-option><span class="lms-option-letter" aria-hidden="true">{{ chr(65 + $optionIndex) }}</span><label class="sr-only" for="question-__INDEX__-option-{{ $optionIndex }}">Option {{ $optionIndex + 1 }}</label><input id="question-__INDEX__-option-{{ $optionIndex }}" name="questions[__INDEX__][options][{{ $optionIndex }}]" required><button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option {{ $optionIndex + 1 }}">x</button></div>
                    @endforeach
                </div>
                <button type="button" class="lms-text-action" data-add-option>Add option</button>
            </fieldset>
            <div class="lms-field lms-correct-answer"><label for="question-__INDEX__-correct">Correct answer</label><select id="question-__INDEX__-correct" name="questions[__INDEX__][correct_option]" data-correct-option>@foreach(range(0, 3) as $optionIndex)<option value="{{ $optionIndex }}">Option {{ chr(65 + $optionIndex) }}</option>@endforeach</select></div>
        </article>
    </template>
</div>
