<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_print_and_export_daily_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Daily Admin']);
        AdminActivityLog::withoutTimestamps(function () use ($admin) {
            AdminActivityLog::query()->forceCreate([
                'user_id' => $admin->id,
                'action' => 'daily.visible.action',
                'ip_address' => '127.0.0.1',
                'meta' => ['result' => 'accepted'],
                'created_at' => '2026-07-13 08:30:00',
                'updated_at' => '2026-07-13 08:30:00',
            ]);
            AdminActivityLog::query()->forceCreate([
                'user_id' => $admin->id,
                'action' => 'older.hidden.action',
                'ip_address' => '127.0.0.2',
                'created_at' => '2026-07-12 08:30:00',
                'updated_at' => '2026-07-12 08:30:00',
            ]);
        });
        $query = ['period' => 'daily', 'date' => '2026-07-13'];

        $this->actingAs($admin)
            ->get(route('admin.logs.index', $query))
            ->assertOk()
            ->assertSee('daily.visible.action')
            ->assertDontSee('older.hidden.action');

        $this->actingAs($admin)
            ->get(route('admin.logs.print', $query))
            ->assertOk()
            ->assertSee('MCARE Admin Log Report', false)
            ->assertSee('daily.visible.action')
            ->assertDontSee('older.hidden.action');

        $export = $this->actingAs($admin)->get(route('admin.logs.export', $query));
        $export->assertOk()->assertDownload('mcare-admin-logs-daily-2026-07-13.csv');
        $csv = $export->streamedContent();
        $this->assertStringContainsString('daily.visible.action', $csv);
        $this->assertStringNotContainsString('older.hidden.action', $csv);
    }

    public function test_non_admin_cannot_export_admin_logs(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($trainee)
            ->get(route('admin.logs.export'))
            ->assertForbidden();
    }
}
