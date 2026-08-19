<?php

namespace App\Jobs;

use App\Models\BatchDocumentExport;
use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Services\OfficialDocumentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class GenerateBatchTorExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly int $exportId) {}

    public function handle(OfficialDocumentManager $manager): void
    {
        $export = BatchDocumentExport::query()->findOrFail($this->exportId);
        $admin = User::query()->findOrFail($export->requested_by_id);
        $query = EnrollmentApplication::query()
            ->where('training_batch_id', $export->training_batch_id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->orderBy('id');

        $export->update([
            'status' => BatchDocumentExport::STATUS_PROCESSING,
            'total_records' => $query->count(),
            'processed_records' => 0,
            'failure_reason' => null,
        ]);

        $temporaryZip = tempnam(sys_get_temp_dir(), 'mcare-tor-');
        $zip = new ZipArchive;

        try {
            if ($zip->open($temporaryZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create the TOR archive.');
            }

            $processed = 0;
            $query->chunkById(25, function ($applications) use (
                $manager,
                $admin,
                $export,
                $zip,
                &$processed,
            ): void {
                foreach ($applications as $application) {
                    $document = $manager->generateTorForExport($application, $admin);

                    if ($document?->file_path) {
                        $name = str($application->last_name.'-'.$application->first_name)
                            ->slug()
                            ->append('-'.$application->id.'-TOR.pdf')
                            ->toString();
                        $zip->addFromString(
                            $name,
                            Storage::disk($document->storage_disk)->get($document->file_path),
                        );
                    }

                    $processed++;
                    $export->update(['processed_records' => $processed]);
                }
            });

            $zip->close();
            $path = sprintf(
                'official-documents/exports/batch-%d/tor-export-%d.zip',
                $export->training_batch_id,
                $export->id,
            );

            $archiveStream = fopen($temporaryZip, 'rb');

            if ($archiveStream === false) {
                throw new \RuntimeException('Could not open the completed TOR archive.');
            }

            try {
                if (! Storage::disk($export->storage_disk)->put($path, $archiveStream)) {
                    throw new \RuntimeException('Could not store the TOR archive.');
                }
            } finally {
                fclose($archiveStream);
            }

            $export->update([
                'status' => BatchDocumentExport::STATUS_READY,
                'file_path' => $path,
                'completed_at' => now(),
                'expires_at' => now()->addHours(config('official_documents.batch_export_expiry_hours', 24)),
            ]);
        } catch (Throwable $exception) {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }

            $export->update([
                'status' => BatchDocumentExport::STATUS_FAILED,
                'failure_reason' => str($exception->getMessage())->limit(2000)->toString(),
            ]);

            throw $exception;
        } finally {
            if (is_file($temporaryZip)) {
                @unlink($temporaryZip);
            }
        }
    }
}
