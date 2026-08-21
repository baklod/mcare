<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingBatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $trainer = $request->user();
        $quizzes = Quiz::query()
            ->with(['batch', 'targetTrainee'])
            ->withCount(['questions', 'attempts'])
            ->withAvg([
                'attempts as average_score' => fn ($query) => $query->where('status', QuizAttempt::STATUS_GRADED),
            ], 'score_percent')
            ->where('trainer_id', $trainer->id)
            ->latest('updated_at')
            ->paginate(12);

        return view('trainer.assessments', [
            'quizzes' => $quizzes,
            'quizStats' => [
                'total' => Quiz::query()->where('trainer_id', $trainer->id)->count(),
                'published' => Quiz::query()
                    ->where('trainer_id', $trainer->id)
                    ->where('is_published', true)
                    ->count(),
                'submissions' => $trainer->quizzes()
                    ->withCount(['attempts' => fn ($query) => $query->where('status', QuizAttempt::STATUS_GRADED)])
                    ->get()
                    ->sum('attempts_count'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('trainer.quizzes.create', [
            'quiz' => new Quiz([
                'attempt_limit' => 1,
                'passing_score_percent' => 75,
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $questions = $this->normalizedQuestions($validated['questions']);
        $published = $request->boolean('is_published');

        $quiz = DB::transaction(function () use (
            $request,
            $validated,
            $batchId,
            $targetTrainee,
            $questions,
            $published,
        ): Quiz {
            $quiz = Quiz::create([
                'trainer_id' => $request->user()->id,
                'training_batch_id' => $batchId,
                'target_enrollment_application_id' => $targetTrainee?->id,
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'available_at' => $validated['available_at'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'attempt_limit' => $validated['attempt_limit'],
                'passing_score_percent' => $validated['passing_score_percent'],
                'is_published' => $published,
                'published_at' => $published ? now() : null,
            ]);

            $this->replaceQuestions($quiz, $questions);

            return $quiz;
        });

        AdminActivityLog::record($request->user(), 'trainer.quiz.created', $quiz, [
            'title' => $quiz->title,
            'batch_id' => $quiz->training_batch_id,
            'question_count' => count($questions),
            'published' => $quiz->is_published,
        ]);

        return redirect()
            ->route('trainer.assessments')
            ->with('saved', $quiz->is_published ? 'Quiz published.' : 'Quiz saved as a draft.');
    }

    public function edit(Request $request, Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        return view('trainer.quizzes.edit', [
            'quiz' => $quiz->load(['questions', 'batch', 'targetTrainee']),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $validated = $this->validatedPayload($request);
        [$batchId, $targetTrainee] = $this->resolveAudience($validated);
        $this->assertTrainerBatch($request, $batchId, $targetTrainee);
        $questions = $this->normalizedQuestions($validated['questions']);

        DB::transaction(function () use (
            $request,
            $quiz,
            $validated,
            $batchId,
            $targetTrainee,
            $questions,
        ): void {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $hasAttempts = $lockedQuiz->attempts()->exists();

            if ($hasAttempts) {
                $this->assertGradingMetadataUnchanged(
                    $lockedQuiz,
                    $validated,
                    $batchId,
                    $targetTrainee,
                    $questions,
                );
            }

            $published = $request->has('is_published')
                ? $request->boolean('is_published')
                : $lockedQuiz->is_published;
            $attributes = [
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'is_published' => $published,
                'published_at' => $published ? ($lockedQuiz->published_at ?? now()) : null,
            ];

            if (! $hasAttempts) {
                $attributes = [
                    ...$attributes,
                    'training_batch_id' => $batchId,
                    'target_enrollment_application_id' => $targetTrainee?->id,
                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                    'attempt_limit' => $validated['attempt_limit'],
                    'passing_score_percent' => $validated['passing_score_percent'],
                ];
            }

            $lockedQuiz->update($attributes);

            if (! $hasAttempts) {
                $this->replaceQuestions($lockedQuiz, $questions);
            }
        });
        $quiz->refresh();

        AdminActivityLog::record($request->user(), 'trainer.quiz.updated', $quiz, [
            'title' => $quiz->title,
            'batch_id' => $quiz->training_batch_id,
            'published' => $quiz->is_published,
        ]);

        return redirect()
            ->route('trainer.assessments')
            ->with('saved', 'Quiz updated.');
    }

    public function publication(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $validated = $request->validate(['is_published' => ['required', 'boolean']]);
        $published = (bool) $validated['is_published'];

        if ($published && ! $quiz->questions()->exists()) {
            throw ValidationException::withMessages([
                'quiz' => 'Add at least one valid question before publishing.',
            ]);
        }

        $quiz->update([
            'is_published' => $published,
            'published_at' => $published ? ($quiz->published_at ?? now()) : null,
        ]);

        AdminActivityLog::record($request->user(), 'trainer.quiz.publication.updated', $quiz, [
            'title' => $quiz->title,
            'published' => $published,
        ]);

        return redirect()
            ->route('trainer.assessments')
            ->with('saved', $published ? 'Quiz published.' : 'Quiz returned to draft.');
    }

    public function results(Request $request, Quiz $quiz): View
    {
        $this->authorize('viewResults', $quiz);

        return view('trainer.quizzes.results', [
            'quiz' => $quiz->load(['batch', 'targetTrainee', 'questions']),
            'attempts' => $quiz->attempts()
                ->with(['application.user'])
                ->where('status', QuizAttempt::STATUS_GRADED)
                ->latest('submitted_at')
                ->paginate(20),
        ]);
    }

    public function destroy(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $title = DB::transaction(function () use ($request, $quiz): string {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);

            if ($lockedQuiz->attempts()->exists()) {
                throw ValidationException::withMessages([
                    'quiz' => 'This quiz already has learner attempts and cannot be deleted. Return it to draft to close access while preserving grades.',
                ]);
            }

            $title = $lockedQuiz->title;
            AdminActivityLog::record($request->user(), 'trainer.quiz.deleted', $lockedQuiz, [
                'title' => $title,
                'batch_id' => $lockedQuiz->training_batch_id,
            ]);
            $lockedQuiz->delete();

            return $title;
        });

        return redirect()
            ->route('trainer.assessments')
            ->with('saved', "Quiz {$title} was removed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $request->merge([
            'audience_type' => $request->input('audience_type', 'batch'),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'audience_type' => ['required', Rule::in(['batch', 'trainee'])],
            'training_batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'target_enrollment_application_id' => [
                'nullable',
                'integer',
                Rule::exists('enrollment_applications', 'id')->where(
                    fn ($query) => $query->where('status', EnrollmentApplication::STATUS_APPROVED)
                ),
            ],
            'available_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'attempt_limit' => ['required', 'integer', 'min:1', 'max:5'],
            'passing_score_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'is_published' => ['nullable', 'boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.type' => ['required', Rule::in(['multiple_choice', 'true_false'])],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:5'],
            'questions.*.points' => ['required', 'numeric', 'min:0.25', 'max:100'],
            'questions.*.position' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        if (
            filled($validated['available_at'] ?? null)
            && filled($validated['due_at'] ?? null)
            && Carbon::parse($validated['due_at'])->lte(Carbon::parse($validated['available_at']))
        ) {
            throw ValidationException::withMessages([
                'due_at' => 'The quiz due date must be after its availability date.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function normalizedQuestions(array $questions): array
    {
        $normalized = [];

        foreach (array_values($questions) as $index => $question) {
            $type = $question['type'];
            $options = $type === 'true_false'
                ? ['True', 'False']
                : array_map(static fn ($option) => trim((string) $option), array_values($question['options']));
            $correctOption = (int) $question['correct_option'];

            if (count(array_unique(array_map('mb_strtolower', $options))) !== count($options)) {
                throw ValidationException::withMessages([
                    "questions.{$index}.options" => 'Answer options must be unique.',
                ]);
            }

            if (! array_key_exists($correctOption, $options)) {
                throw ValidationException::withMessages([
                    "questions.{$index}.correct_option" => 'Choose a valid correct answer.',
                ]);
            }

            $normalized[] = [
                'type' => $type,
                'prompt' => trim((string) $question['prompt']),
                'options' => $options,
                'correct_option' => $correctOption,
                'points' => (float) $question['points'],
                'position' => isset($question['position']) ? (int) $question['position'] : $index,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function replaceQuestions(Quiz $quiz, array $questions): void
    {
        $quiz->questions()->delete();
        $quiz->questions()->createMany($questions);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: int, 1: EnrollmentApplication|null}
     */
    private function resolveAudience(array $validated): array
    {
        $targetTrainee = $validated['audience_type'] === 'trainee'
            ? EnrollmentApplication::query()->find($validated['target_enrollment_application_id'] ?? null)
            : null;
        $batchId = $targetTrainee?->training_batch_id ?? ($validated['training_batch_id'] ?? null);

        if (! $batchId || ($validated['audience_type'] === 'trainee' && ! $targetTrainee)) {
            throw ValidationException::withMessages([
                'audience_type' => 'Choose a class or an approved trainee before saving.',
            ]);
        }

        return [(int) $batchId, $targetTrainee];
    }

    /**
     * @return array{batches: Collection, trainees: Collection}
     */
    private function formOptions(): array
    {
        $assignedBatch = TrainingBatch::assignedTo(request()->user());

        return [
            'batches' => $assignedBatch ? collect([$assignedBatch]) : collect(),
            'trainees' => EnrollmentApplication::query()
                ->with('batch')
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->when($assignedBatch, fn ($query) => $query->where('training_batch_id', $assignedBatch->id))
                ->when(! $assignedBatch, fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ];
    }

    private function assertTrainerBatch(
        Request $request,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
    ): void {
        $assignedBatch = TrainingBatch::assignedTo($request->user());

        if (! $assignedBatch || $batchId !== (int) $assignedBatch->id) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Quizzes can only be assigned to the trainer\'s current batch.',
            ]);
        }

        if ($targetTrainee && (int) $targetTrainee->training_batch_id !== $batchId) {
            throw ValidationException::withMessages([
                'target_enrollment_application_id' => 'The selected trainee is outside the trainer\'s assigned batch.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function questionFingerprint(array $questions): string
    {
        return json_encode($questions, JSON_THROW_ON_ERROR);
    }

    private function storedQuestionFingerprint(Quiz $quiz): string
    {
        $questions = $quiz->questions()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(fn ($question, $index) => [
                'type' => $question->type,
                'prompt' => $question->prompt,
                'options' => array_values($question->options),
                'correct_option' => (int) $question->correct_option,
                'points' => (float) $question->points,
                'position' => (int) ($question->position ?? $index),
            ])
            ->all();

        return $this->questionFingerprint($questions);
    }

    /**
     * Once an attempt exists, keep every value that affects access or grading
     * immutable. Trainers may still edit explanatory text or close/reopen access.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function assertGradingMetadataUnchanged(
        Quiz $quiz,
        array $validated,
        int $batchId,
        ?EnrollmentApplication $targetTrainee,
        array $questions,
    ): void {
        $message = 'This setting is locked because a trainee has already started the quiz.';
        $errors = [];

        if ((int) $quiz->training_batch_id !== $batchId) {
            $errors['training_batch_id'] = $message;
        }

        if (
            $this->nullableInt($quiz->target_enrollment_application_id)
            !== $this->nullableInt($targetTrainee?->id)
        ) {
            $errors['target_enrollment_application_id'] = $message;
        }

        if (! $this->sameDateTime($quiz->available_at, $validated['available_at'] ?? null)) {
            $errors['available_at'] = $message;
        }

        if (! $this->sameDateTime($quiz->due_at, $validated['due_at'] ?? null)) {
            $errors['due_at'] = $message;
        }

        if (
            $this->nullableInt($quiz->time_limit_minutes)
            !== $this->nullableInt($validated['time_limit_minutes'] ?? null)
        ) {
            $errors['time_limit_minutes'] = $message;
        }

        if ((int) $quiz->attempt_limit !== (int) $validated['attempt_limit']) {
            $errors['attempt_limit'] = $message;
        }

        if (
            abs(
                (float) $quiz->passing_score_percent
                - (float) $validated['passing_score_percent']
            ) > 0.001
        ) {
            $errors['passing_score_percent'] = $message;
        }

        if (
            $this->questionFingerprint($questions)
            !== $this->storedQuestionFingerprint($quiz)
        ) {
            $errors['questions'] = $message;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function sameDateTime(mixed $current, mixed $incoming): bool
    {
        if (blank($incoming)) {
            return $current === null;
        }

        return $current !== null && $current->equalTo(Carbon::parse($incoming));
    }

    private function nullableInt(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }
}
