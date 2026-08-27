<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\AdminAnnouncement;
use App\Models\TrainerAnnouncement;
use App\Notifications\AdminAnnouncementNotification;
use App\Notifications\LmsAnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class GoogleLoginAnnouncementCatchUpTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_google_login_is_audited_and_catches_up_visible_announcements_exactly_once(): void
    {
        Notification::fake();

        $trainer = $this->lmsUser('trainer');
        $admin = $this->lmsUser('admin');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        $otherBatch = $this->lmsBatch([
            'name' => 'Caregiving Batch B',
            'trainer_id' => null,
        ]);
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $activeTrainerAnnouncement = TrainerAnnouncement::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Bring the caregiving workbook',
            'message' => 'Please bring your workbook to the next laboratory session.',
            'audience' => 'trainees',
            'kind' => TrainerAnnouncement::KIND_ANNOUNCEMENT,
            'is_published' => true,
            'posted_at' => now()->subDay(),
            'expires_at' => null,
        ]);

        $activeAdminAnnouncement = AdminAnnouncement::create([
            'author_id' => $admin->id,
            'target_type' => AdminAnnouncement::TARGET_BATCH,
            'training_batch_id' => $batch->id,
            'title' => 'Office schedule reminder',
            'message' => 'The registrar is open until 5:00 PM.',
            'kind' => AdminAnnouncement::KIND_ANNOUNCEMENT,
            'send_email' => true,
            'is_published' => true,
            'posted_at' => now()->subHour(),
            'expires_at' => null,
        ]);

        $expiredAnnouncement = TrainerAnnouncement::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Expired reminder',
            'message' => 'This reminder has already expired.',
            'audience' => 'trainees',
            'kind' => TrainerAnnouncement::KIND_REMINDER,
            'is_published' => true,
            'posted_at' => now()->subDays(2),
            'expires_at' => now()->subMinute(),
        ]);

        $futureAnnouncement = TrainerAnnouncement::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Future reminder',
            'message' => 'This reminder is not visible yet.',
            'audience' => 'trainees',
            'kind' => TrainerAnnouncement::KIND_REMINDER,
            'is_published' => true,
            'posted_at' => now()->addHour(),
            'expires_at' => null,
        ]);

        $otherBatchAnnouncement = TrainerAnnouncement::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $otherBatch->id,
            'title' => 'Other batch reminder',
            'message' => 'This reminder belongs to another batch.',
            'audience' => 'trainees',
            'kind' => TrainerAnnouncement::KIND_REMINDER,
            'is_published' => true,
            'posted_at' => now()->subHour(),
            'expires_at' => null,
        ]);

        $googleUser = SocialiteUser::fake([
            'id' => 'google-trainee-123',
            'name' => $trainee->name,
            'email' => $trainee->email,
            'avatar' => 'https://example.test/trainee.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->twice()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->twice()->with('google')->andReturn($provider);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('User-Agent', 'MCARE Test Browser')
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('trainee.dashboard'));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('trainee.dashboard'));

        $this->assertCount(1, Notification::sent($trainee, LmsAnnouncementPublished::class));
        $this->assertCount(1, Notification::sent($trainee, AdminAnnouncementNotification::class));

        Notification::assertSentTo(
            $trainee,
            LmsAnnouncementPublished::class,
            fn (LmsAnnouncementPublished $notification) => $notification->announcement->is($activeTrainerAnnouncement),
        );
        Notification::assertNotSentTo(
            $trainee,
            LmsAnnouncementPublished::class,
            fn (LmsAnnouncementPublished $notification) => in_array(
                $notification->announcement->id,
                [$expiredAnnouncement->id, $futureAnnouncement->id, $otherBatchAnnouncement->id],
                true,
            ),
        );
        Notification::assertSentTo(
            $trainee,
            AdminAnnouncementNotification::class,
            fn (AdminAnnouncementNotification $notification) => $notification->announcement->is($activeAdminAnnouncement),
        );

        $this->assertDatabaseCount('announcement_deliveries', 2);
        $this->assertDatabaseHas('announcement_deliveries', [
            'user_id' => $trainee->id,
            'announcement_type' => 'trainer',
            'announcement_id' => $activeTrainerAnnouncement->id,
            'delivery_reason' => 'login_catch_up',
        ]);
        $this->assertDatabaseHas('announcement_deliveries', [
            'user_id' => $trainee->id,
            'announcement_type' => 'admin',
            'announcement_id' => $activeAdminAnnouncement->id,
            'delivery_reason' => 'login_catch_up',
        ]);

        $loginLogs = AdminActivityLog::query()
            ->where('user_id', $trainee->id)
            ->where('action', 'account.login.google.success')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $loginLogs);
        $this->assertSame(2, $loginLogs[0]->meta['announcement_catch_up_count']);
        $this->assertSame(0, $loginLogs[1]->meta['announcement_catch_up_count']);
        $this->assertSame('203.0.113.10', $loginLogs[0]->ip_address);
        $this->assertSame('MCARE Test Browser', $loginLogs[0]->user_agent);
    }

    public function test_publication_delivery_is_not_duplicated_by_a_later_password_login(): void
    {
        Notification::fake();

        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $this->actingAs($trainer)
            ->post(route('trainer.announcements.store'), [
                'training_batch_id' => $batch->id,
                'kind' => TrainerAnnouncement::KIND_ANNOUNCEMENT,
                'audience' => 'trainees',
                'title' => 'Skills laboratory schedule',
                'message' => 'The laboratory session begins at 8:00 AM.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('trainer.stream'));

        $announcement = TrainerAnnouncement::query()->firstOrFail();

        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'email' => $trainee->email,
            'password' => 'password',
        ])->assertRedirect(route('trainee.dashboard'));

        $this->assertCount(1, Notification::sent($trainee, LmsAnnouncementPublished::class));
        $this->assertDatabaseHas('announcement_deliveries', [
            'user_id' => $trainee->id,
            'announcement_type' => 'trainer',
            'announcement_id' => $announcement->id,
            'delivery_reason' => 'publication',
        ]);

        $loginLog = AdminActivityLog::query()
            ->where('user_id', $trainee->id)
            ->where('action', 'account.login.success')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(0, $loginLog->meta['announcement_catch_up_count']);
    }

    public function test_failed_google_callback_is_audited_without_storing_exception_details(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new RuntimeException('access_token=do-not-store-this'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('auth_error');

        $log = AdminActivityLog::query()
            ->where('action', 'account.login.google.failed')
            ->latest('id')
            ->firstOrFail();

        $this->assertNull($log->user_id);
        $this->assertSame(['reason' => 'oauth_callback_failed'], $log->meta);
        $this->assertStringNotContainsString('do-not-store-this', $log->toJson());
    }
}
