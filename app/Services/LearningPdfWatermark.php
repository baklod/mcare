<?php

namespace App\Services;

use App\Support\WatermarkedFpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LearningPdfWatermark
{
    public function isPdf(?string $originalName = null, ?string $mime = null, ?string $storagePath = null): bool
    {
        $extension = strtolower(pathinfo((string) ($originalName ?: $storagePath), PATHINFO_EXTENSION));
        $mime = strtolower((string) $mime);

        return $extension === 'pdf'
            || in_array($mime, [
                'application/pdf',
                'application/x-pdf',
                'application/acrobat',
                'application/vnd.adobe.pdf',
                'application/vnd.pdf',
                'text/pdf',
            ], true);
    }

    public function stampStoredFile(string $storagePath, ?string $originalName = null, ?string $mime = null): int
    {
        return (int) Storage::disk('local')->size($storagePath);
    }

    public function respond(
        string $storagePath,
        string $filename,
        ?string $mime,
        string $disposition,
    ): BinaryFileResponse|StreamedResponse {
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();
        $headers = [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename, $fallbackFilename),
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($this->isPdf($filename, $mime, $storagePath)) {
            $stamped = $this->stampAbsolutePath(Storage::disk('local')->path($storagePath));

            if (is_string($stamped) && $stamped !== '') {
                return response()->streamDownload(function () use ($stamped): void {
                    echo $stamped;
                }, $filename, [
                    ...$headers,
                    'Content-Type' => 'application/pdf',
                ], $disposition);
            }

            $headers['Content-Type'] = 'application/pdf';
        }

        return response()->file(Storage::disk('local')->path($storagePath), [
            ...$headers,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function stampAbsolutePath(string $absolutePath): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        try {
            $pdf = new WatermarkedFpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);
            $pageCount = $pdf->setSourceFile($absolutePath);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
                $pdf->paintWatermark();
            }

            $output = $pdf->Output('S');

            return is_string($output) && $output !== '' ? $output : null;
        } catch (Throwable $exception) {
            Log::warning('Learning PDF watermark could not be applied.', [
                'path_basename' => basename($absolutePath),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
