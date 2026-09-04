<?php

namespace App\Support;

use App\Models\TrainingModule;
use App\Services\LearningPdfWatermark;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TrainingModuleFiles
{
    public const MAX_UPLOAD_KB = 38912;

    public const MAX_SUPPLEMENTARY_FILES = 10;

    public const MAX_SUPPLEMENTARY_UPLOAD_KB = 25600;

    /** @return list<string> */
    public static function extensions(): array
    {
        return [
            'pdf',
            'jpg', 'jpeg', 'png', 'webp', 'gif',
        ];
    }

    public static function humanLabel(): string
    {
        return 'PDF or image';
    }

    public static function acceptAttribute(): string
    {
        return collect(self::extensions())
            ->map(fn (string $extension): string => '.'.$extension)
            ->implode(',');
    }

    public static function mimesRule(): string
    {
        return 'mimes:'.implode(',', self::extensions());
    }

    public static function validationError(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (! in_array($extension, self::extensions(), true)) {
            return 'The file must be a PDF or image.';
        }

        $allowedMimes = self::mimeMap()[$extension] ?? [];
        if (! in_array($mime, $allowedMimes, true)) {
            return "The file contents do not match the .{$extension} file type.";
        }

        return null;
    }

    public static function previewKind(TrainingModule $module): string
    {
        $mime = strtolower((string) $module->mime_type);
        $extension = strtolower(pathinfo($module->original_file_name, PATHINFO_EXTENSION));

        return match (true) {
            $mime === 'application/pdf' || $extension === 'pdf' => 'pdf',
            str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) => 'image',
            str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'mov'], true) => 'video',
            str_starts_with($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'm4a', 'ogg'], true) => 'audio',
            in_array($extension, ['ppt', 'pptx', 'doc', 'docx'], true) => 'office',
            default => 'download',
        };
    }

    public static function typeLabel(TrainingModule $module): string
    {
        $extension = strtoupper(pathinfo($module->original_file_name, PATHINFO_EXTENSION));

        return match (self::previewKind($module)) {
            'pdf' => 'PDF document',
            'image' => $extension.' image',
            'video' => $extension.' video',
            'audio' => $extension.' audio',
            'office' => $extension.' Office document',
            default => $extension ? $extension.' file' : 'Downloadable file',
        };
    }

    /** @return array<string, list<string>> */
    private static function mimeMap(): array
    {
        return [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'gif' => ['image/gif'],
        ];
    }

    /**
     * Store supplementary learning attachments and return structured metadata.
     *
     * @param  list<UploadedFile>  $uploadedFiles
     * @return list<array{file_path: string, original_name: string, mime_type: string, file_size: int, human_size: string}>
     */
    public static function storeSupplementaryFiles(array $uploadedFiles, int $userId): array
    {
        $stored = [];

        try {
            foreach ($uploadedFiles as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }

                $path = self::storeLearningFile($file, "training-modules/{$userId}/supplementary");
                $size = (int) Storage::disk('local')->size($path);
                $stored[] = [
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => (string) $file->getMimeType(),
                    'file_size' => $size,
                    'human_size' => self::formatBytes($size),
                ];
            }
        } catch (\Throwable $exception) {
            self::deleteSupplementaryFiles($stored);

            throw $exception;
        }

        return $stored;
    }

    /**
     * @param  array<int, array<string, mixed>>  $storedFiles
     */
    public static function deleteSupplementaryFiles(array $storedFiles): void
    {
        $paths = collect($storedFiles)
            ->pluck('file_path')
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();

        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }
    }

    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $power = max(0, $power);
        $value = $bytes / (1024 ** $power);

        return round($value, $precision).' '.$units[$power];
    }

    public static function storeLearningFile(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'local');
        if ($path === false) {
            throw new \RuntimeException('The learning file could not be stored.');
        }

        app(LearningPdfWatermark::class)->stampStoredFile(
            $path,
            $file->getClientOriginalName(),
            $file->getMimeType(),
        );

        return $path;
    }
}
