<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_account_settings(): void
    {
        $this->get(route('account.settings'))->assertRedirect(route('login'));
    }

    public function test_admin_trainer_and_trainee_can_open_role_aware_settings_and_help(): void
    {
        foreach (['admin', 'trainer', 'trainee'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('account.settings'))
                ->assertOk()
                ->assertSee('Night mode')
                ->assertSee('Change password');

            $this->actingAs($user)
                ->get(route('account.help'))
                ->assertOk()
                ->assertSee('Help for');
        }
    }

    public function test_user_can_change_password_and_the_action_is_logged(): void
    {
        $trainer = User::factory()->create([
            'role' => 'trainer',
            'password' => 'OldPassword123',
        ]);

        $this->actingAs($trainer)
            ->patch(route('account.password.update'), [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertTrue(Hash::check('NewPassword456', $trainer->fresh()->password));
        $this->assertTrue(AdminActivityLog::query()->where('action', 'account.password.updated')->exists());
    }

    public function test_dashboard_account_dropdowns_include_the_new_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Night mode')
            ->assertSee('Settings')
            ->assertSee('Help')
            ->assertDontSee('data-dashboard-notifications', false);

        $trainer = User::factory()->create(['role' => 'trainer']);
        $this->actingAs($trainer)->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Night mode')
            ->assertSee('Settings')
            ->assertSee('Help')
            ->assertDontSee('data-dashboard-notifications', false);

        $trainee = User::factory()->create(['role' => 'trainee']);
        EnrollmentApplication::query()->create([
            'user_id' => $trainee->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Trainee',
            'last_name' => 'Sample',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
        ]);
        $this->actingAs($trainee)->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Night mode')
            ->assertSee('Settings')
            ->assertSee('Help')
            ->assertDontSee('data-dashboard-notifications', false);
    }
}
