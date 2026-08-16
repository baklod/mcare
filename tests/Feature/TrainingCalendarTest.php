<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Services\TrainingCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrainingCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_calendar_combines_sessions_from_multiple_batches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dayPattern = strtoupper(now()->format('D'));

        $this->scheduledBatch('Calendar Batch Alpha', $dayPattern, '08:00', '10:00', 'Room Alpha', true);
        $this->scheduledBatch('Calendar Batch Beta', $dayPattern, '10:30', '12:30', 'Room Beta');

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Admin master calendar')
            ->assertSee('Calendar Batch Alpha')
            ->assertSee('Calendar Batch Beta')
            ->assertSee('Room Alpha')
            ->assertSee('Room Beta')
            ->assertSee('data-training-calendar', false)
            ->assertSee('data-calendar-month-url', false)
            ->assertSee('data-batch-dialog', false)
            ->assertSee('data-batch-dialog-open', false)
            ->assertSee('data-auto-open="false"', false);
    }

    public function test_admin_batch_edit_opens_the_existing_batch_in_the_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->scheduledBatch('Modal Edit Batch', 'MWF', '08:00', '12:00', 'Skills Lab');

        $this->actingAs($admin)
            ->get(route('admin.schedules.edit', $batch))
            ->assertOk()
            ->assertSee('data-batch-dialog', false)
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('Edit batch')
            ->assertSee('Modal Edit Batch')
            ->assertSee(route('admin.schedules.update', $batch), false);
    }

    public function test_trainee_calendar_only_contains_their_assigned_period(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $dayPattern = strtoupper(now()->format('D'));
        $batch = TrainingBatch::create([
            'name' => 'Filtered Calendar Batch',
            'year' => now()->year,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->startOfMonth(),
            'training_ends_at' => now()->endOfMonth(),
            'am_days' => $dayPattern,
            'am_start_time' => '08:00',
            'am_end_time' => '10:00',
            'am_room' => 'AM Skills Room',
            'pm_days' => $dayPattern,
            'pm_start_time' => '13:00',
            'pm_end_time' => '15:00',
            'pm_room' => 'PM Lecture Room',
        ]);
        $this->approvedApplication($trainee, $batch, 'AM');

        $this->actingAs($trainee)
            ->get(route('trainee.schedule', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Read-only class calendar')
            ->assertSee('8:00 AM - 10:00 AM')
            ->assertSee('AM Skills Room')
            ->assertDontSee('1:00 PM - 3:00 PM')
            ->assertDontSee('PM Lecture Room')
            ->assertSee('data-calendar-agenda', false);
    }

    public function test_weekend_calendar_only_returns_weekend_sessions_and_hides_admin_notes(): void
    {
        $month = now()->startOfMonth();
        $batch = TrainingBatch::create([
            'name' => 'Weekend Calendar Batch',
            'year' => $month->year,
            'enrollment_ends_at' => $month->copy()->addMonth(),
            'training_starts_at' => $month->copy()->startOfMonth(),
            'training_ends_at' => $month->copy()->endOfMonth(),
            'am_days' => 'MON',
            'am_start_time' => '08:00',
            'am_end_time' => '10:00',
            'am_room' => 'Weekday Room',
            'pm_days' => 'SAT',
            'pm_start_time' => '13:00',
            'pm_end_time' => '15:00',
            'pm_room' => 'Weekend Room',
            'notes' => 'Internal staffing concern - do not show learners.',
        ]);

        $sessions = app(TrainingCalendarService::class)->month($batch, $month, 'Weekend');

        $this->assertNotEmpty($sessions);
        $this->assertTrue($sessions->every(fn (array $session) => $session['date']->isWeekend()));
        $this->assertTrue($sessions->every(fn (array $session) => $session['room'] === 'Weekend Room'));
        $this->assertTrue($sessions->every(
            fn (array $session) => ! str_contains($session['title'], 'Internal staffing concern')
        ));
    }

    public function test_calendar_does_not_invent_sessions_without_training_boundaries(): void
    {
        $batch = TrainingBatch::create([
            'name' => 'Unscheduled Calendar Batch',
            'year' => now()->year,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'am_start_time' => '08:00',
            'am_end_time' => '10:00',
            'pm_days' => 'TTS',
            'pm_start_time' => '13:00',
            'pm_end_time' => '15:00',
        ]);

        $sessions = app(TrainingCalendarService::class)->month($batch, now()->startOfMonth());

        $this->assertEmpty($sessions);
    }

    public function test_ongoing_batch_defaults_to_the_current_month(): void
    {
        $reference = Carbon::parse('2026-07-15 09:00:00');
        $batch = new TrainingBatch([
            'training_starts_at' => Carbon::parse('2026-01-05 08:00:00'),
            'training_ends_at' => Carbon::parse('2026-12-18 17:00:00'),
        ]);

        $month = app(TrainingCalendarService::class)->suggestedMonth($batch, $reference);

        $this->assertSame('2026-07', $month->format('Y-m'));
    }

    private function scheduledBatch(
        string $name,
        string $dayPattern,
        string $start,
        string $end,
        string $room,
        bool $active = false,
    ): TrainingBatch {
        return TrainingBatch::create([
            'name' => $name,
            'year' => now()->year,
            'is_active' => $active,
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->startOfMonth(),
            'training_ends_at' => now()->endOfMonth(),
            'am_days' => $dayPattern,
            'am_start_time' => $start,
            'am_end_time' => $end,
            'am_room' => $room,
            'pm_days' => 'SUN',
        ]);
    }

    private function approvedApplication(User $user, TrainingBatch $batch, string $period): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Calendar',
            'last_name' => 'Trainee',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => $period,
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_APPROVED,
        ]);
    }
}
