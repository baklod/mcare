<?php

namespace Tests\Feature\Lms;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TraineeAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class AttendanceTrackingTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_can_view_attendance_sheet_and_save_daily_records(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $userA, 'application' => $traineeA] = $this->lmsTrainee($batch, ['first_name' => 'Maria', 'last_name' => 'Santos']);
        ['user' => $userB, 'application' => $traineeB] = $this->lmsTrainee($batch, ['first_name' => 'Juan', 'last_name' => 'Dela Cruz']);

        $this->actingAs($trainer)
            ->get(route('trainer.attendance.index', ['batch_id' => $batch->id]))
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Daily Sheet')
            ->assertSee('Mark All as Present');

        $response = $this->actingAs($trainer)
            ->post(route('trainer.attendance.store'), [
                'batch_id' => $batch->id,
                'date' => now()->toDateString(),
                'records' => [
                    $traineeA->id => [
                        'status' => 'present',
                        'notes' => 'On time for lecture',
                    ],
                    $traineeB->id => [
                        'status' => 'late',
                        'notes' => '15 mins late due to traffic',
                    ],
                ],
            ]);

        $response->assertRedirect(route('trainer.attendance.index', [
            'batch_id' => $batch->id,
            'date' => now()->toDateString(),
            'tab' => 'sheet',
        ]))->assertSessionHas('status');

        $this->actingAs($trainer)
            ->get(route('trainer.attendance.index', [
                'batch_id' => $batch->id,
                'date' => now()->toDateString(),
                'tab' => 'sheet',
            ]))
            ->assertOk()
            ->assertSee('data-dashboard-toast', false)
            ->assertSee('dashboard-toast-success', false)
            ->assertSee('Attendance recorded for 2 trainee(s)')
            ->assertDontSee('rounded-xl border border-emerald-200 bg-emerald-50/80', false);

        $this->assertDatabaseHas('trainee_attendances', [
            'training_batch_id' => $batch->id,
            'enrollment_application_id' => $traineeA->id,
            'status' => 'present',
            'notes' => 'On time for lecture',
        ]);

        $this->assertDatabaseHas('trainee_attendances', [
            'training_batch_id' => $batch->id,
            'enrollment_application_id' => $traineeB->id,
            'status' => 'late',
            'notes' => '15 mins late due to traffic',
        ]);
    }

    public function test_graduated_trainees_are_excluded_from_the_attendance_sheet(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);

        ['user' => $activeUser, 'application' => $activeTrainee] = $this->lmsTrainee($batch, [
            'first_name' => 'Active',
            'last_name' => 'Learner',
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
        ]);

        ['user' => $gradUser, 'application' => $gradTrainee] = $this->lmsTrainee($batch, [
            'first_name' => 'Graduated',
            'last_name' => 'Alumnus',
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.attendance.index', ['batch_id' => $batch->id]))
            ->assertOk()
            ->assertSee('Active Learner')
            ->assertDontSee('Graduated Alumnus');
    }

    public function test_trainer_and_admin_can_export_batch_attendance_xlsx_and_csv(): void
    {
        $trainer = $this->lmsUser('trainer');
        $admin = $this->lmsUser('admin');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $user, 'application' => $trainee] = $this->lmsTrainee($batch);

        TraineeAttendance::create([
            'training_batch_id' => $batch->id,
            'enrollment_application_id' => $trainee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
            'check_in_type' => 'daily_sheet',
            'timed_in_at' => now(),
            'recorded_by_id' => $trainer->id,
        ]);

        // Default export: Excel OpenSpout .xlsx
        $trainerXlsxResponse = $this->actingAs($trainer)
            ->get(route('trainer.attendance.export', $batch));

        $trainerXlsxResponse->assertOk();
        $trainerXlsxResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $adminXlsxResponse = $this->actingAs($admin)
            ->get(route('admin.learning.attendance.export', $batch));

        $adminXlsxResponse->assertOk();
        $adminXlsxResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // CSV export via format=csv
        $trainerCsvResponse = $this->actingAs($trainer)
            ->get(route('trainer.attendance.export', ['batch' => $batch, 'format' => 'csv']));

        $trainerCsvResponse->assertOk();
        $trainerCsvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_quizzes_do_not_offer_or_record_automatic_attendance_time_in(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $user, 'application' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);

        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Self-Paced Patient Care Activity',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'available_at' => now()->subHour(),
            'due_at' => now()->addDays(2),
            'time_limit_minutes' => 45,
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ]);

        $this->actingAs($user)
            ->get(route('trainee.quizzes.show', $quiz))
            ->assertOk()
            ->assertDontSee('Activity Attendance')
            ->assertDontSee('Record Time-In');

        $this->actingAs($user)
            ->post("/trainee/quizzes/{$quiz->id}/time-in")
            ->assertNotFound();

        $this->assertDatabaseMissing('trainee_attendances', [
            'enrollment_application_id' => $trainee->id,
            'quiz_id' => $quiz->id,
        ]);
    }
}
