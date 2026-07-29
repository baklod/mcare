<?php

namespace Tests\Feature\Lms;

use App\Models\TrainerAnnouncement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class AnnouncementStreamTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_can_create_update_and_delete_a_scheduled_batch_announcement(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $postedAt = now()->addDay()->seconds(0);
        $expiresAt = now()->addWeek()->seconds(0);

        $this->actingAs($trainer)
            ->post(route('trainer.announcements.store'), [
                'training_batch_id' => $batch->id,
                'kind' => 'reminder',
                'audience' => '',
                'title' => 'Skills demonstration',
                'message' => 'Bring the required PPE for the laboratory demonstration.',
                'posted_at' => $postedAt->toDateTimeString(),
                'expires_at' => $expiresAt->toDateTimeString(),
                'is_pinned' => '1',
                'is_published' => '1',
            ])
            ->assertRedirect(route('trainer.stream'))
            ->assertSessionHas('saved');

        $announcement = TrainerAnnouncement::query()
            ->where('title', 'Skills demonstration')
            ->firstOrFail();

        $this->assertSame($trainer->id, $announcement->trainer_id);
        $this->assertSame($batch->id, $announcement->training_batch_id);
        $this->assertTrue($announcement->is_published);
        $this->assertTrue($announcement->is_pinned);
        $this->assertSame('reminder', $announcement->kind);
        $this->assertSame('trainees', $announcement->audience);

        $this->actingAs($trainer)
            ->patch(route('trainer.announcements.update', $announcement), [
                'training_batch_id' => $batch->id,
                'kind' => 'news',
                'title' => 'Updated skills demonstration',
                'message' => 'The laboratory demonstration has moved to Skills Lab B.',
                'posted_at' => now()->toDateTimeString(),
                'expires_at' => $expiresAt->toDateTimeString(),
                'is_published' => '0',
            ])
            ->assertRedirect(route('trainer.stream'));

        $this->assertDatabaseHas('trainer_announcements', [
            'id' => $announcement->id,
            'title' => 'Updated skills demonstration',
            'kind' => 'news',
            'is_published' => false,
        ]);

        $this->actingAs($trainer)
            ->delete(route('trainer.announcements.destroy', $announcement))
            ->assertRedirect(route('trainer.stream'));

        $this->assertDatabaseMissing('trainer_announcements', ['id' => $announcement->id]);
    }

    public function test_trainee_stream_only_shows_current_published_posts_for_its_batch(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $otherBatch = $this->lmsBatch(['name' => 'Caregiving Batch B']);
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $this->announcement($trainer->id, $batch->id, 'Visible class news');
        $this->announcement($trainer->id, null, 'Visible center announcement');
        $this->announcement($trainer->id, $otherBatch->id, 'Other batch only');
        $this->announcement($trainer->id, $batch->id, 'Draft post', [
            'is_published' => false,
        ]);
        $this->announcement($trainer->id, $batch->id, 'Scheduled post', [
            'posted_at' => now()->addHour(),
        ]);
        $this->announcement($trainer->id, $batch->id, 'Expired post', [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.stream'))
            ->assertOk()
            ->assertSee('Visible class news')
            ->assertSee('Visible center announcement')
            ->assertDontSee('Other batch only')
            ->assertDontSee('Draft post')
            ->assertDontSee('Scheduled post')
            ->assertDontSee('Expired post');
    }

    public function test_trainer_cannot_mutate_another_trainers_announcement(): void
    {
        $owner = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $announcement = $this->announcement($owner->id, $batch->id, 'Owner post');

        $payload = [
            'training_batch_id' => $batch->id,
            'kind' => 'announcement',
            'title' => 'Unauthorized edit',
            'message' => 'This must not be persisted.',
            'is_published' => '1',
        ];

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.announcements.update', $announcement), $payload)
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->delete(route('trainer.announcements.destroy', $announcement))
            ->assertForbidden();

        $this->assertDatabaseHas('trainer_announcements', [
            'id' => $announcement->id,
            'title' => 'Owner post',
        ]);
    }

    public function test_announcement_expiration_must_follow_its_scheduled_post_time(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();

        $this->actingAs($trainer)
            ->from(route('trainer.stream'))
            ->post(route('trainer.announcements.store'), [
                'training_batch_id' => $batch->id,
                'kind' => 'announcement',
                'title' => 'Invalid schedule',
                'message' => 'This announcement must not be stored.',
                'posted_at' => now()->addWeek()->toDateTimeString(),
                'expires_at' => now()->addDay()->toDateTimeString(),
                'is_published' => '1',
            ])
            ->assertRedirect(route('trainer.stream'))
            ->assertSessionHasErrors('expires_at');

        $this->assertDatabaseMissing('trainer_announcements', [
            'title' => 'Invalid schedule',
        ]);
    }

    private function announcement(
        int $trainerId,
        ?int $batchId,
        string $title,
        array $overrides = [],
    ): TrainerAnnouncement {
        return TrainerAnnouncement::create(array_merge([
            'trainer_id' => $trainerId,
            'training_batch_id' => $batchId,
            'kind' => 'announcement',
            'title' => $title,
            'message' => "{$title} details.",
            'audience' => 'trainees',
            'is_pinned' => false,
            'is_published' => true,
            'posted_at' => now()->subMinute(),
            'expires_at' => now()->addWeek(),
        ], $overrides));
    }
}
