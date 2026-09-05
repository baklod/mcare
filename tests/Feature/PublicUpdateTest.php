<?php

namespace Tests\Feature;

use App\Models\PublicSiteSetting;
use App\Models\PublicUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_facebook_update_to_the_landing_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $facebookUrl = 'https://www.facebook.com/mcare.official/videos/123456789012345/';

        $this->actingAs($admin)
            ->get(route('admin.public-settings.index'))
            ->assertOk()
            ->assertSee('Public Settings')
            ->assertSee('Social media links')
            ->assertDontSee('TESDA form registrar')
            ->assertSee('Add Facebook update')
            ->assertSee('data-public-update-dialog', false);

        $this->actingAs($admin)
            ->post(route('admin.public-settings.store'), [
                'title' => 'Skills lab demo',
                'description' => 'Trainees practice patient transfer.',
                'facebook_url' => $facebookUrl,
                'position' => 1,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.public-settings.index'))
            ->assertSessionHas('saved');

        $update = PublicUpdate::query()->firstOrFail();
        $this->assertSame('Skills lab demo', $update->title);
        $this->assertSame($facebookUrl, $update->facebook_url);
        $this->assertTrue($update->is_published);
        $this->assertStringContainsString('plugins/video.php', $update->embedSrc());
        $this->assertStringContainsString(rawurlencode($facebookUrl), $update->embedSrc());

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Skills lab demo')
            ->assertSee('Trainees practice patient transfer.')
            ->assertSee($facebookUrl, false)
            ->assertSee(e($update->embedSrc()), false)
            ->assertDontSee('https://www.facebook.com/facebook/videos/10153231379946729/')
            ->assertDontSee('Training highlights');
    }

    public function test_landing_hides_unpublished_updates_and_the_old_static_embed(): void
    {
        PublicUpdate::query()->create([
            'title' => 'Hidden draft reel',
            'description' => 'Should stay off the public site.',
            'facebook_url' => 'https://www.facebook.com/reel/987654321/',
            'position' => 1,
            'is_published' => false,
        ]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('No public updates yet')
            ->assertDontSee('Hidden draft reel')
            ->assertDontSee('https://www.facebook.com/facebook/videos/10153231379946729/')
            ->assertDontSee('Student activities');
    }

    public function test_admin_cannot_save_a_non_facebook_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.public-settings.index'))
            ->post(route('admin.public-settings.store'), [
                'title' => 'Invalid link',
                'facebook_url' => 'https://example.com/video',
                'position' => 1,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.public-settings.index'))
            ->assertSessionHasErrorsIn('publicUpdate', [
                'facebook_url' => 'Use a public Facebook post, video, or reel link.',
            ]);

        $this->assertDatabaseCount('public_updates', 0);
    }

    public function test_admin_can_save_social_links_for_the_landing_footer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $facebook = 'https://www.facebook.com/mcare.official';
        $instagram = 'https://www.instagram.com/mcare.official';
        $youtube = 'https://www.youtube.com/@mcareofficial';

        $this->actingAs($admin)
            ->patch(route('admin.public-settings.social'), [
                'social_facebook_url' => $facebook,
                'social_instagram_url' => $instagram,
                'social_youtube_url' => $youtube,
            ])
            ->assertRedirect(route('admin.public-settings.index'))
            ->assertSessionHas('saved', 'Social media links saved.');

        $settings = PublicSiteSetting::query()->firstOrFail();
        $this->assertSame($facebook, $settings->facebook_url);
        $this->assertSame($instagram, $settings->instagram_url);
        $this->assertSame($youtube, $settings->youtube_url);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee($facebook, false)
            ->assertSee($instagram, false)
            ->assertSee($youtube, false)
            ->assertSee('Open MCARE Facebook page from footer', false);
    }

    public function test_landing_hides_social_icons_until_admin_saves_links(): void
    {
        $html = $this->get(route('landing'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Open MCARE Facebook page from footer', $html);
        $this->assertStringNotContainsString('https://www.instagram.com/', $html);
        $this->assertStringNotContainsString('https://www.youtube.com/', $html);
    }

    public function test_admin_cannot_save_a_non_instagram_social_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.public-settings.index'))
            ->patch(route('admin.public-settings.social'), [
                'social_facebook_url' => '',
                'social_instagram_url' => 'https://example.com/mcare',
                'social_youtube_url' => '',
            ])
            ->assertRedirect(route('admin.public-settings.index'))
            ->assertSessionHasErrorsIn('publicSocial', [
                'social_instagram_url' => 'Use a public Instagram profile link.',
            ]);

        $this->assertDatabaseCount('public_site_settings', 0);
    }

    public function test_non_admin_cannot_manage_public_updates(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->get(route('admin.public-settings.index'))
            ->assertForbidden();

        $this->actingAs($trainer)
            ->patch(route('admin.public-settings.social'), [
                'social_facebook_url' => 'https://www.facebook.com/mcare.official',
            ])
            ->assertForbidden();
    }
}
