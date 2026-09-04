<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoStore
{
    public const DISK = 'public';

    public const DIRECTORY = 'avatars';

    public function storeUploaded(User $user, UploadedFile $file): string
    {
        $path = $file->store($this->directoryFor($user), self::DISK);

        return $this->remember($user, $path);
    }

    public function syncFromPrivateDisk(User $user, string $localPath): ?string
    {
        if ($localPath === '' || ! Storage::disk('local')->exists($localPath)) {
            return null;
        }

        $extension = strtolower((string) pathinfo($localPath, PATHINFO_EXTENSION));
        $mime = (string) (mime_content_type(Storage::disk('local')->path($localPath)) ?: '');

        if (
            ! str_starts_with($mime, 'image/')
            && ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
        ) {
            return null;
        }

        $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? $extension
            : 'jpg';
        $path = $this->directoryFor($user).'/'.Str::uuid()->toString().'.'.$safeExtension;
        Storage::disk(self::DISK)->put($path, Storage::disk('local')->get($localPath));

        return $this->remember($user, $path);
    }

    public function isManaged(User $user): bool
    {
        $path = (string) $user->profile_photo_path;

        return str_starts_with($path, self::DIRECTORY.'/'.$user->id.'/')
            && ! str_contains($path, '..');
    }

    public function deleteFor(User $user): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->directoryFor($user));
    }

    private function remember(User $user, string $path): string
    {
        $previous = is_string($user->profile_photo_path) ? $user->profile_photo_path : null;

        $user->update([
            'profile_photo_path' => $path,
        ]);

        if ($previous && $previous !== $path && str_starts_with($previous, self::DIRECTORY.'/')) {
            Storage::disk(self::DISK)->delete($previous);
        }

        return $this->publicUrl($path);
    }

    private function directoryFor(User $user): string
    {
        return self::DIRECTORY.'/'.$user->id;
    }

    private function publicUrl(string $path): string
    {
        return '/storage/'.$path;
    }
}
