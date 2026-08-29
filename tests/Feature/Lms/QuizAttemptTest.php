<?php

namespace Tests\Feature\Lms;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainee_can_start_submit_and_view_a_server_graded_quiz(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'training_module_id' => $module->id,
            'attempt_limit' => 2,
        ]);
        $first = $this->question($quiz, 'Which action comes first?', [
            'Perform hand hygiene',
            'Touch the patient',
            'Remove the care plan',
        ], 0, 2);
        $second = $this->question($quiz, 'Gloves replace hand hygiene.', [
            'True',
            'False',
        ], 1, 1, 'true_false', 1);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('id="assessments"', false)
            ->assertSee($quiz->title);

        $detail = $this->actingAs($trainee)
            ->get(route('trainee.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('data-quiz-detail', false)
            ->assertDontSee('correct_option', false)
            ->assertDontSee('data-correct', false);

        $this->assertStringNotContainsString(
            '"correct_option"',
            $detail->getContent(),
            'The trainee response must not expose the server-side answer key.'
        );

        $start = $this->actingAs($trainee)
            ->post(route('trainee.quizzes.start', $quiz));

        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_application_id', $application->id)
            ->firstOrFail();

        $start->assertRedirect(route('trainee.quiz-attempts.show', $attempt));

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertOk()
            ->assertSee('data-quiz-attempt-form', false)
            ->assertSee('data-quiz-timer', false)
            ->assertSee('Perform hand hygiene')
            ->assertDontSee('correct_option', false);

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), [
                'answers' => [
                    $first->id => 0,
                    $second->id => 1,
                    999999 => 0,
                ],
                'earned_points' => 0,
                'score_percent' => 0,
                'passed' => false,
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);
        $this->assertEquals(3.0, (float) $attempt->earned_points);
        $this->assertEquals(3.0, (float) $attempt->total_points);
        $this->assertEquals(100.0, (float) $attempt->score_percent);
        $this->assertTrue($attempt->passed);
        $this->assertCount(2, $attempt->answers);
        $this->assertSame(0, $attempt->answers[$first->id]);
        $this->assertSame(1, $attempt->answers[$second->id]);
        $this->assertArrayNotHasKey(999999, $attempt->answers);

        // A retried browser submission must not overwrite a finalized score.
        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), [
                'answers' => [
                    $first->id => 2,
                    $second->id => 0,
                ],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $attempt));

        $this->assertEquals(100.0, (float) $attempt->fresh()->score_percent);

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.result', $attempt))
            ->assertOk()
            ->assertSee('data-quiz-result', false)
            ->assertSee('Quiz Result')
            ->assertSee('100');
    }

    public function test_wrong_batch_quiz_and_another_trainees_attempt_are_not_accessible(): void
    {
        $trainer = $this->lmsUser('trainer');
        $firstBatch = $this->lmsBatch();
        $secondBatch = $this->lmsBatch(['name' => 'Caregiving Batch B']);
        ['user' => $firstTrainee, 'application' => $firstApplication] = $this->lmsTrainee($firstBatch);
        ['user' => $secondTrainee] = $this->lmsTrainee($secondBatch);
        $quiz = $this->publishedQuiz($trainer, $firstBatch);
        $this->question($quiz, 'Batch-only question', ['Correct', 'Incorrect'], 0);
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $firstApplication->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'answers' => [],
            'started_at' => now(),
        ]);

        $this->actingAs($secondTrainee)
            ->get(route('trainee.quizzes.index'))
            ->assertRedirect(route('trainee.modules.index'));

        $this->actingAs($secondTrainee)
            ->get(route('trainee.quizzes.show', $quiz))
            ->assertNotFound();

        foreach ([
            route('trainee.quiz-attempts.show', $attempt),
            route('trainee.quiz-attempts.result', $attempt),
        ] as $url) {
            $this->actingAs($secondTrainee)->get($url)->assertForbidden();
        }

        $this->actingAs($secondTrainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), ['answers' => []])
            ->assertForbidden();

        $this->actingAs($firstTrainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertOk();
    }

    public function test_deadline_time_limit_and_attempt_limit_are_enforced_on_the_server(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);

        $closedQuiz = $this->publishedQuiz($trainer, $batch, [
            'title' => 'Closed quiz',
            'due_at' => now()->subMinute(),
        ]);
        $this->question($closedQuiz, 'Closed question', ['Yes', 'No'], 0);

        $this->actingAs($trainee)
            ->get(route('trainee.quizzes.index'))
            ->assertRedirect(route('trainee.modules.index'));

        $this->actingAs($trainee)
            ->post(route('trainee.quizzes.start', $closedQuiz))
            ->assertSessionHasErrors();

        $timedQuiz = $this->publishedQuiz($trainer, $batch, [
            'title' => 'Timed quiz',
            'time_limit_minutes' => 1,
        ]);
        $timedQuestion = $this->question($timedQuiz, 'Timed question', ['Yes', 'No'], 0);
        $expiredAttempt = QuizAttempt::create([
            'quiz_id' => $timedQuiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'answers' => [],
            'started_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $expiredAttempt), [
                'answers' => [$timedQuestion->id => 0],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $expiredAttempt))
            ->assertSessionHas('saved');

        $expiredAttempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_GRADED, $expiredAttempt->status);
        $this->assertEquals(0.0, (float) $expiredAttempt->score_percent);
        $this->assertNull($expiredAttempt->answers[$timedQuestion->id]);

        $limitedQuiz = $this->publishedQuiz($trainer, $batch, [
            'title' => 'Single-attempt quiz',
            'attempt_limit' => 1,
        ]);
        $this->question($limitedQuiz, 'Limited question', ['Yes', 'No'], 0);
        QuizAttempt::create([
            'quiz_id' => $limitedQuiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'answers' => [],
            'earned_points' => 0,
            'total_points' => 1,
            'score_percent' => 0,
            'passed' => false,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now()->subMinutes(4),
            'graded_at' => now()->subMinutes(4),
        ]);

        $this->actingAs($trainee)
            ->from(route('trainee.quizzes.show', $limitedQuiz))
            ->post(route('trainee.quizzes.start', $limitedQuiz))
            ->assertRedirect(route('trainee.quizzes.show', $limitedQuiz))
            ->assertSessionHasErrors();

        $this->assertSame(
            1,
            QuizAttempt::query()
                ->where('quiz_id', $limitedQuiz->id)
                ->where('enrollment_application_id', $application->id)
                ->count()
        );
    }

    public function test_quiz_without_a_timer_or_due_date_stays_unlimited_until_manual_submission(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'due_at' => null,
            'time_limit_minutes' => null,
        ]);
        $question = $this->question($quiz, 'Unlimited question', ['Yes', 'No'], 0);

        $this->actingAs($trainee)
            ->post(route('trainee.quizzes.start', $quiz))
            ->assertRedirect();

        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_application_id', $application->id)
            ->firstOrFail();

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertOk()
            ->assertSee('data-remaining-seconds="unlimited"', false);

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->fresh()->status);

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), [
                'answers' => [$question->id => 0],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $attempt));

        $this->assertEquals(100.0, (float) $attempt->fresh()->score_percent);
    }

    public function test_due_date_shortens_the_timer_and_the_expiration_grace_finalizes_received_answers(): void
    {
        $this->travelTo(now()->startOfSecond());

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'due_at' => now()->addMinutes(2),
            'time_limit_minutes' => 60,
        ]);
        $question = $this->question($quiz, 'Deadline question', ['Safe', 'Unsafe'], 0);
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertOk()
            ->assertSee('data-remaining-seconds="120"', false);

        $this->travel(121)->seconds();

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), [
                'answers' => [$question->id => 0],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $attempt))
            ->assertSessionHas('saved');

        $attempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_GRADED, $attempt->status);
        $this->assertEquals(100.0, (float) $attempt->score_percent);
    }

    public function test_reopening_an_expired_attempt_finalizes_it_instead_of_leaving_it_stuck(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'time_limit_minutes' => 1,
            'attempt_limit' => 2,
        ]);
        $question = $this->question($quiz, 'Expired question', ['Yes', 'No'], 0);
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertRedirect(route('trainee.quiz-attempts.result', $attempt))
            ->assertSessionHas('saved');

        $attempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_GRADED, $attempt->status);
        $this->assertEquals(0.0, (float) $attempt->score_percent);
        $this->assertNull($attempt->answers[$question->id]);
        $this->assertSame(1, $quiz->attemptsRemainingFor($application));
    }

    public function test_unpublished_quiz_blocks_an_active_attempt_but_preserves_the_record(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $quiz = $this->publishedQuiz($trainer, $batch);
        $question = $this->question($quiz, 'Closed access question', ['Yes', 'No'], 0);
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now(),
        ]);
        $quiz->update(['is_published' => false, 'published_at' => null]);

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertNotFound();

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), [
                'answers' => [$question->id => 0],
            ])
            ->assertNotFound();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->fresh()->status);
    }

    public function test_correct_answers_unlock_only_after_the_last_attempt(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $quiz = $this->publishedQuiz($trainer, $batch, ['attempt_limit' => 2]);
        $question = $this->question(
            $quiz,
            'Choose the safe action.',
            ['Perform hand hygiene', 'Skip preparation'],
            0,
        );
        $firstAttempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $firstAttempt), [
                'answers' => [$question->id => 1],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $firstAttempt));

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.result', $firstAttempt))
            ->assertOk()
            ->assertSee('Correctness and answer keys unlock')
            ->assertDontSee('Correct answer')
            ->assertDontSee('Perform hand hygiene');

        $secondAttempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 2,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now(),
        ]);

        // Starting the final attempt must not expose the key in another tab.
        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.result', $firstAttempt))
            ->assertOk()
            ->assertDontSee('Correct answer')
            ->assertDontSee('Perform hand hygiene');

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $secondAttempt), [
                'answers' => [$question->id => 1],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $secondAttempt));

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.result', $secondAttempt))
            ->assertOk()
            ->assertSee('Correct answer')
            ->assertSee('Perform hand hygiene');
    }

    public function test_trainee_can_submit_docx_activity_file_and_download_it(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = $this->publishedQuiz($trainer, $batch, [
            'training_module_id' => $module->id,
            'attempt_limit' => 1,
        ]);

        $fileQuestion = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'file_upload',
            'prompt' => 'Upload your completed Caregiving Activity Sheet (.docx).',
            'options' => [],
            'correct_option' => null,
            'points' => 10,
            'position' => 0,
        ]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.show', $attempt))
            ->assertOk()
            ->assertSee('lms-activity-upload', false)
            ->assertSee('lms-activity-file-input', false)
            ->assertSee('type="file"', false);

        $docxFile = \Illuminate\Http\UploadedFile::fake()->create('My_Activity.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($trainee)
            ->post(route('trainee.quiz-attempts.submit', $attempt), [
                'file_answers' => [
                    $fileQuestion->id => $docxFile,
                ],
            ])
            ->assertRedirect(route('trainee.quiz-attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);
        $this->assertEquals(10.0, (float) $attempt->earned_points);
        $this->assertEquals(100.0, (float) $attempt->score_percent);
        $this->assertTrue($attempt->passed);

        $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.result', $attempt))
            ->assertOk()
            ->assertSee('My_Activity.docx')
            ->assertSee(route('trainee.quiz-attempts.download', ['attempt' => $attempt, 'question' => $fileQuestion->id]));

        $download = $this->actingAs($trainee)
            ->get(route('trainee.quiz-attempts.download', ['attempt' => $attempt, 'question' => $fileQuestion->id]));

        $download->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=My_Activity.docx');

        // Trainer can also download the student's submission from quiz results
        $trainerDownload = $this->actingAs($trainer)
            ->get(route('trainer.quizzes.attempts.download', ['quiz' => $quiz, 'attempt' => $attempt, 'question' => $fileQuestion->id]));

        $trainerDownload->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=My_Activity.docx');
    }

    private function publishedQuiz(
        User $trainer,
        TrainingBatch $batch,
        array $overrides = [],
    ): Quiz {
        return Quiz::create(array_merge([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Caregiving safety quiz',
            'instructions' => 'Complete the quiz before its deadline.',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'available_at' => now()->subHour(),
            'due_at' => now()->addWeek(),
            'time_limit_minutes' => 20,
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ], $overrides));
    }

    private function question(
        Quiz $quiz,
        string $prompt,
        array $options,
        int $correctOption,
        float $points = 1,
        string $type = 'multiple_choice',
        int $position = 0,
    ): QuizQuestion {
        return QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => $type,
            'prompt' => $prompt,
            'options' => $options,
            'correct_option' => $correctOption,
            'points' => $points,
            'position' => $position,
        ]);
    }
}
