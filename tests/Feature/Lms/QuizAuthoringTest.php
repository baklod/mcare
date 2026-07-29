<?php

namespace Tests\Feature\Lms;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class QuizAuthoringTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_can_author_edit_publish_review_and_delete_a_quiz(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.create'))
            ->assertOk()
            ->assertSee('data-quiz-builder', false)
            ->assertSee('data-quiz-question-list', false);

        $this->actingAs($trainer)
            ->post(route('trainer.quizzes.store'), $this->quizPayload($batch->id))
            ->assertRedirect(route('trainer.assessments'))
            ->assertSessionHas('saved');

        $quiz = Quiz::query()->where('title', 'Infection control check')->firstOrFail();
        $this->assertSame($trainer->id, $quiz->trainer_id);
        $this->assertFalse($quiz->is_published);
        $this->assertCount(2, $quiz->questions);

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('data-quiz-builder', false)
            ->assertSee('data-quiz-question', false);

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.update', $quiz), $this->quizPayload($batch->id, [
                'title' => 'Updated infection control check',
                'passing_score_percent' => 80,
            ]))
            ->assertRedirect(route('trainer.assessments'));

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertRedirect(route('trainer.assessments'));

        $quiz->refresh();
        $this->assertTrue($quiz->is_published);
        $this->assertNotNull($quiz->published_at);
        $this->assertSame('Updated infection control check', $quiz->title);

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.results', $quiz))
            ->assertOk()
            ->assertSee('data-quiz-results', false);

        $this->actingAs($trainer)
            ->delete(route('trainer.quizzes.destroy', $quiz))
            ->assertRedirect(route('trainer.assessments'));

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_questions', ['quiz_id' => $quiz->id]);
    }

    public function test_quiz_publication_requires_a_valid_question_set(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $quiz = $this->quiz($trainer->id, $batch->id);

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.edit', $quiz))
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertRedirect(route('trainer.quizzes.edit', $quiz))
            ->assertSessionHasErrors();

        $this->assertFalse($quiz->fresh()->is_published);
    }

    public function test_quiz_rejects_an_answer_key_outside_the_available_options(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $payload = $this->quizPayload($batch->id);
        $payload['questions'][0]['correct_option'] = 99;

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.create'))
            ->post(route('trainer.quizzes.store'), $payload)
            ->assertRedirect(route('trainer.quizzes.create'))
            ->assertSessionHasErrors('questions.0.correct_option');

        $this->assertDatabaseMissing('quizzes', [
            'title' => 'Infection control check',
        ]);
    }

    public function test_trainer_cannot_manage_another_trainers_quiz(): void
    {
        $owner = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $quiz = $this->quiz($owner->id, $batch->id);
        $this->question($quiz);

        $this->actingAs($otherTrainer)
            ->get(route('trainer.quizzes.edit', $quiz))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.quizzes.update', $quiz), $this->quizPayload($batch->id))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.quizzes.publication', $quiz), ['is_published' => '1'])
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->get(route('trainer.quizzes.results', $quiz))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->delete(route('trainer.quizzes.destroy', $quiz))
            ->assertForbidden();
    }

    public function test_first_attempt_locks_grading_metadata_and_prevents_quiz_deletion(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        $quiz = $this->quiz($trainer->id, $batch->id, ['is_published' => true]);
        $question = $this->question($quiz);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'answers' => [],
            'started_at' => now(),
        ]);
        $payload = [
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'title' => 'Safe title update',
            'instructions' => 'Updated explanation only.',
            'available_at' => $quiz->available_at?->toDateTimeString(),
            'due_at' => $quiz->due_at?->toDateTimeString(),
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'attempt_limit' => $quiz->attempt_limit,
            'passing_score_percent' => 90,
            'is_published' => '0',
            'questions' => [[
                'type' => $question->type,
                'prompt' => $question->prompt,
                'options' => $question->options,
                'correct_option' => $question->correct_option,
                'points' => $question->points,
                'position' => $question->position,
            ]],
        ];

        $this->actingAs($trainer)
            ->from(route('trainer.quizzes.edit', $quiz))
            ->patch(route('trainer.quizzes.update', $quiz), $payload)
            ->assertRedirect(route('trainer.quizzes.edit', $quiz))
            ->assertSessionHasErrors('passing_score_percent');

        $quiz->refresh();
        $this->assertSame('Draft knowledge check', $quiz->title);
        $this->assertEquals(75.0, (float) $quiz->passing_score_percent);

        $payload['passing_score_percent'] = 75;

        $this->actingAs($trainer)
            ->patch(route('trainer.quizzes.update', $quiz), $payload)
            ->assertRedirect(route('trainer.assessments'))
            ->assertSessionHas('saved');

        $quiz->refresh();
        $this->assertSame('Safe title update', $quiz->title);
        $this->assertFalse($quiz->is_published);
        $this->assertEquals(75.0, (float) $quiz->passing_score_percent);

        $this->actingAs($trainer)
            ->from(route('trainer.assessments'))
            ->delete(route('trainer.quizzes.destroy', $quiz))
            ->assertRedirect(route('trainer.assessments'))
            ->assertSessionHasErrors('quiz');

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id]);
    }

    private function quizPayload(int $batchId, array $overrides = []): array
    {
        return array_merge([
            'training_batch_id' => $batchId,
            'title' => 'Infection control check',
            'instructions' => 'Choose the safest answer for each item.',
            'available_at' => now()->subHour()->toDateTimeString(),
            'due_at' => now()->addWeek()->toDateTimeString(),
            'time_limit_minutes' => 20,
            'attempt_limit' => 2,
            'passing_score_percent' => 75,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'What should happen before patient contact?',
                    'options' => ['Perform hand hygiene', 'Remove all PPE', 'Skip preparation'],
                    'correct_option' => 0,
                    'points' => 2,
                    'position' => 0,
                ],
                [
                    'type' => 'true_false',
                    'prompt' => 'Gloves replace hand hygiene.',
                    'options' => ['True', 'False'],
                    'correct_option' => 1,
                    'points' => 1,
                    'position' => 1,
                ],
            ],
        ], $overrides);
    }

    private function quiz(int $trainerId, int $batchId, array $overrides = []): Quiz
    {
        return Quiz::create(array_merge([
            'trainer_id' => $trainerId,
            'training_batch_id' => $batchId,
            'title' => 'Draft knowledge check',
            'instructions' => 'Answer every question.',
            'is_published' => false,
            'available_at' => now()->subHour(),
            'due_at' => now()->addWeek(),
            'time_limit_minutes' => 20,
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ], $overrides));
    }

    private function question(Quiz $quiz): QuizQuestion
    {
        return QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'multiple_choice',
            'prompt' => 'Choose the correct procedure.',
            'options' => ['Safe procedure', 'Unsafe procedure'],
            'correct_option' => 0,
            'points' => 1,
            'position' => 0,
        ]);
    }
}
