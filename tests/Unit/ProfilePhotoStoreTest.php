<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ProfilePhotoStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_photo_replaces_a_previous_public_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $store = new ProfilePhotoStore;

        $firstUrl = $store->storeUploaded($user, UploadedFile::fake()->image('first.jpg', 60, 60));
        $firstPath = $user->fresh()->profile_photo_path;
        $this->assertNotNull($firstPath);
        $this->assertTrue(Storage::disk('public')->exists($firstPath));

        $secondUrl = $store->storeUploaded($user->fresh(), UploadedFile::fake()->image('second.png', 80, 80));
        $secondPath = $user->fresh()->profile_photo_path;
        $this->assertNotNull($secondPath);

        $this->assertNotSame($firstUrl, $secondUrl);
        $user = $user->fresh();
        $this->assertSame($secondUrl, $user->profilePhotoUrl());
        $this->assertSame($secondPath, $user->profile_photo_path);
        $this->assertTrue(Storage::disk('public')->exists($secondPath));
        $this->assertFalse(Storage::disk('public')->exists($firstPath));
    }

    public function test_private_id_photo_is_copied_onto_the_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create(['avatar_url' => 'https://example.test/google.jpg']);
        Storage::disk('local')->put('enrollment-documents/'.$user->id.'/id-photo.jpg', 'id-photo-bytes');

        $url = (new ProfilePhotoStore)->syncFromPrivateDisk($user, 'enrollment-documents/'.$user->id.'/id-photo.jpg');
        $user->refresh();

        $this->assertStringStartsWith('/storage/avatars/'.$user->id.'/', (string) $url);
        $this->assertNotNull($user->profile_photo_path);
        $this->assertTrue(Storage::disk('public')->exists($user->profile_photo_path));
        $this->assertTrue(Storage::disk('local')->exists('enrollment-documents/'.$user->id.'/id-photo.jpg'));
        $this->assertTrue((new ProfilePhotoStore)->isManaged($user));
        $this->assertFalse((new ProfilePhotoStore)->isManaged(User::factory()->create([
            'avatar_url' => 'https://example.test/google.jpg',
        ])));
    }
}
