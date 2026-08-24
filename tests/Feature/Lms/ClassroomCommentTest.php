<?php

namespace Tests\Feature\Lms;

use App\Models\ClassroomComment;
use App\Models\Quiz;
use App\Notifications\ClassroomCommentPosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class ClassroomCommentTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_class_comment_is_visible_to_the_class_and_notifies_each_trainee_once(): void
    {
        Notification::fake();
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $traineeOne] = $this->lmsTrainee($batch);
        ['user' => $traineeTwo] = $this->lmsTrainee($batch, ['email' => 'second@example.test']);
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainer)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $module->id,
                'visibility' => 'class',
                'body' => 'Please bring your skills checklist tomorrow.',
            ])
            ->assertRedirect(route('trainer.modules.show', $module).'#classroom-comments');

        $comment = ClassroomComment::firstOrFail();
        $this->assertSame(ClassroomComment::VISIBILITY_CLASS, $comment->visibility);
        $this->assertSame($trainer->id, $comment->author_id);

        Notification::assertSentToTimes($traineeOne, ClassroomCommentPosted::class, 1);
        Notification::assertSentToTimes($traineeTwo, ClassroomCommentPosted::class, 1);

        $this->actingAs($traineeOne)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('Please bring your skills checklist tomorrow.');
    }

    public function test_trainee_private_comment_is_only_visible_to_participants_and_authorized_staff(): void
    {
        Notification::fake();
        $trainer = $this->lmsUser('trainer');
        $admin = $this->lmsUser('admin');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $traineeOne] = $this->lmsTrainee($batch);
        ['user' => $traineeTwo] = $this->lmsTrainee($batch, ['email' => 'peer@example.test']);
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($traineeOne)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $module->id,
                'visibility' => 'private',
                // A trainee cannot redirect a private message to a classmate.
                'recipient_user_id' => $traineeTwo->id,
                'body' => 'I need help with the transfer procedure.',
            ])
            ->assertRedirect(route('trainee.modules.show', $module).'#classroom-comments');

        $comment = ClassroomComment::firstOrFail();
        $this->assertSame($trainer->id, $comment->recipient_user_id);
        Notification::assertSentToTimes($trainer, ClassroomCommentPosted::class, 1);
        Notification::assertNotSentTo($traineeTwo, ClassroomCommentPosted::class);

        $this->actingAs($traineeTwo)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertDontSee('I need help with the transfer procedure.');

        $this->actingAs($trainer)
            ->get(route('trainer.modules.show', $module))
            ->assertOk()
            ->assertSee('I need help with the transfer procedure.');

        $this->actingAs($admin)
            ->get(route('classroom-comments.index', ['type' => 'module', 'id' => $module->id]))
            ->assertOk()
            ->assertSee('I need help with the transfer procedure.');
    }

    public function test_trainer_can_send_private_feedback_to_one_trainee_without_exposing_it_to_peers(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $traineeOne] = $this->lmsTrainee($batch);
        ['user' => $traineeTwo] = $this->lmsTrainee($batch, ['email' => 'other@example.test']);
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainer)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $module->id,
                'visibility' => 'private',
                'recipient_user_id' => $traineeOne->id,
                'body' => 'Your practical sequence needs one correction.',
            ])
            ->assertRedirect();

        $this->actingAs($traineeOne)
            ->get(route('trainee.modules.show', $module))
            ->assertSee('Your practical sequence needs one correction.')
            ->assertSee('Private with '.$trainer->name)
            ->assertDontSee('Private with '.$traineeOne->name);

        $this->actingAs($traineeTwo)
            ->get(route('trainee.modules.show', $module))
            ->assertDontSee('Your practical sequence needs one correction.');
    }

    public function test_admin_can_join_the_class_conversation_and_send_scoped_private_feedback(): void
    {
        Notification::fake();
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $traineeOne] = $this->lmsTrainee($batch);
        ['user' => $traineeTwo] = $this->lmsTrainee($batch, ['email' => 'admin-comment-peer@example.test']);
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($admin)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $module->id,
                'visibility' => 'private',
                'recipient_user_id' => $traineeOne->id,
                'body' => 'Please review this privately with your trainer.',
            ])
            ->assertRedirect(route('classroom-comments.index', [
                'type' => 'module',
                'id' => $module->id,
            ]).'#classroom-comments');

        Notification::assertSentToTimes($traineeOne, ClassroomCommentPosted::class, 1);
        Notification::assertNotSentTo($traineeTwo, ClassroomCommentPosted::class);

        $this->actingAs($traineeOne)
            ->get(route('trainee.modules.show', $module))
            ->assertSee('Please review this privately with your trainer.');

        $this->actingAs($traineeTwo)
            ->get(route('trainee.modules.show', $module))
            ->assertDontSee('Please review this privately with your trainer.');
    }

    public function test_unassigned_trainer_cannot_read_or_post_comments_to_another_trainers_module(): void
    {
        $owner = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $owner->id]);
        $module = $this->lmsModule($owner, $batch);

        $this->actingAs($otherTrainer)
            ->get(route('classroom-comments.index', ['type' => 'module', 'id' => $module->id]))
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $module->id,
                'visibility' => 'class',
                'body' => 'Unauthorized comment.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('classroom_comments', 0);
    }

    public function test_comment_body_is_plain_text_and_only_the_author_can_edit_it(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainee)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $module->id,
                'visibility' => 'class',
                'body' => '<script>alert(1)</script>',
            ])
            ->assertSessionHasErrors('body');

        $comment = $module->comments()->create([
            'author_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'visibility' => 'class',
            'body' => 'Original question',
        ]);

        $this->actingAs($trainer)
            ->patch(route('classroom-comments.update', $comment), ['body' => 'Trainer overwrite'])
            ->assertForbidden();

        $this->actingAs($trainee)
            ->patch(route('classroom-comments.update', $comment), ['body' => 'Clarified question'])
            ->assertRedirect();

        $this->assertDatabaseHas('classroom_comments', [
            'id' => $comment->id,
            'body' => 'Clarified question',
        ]);
    }

    public function test_quiz_comments_are_available_to_the_assigned_trainee_and_trainer(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Skills Check',
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'quiz',
                'commentable_id' => $quiz->id,
                'visibility' => 'class',
                'body' => 'Can I review the lesson before starting?',
            ])
            ->assertRedirect(route('trainee.quizzes.show', $quiz).'#classroom-comments');

        $this->actingAs($trainer)
            ->get(route('trainer.quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('Can I review the lesson before starting?');

        $this->actingAs($trainee)
            ->get(route('trainee.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Can I review the lesson before starting?');
    }
}
