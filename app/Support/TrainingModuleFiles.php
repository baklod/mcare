<?php

namespace App\Support;

use App\Models\TrainingModule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

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
            'ppt', 'pptx',
            'doc', 'docx',
            'jpg', 'jpeg', 'png', 'webp', 'gif',
            'mp4', 'webm', 'mov',
            'mp3', 'wav', 'm4a', 'ogg',
        ];
    }

    public static function acceptAttribute(): string
    {
        return collect(self::extensions())
            ->map(fn (string $extension): string => '.'.$extension)
            ->implode(',');
    }

    public static function validationError(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (! in_array($extension, self::extensions(), true)) {
            return 'Learning materials must use one of the supported PDF, Office, image, video, or audio formats.';
        }

        $allowedMimes = self::mimeMap()[$extension] ?? [];
        if (! in_array($mime, $allowedMimes, true)) {
            return "The file contents do not match the .{$extension} file type.";
        }

        if (in_array($extension, ['docx', 'pptx'], true)
            && in_array($mime, ['application/zip', 'application/x-zip-compressed'], true)
            && ! self::hasOpenXmlStructure($file, $extension)) {
            return 'The uploaded Office file is not a valid DOCX or PPTX package.';
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
            'ppt' => ['application/vnd.ms-powerpoint', 'application/mspowerpoint', 'application/powerpoint', 'application/cdfv2', 'application/x-ole-storage'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/x-zip-compressed'],
            'doc' => ['application/msword', 'application/cdfv2', 'application/x-ole-storage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'gif' => ['image/gif'],
            'mp4' => ['video/mp4', 'application/mp4'],
            'webm' => ['video/webm', 'audio/webm'],
            'mov' => ['video/quicktime'],
            'mp3' => ['audio/mpeg', 'audio/mp3'],
            'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave'],
            'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
            'ogg' => ['audio/ogg', 'application/ogg'],
        ];
    }

    private static function hasOpenXmlStructure(UploadedFile $file, string $extension): bool
    {
        if (! class_exists(ZipArchive::class)) {
            return false;
        }

        $archive = new ZipArchive;
        if ($archive->open($file->getRealPath()) !== true) {
            return false;
        }

        try {
            $requiredDirectory = $extension === 'docx' ? 'word/' : 'ppt/';

            return $archive->locateName('[Content_Types].xml') !== false
                && collect(range(0, max(0, $archive->numFiles - 1)))
                    ->contains(fn (int $index): bool => str_starts_with((string) $archive->getNameIndex($index), $requiredDirectory));
        } finally {
            $archive->close();
        }
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

                $path = $file->store("training-modules/{$userId}/supplementary", 'local');
                if ($path === false) {
                    throw new \RuntimeException('A supplementary learning file could not be stored.');
                }

                $size = (int) ($file->getSize() ?: 0);
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
}
