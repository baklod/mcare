<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraineeRosterCsv
{
    public function download(Collection $trainees, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($trainees) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Learner name',
                'Email',
                'Contact number',
                'Batch',
                'Schedule',
                'Learning status',
                'Payment status',
                'Total tuition',
                'Total paid',
                'Remaining balance',
                'Downpayment cleared',
                'Latest OR number',
                'Completed modules',
                'Modules in progress',
                'Approval date',
            ]);

            foreach ($trainees as $trainee) {
                $latestOr = $trainee->paymentTransactions?->where('status', 'verified')->first()?->or_number
                    ?? $trainee->payment_receipt_number
                    ?? '';

                fputcsv($output, array_map($this->excelSafe(...), [
                    trim("{$trainee->last_name}, {$trainee->first_name} {$trainee->middle_name}"),
                    $trainee->email,
                    $trainee->contact_number,
                    $trainee->batch ? "{$trainee->batch->name} {$trainee->batch->year}" : 'Unassigned',
                    $trainee->schedule_preference,
                    $trainee->learningStatusLabel(),
                    $trainee->paymentStatusLabel(),
                    number_format((float) ($trainee->total_program_fee ?? 22000.00), 2),
                    number_format((float) ($trainee->total_paid_amount ?? 0.00), 2),
                    number_format($trainee->remainingBalance(), 2),
                    $trainee->isDownpaymentSatisfied() ? 'Yes' : 'No',
                    $latestOr,
                    $trainee->moduleProgress->where('status', 'completed')->count(),
                    $trainee->moduleProgress->where('status', 'in_progress')->count(),
                    $trainee->reviewed_at?->format('Y-m-d') ?? '',
                ]));
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function excelSafe(mixed $value): string
    {
        $value = (string) $value;

        // Prevent imported text from being interpreted as a spreadsheet formula.
        return preg_match('/^[=+\-@]/', ltrim($value)) ? "'{$value}" : $value;
    }
}
