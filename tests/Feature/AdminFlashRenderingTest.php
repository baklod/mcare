<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlashRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_page_renders_the_shared_success_notice_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['saved' => 'Account saved successfully.'])
            ->get(route('admin.accounts.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Account saved successfully.'));
    }
}
