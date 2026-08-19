<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\TrainingBatch;
use App\Services\CompetencyWorkbookExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompetencyWorkbookController extends Controller
{
    public function downloadForTrainer(
        Request $request,
        TrainingBatch $trainingBatch,
        CompetencyWorkbookExporter $exporter,
    ): BinaryFileResponse {
        $validated = $this->validateFilters($request);

        return $this->download($request, $trainingBatch, $validated['schedule'] ?? null, $exporter);
    }

    public function downloadForAdmin(
        Request $request,
        CompetencyWorkbookExporter $exporter,
    ): BinaryFileResponse {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $batch = TrainingBatch::query()->findOrFail($validated['batch_id']);

        return $this->download($request, $batch, $validated['schedule'] ?? null, $exporter);
    }

    /** @return array{schedule?: string|null} */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
    }

    private function download(
        Request $request,
        TrainingBatch $batch,
        ?string $schedule,
        CompetencyWorkbookExporter $exporter,
    ): BinaryFileResponse {
        $export = $exporter->build($batch, $schedule);
        $role = (string) $request->user()->role;

        AdminActivityLog::record(
            $request->user(),
            $role.'.competency-workbook.downloaded',
            $batch,
            [
                'schedule' => $schedule,
                'trainee_count' => $export['trainee_count'],
                'filename' => $export['filename'],
            ],
        );

        return response()->download($export['path'], $export['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}
