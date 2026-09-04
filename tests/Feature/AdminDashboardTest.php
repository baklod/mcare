<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_admin_dashboard_shows_compact_action_queue_and_training_progress_chart(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $enrolled = User::factory()->create([
            'role' => 'trainee',
            'email' => 'enrolled.chart@gmail.com',
        ]);
        $passing = User::factory()->create([
            'role' => 'trainee',
            'email' => 'passing.chart@gmail.com',
        ]);

        $this->approvedApplication($enrolled, [
            'reviewed_at' => now(),
        ]);
        $this->approvedApplication($passing, [
            'reviewed_at' => now(),
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            'learning_status_changed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Training progress')
            ->assertSee('data-chart-enrolled="2"', false)
            ->assertSee('data-chart-passing="1"', false)
            ->assertSee('No urgent applications')
            ->assertSee('No learning modules yet')
            ->assertDontSee('Eligibility signal')
            ->assertDontSee('Capstone workflow coverage')
            ->assertDontSee('Caregiving NC II Orientation')
            ->assertDontSee('Secure PDF Viewer');
    }

    public function test_admin_dashboard_lists_current_learning_modules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['name' => 'Batch 1']);
        $published = $this->lmsModule($trainer, $batch, [
            'title' => 'Provide Care And Support To Infants And Toddlers',
        ]);
        $this->lmsModule($trainer, $batch, [
            'title' => 'Workplace Communication Draft',
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($published->title, false)
            ->assertSee('Active module', false)
            ->assertSee('Batch 1 2026', false)
            ->assertSee('waiting to unlock', false)
            ->assertSee('Workplace Communication Draft', false)
            ->assertSee('Draft — not available to trainees', false)
            ->assertDontSee('No learning modules yet')
            ->assertDontSee('Caregiving NC II Orientation')
            ->assertDontSee('Secure PDF Viewer');
    }

    /** @param array<string, mixed> $overrides */
    private function approvedApplication(User $user, array $overrides = []): EnrollmentApplication
    {
        return EnrollmentApplication::create(array_merge([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => str($user->name)->beforeLast(' ')->toString(),
            'last_name' => str($user->name)->afterLast(' ')->toString(),
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'reviewed_at' => now(),
        ], $overrides));
    }
}
