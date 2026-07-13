<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $logs = $this->filteredQuery($filters)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            ...$filters,
        ]);
    }

    public function print(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.logs.print', [
            'logs' => $this->filteredQuery($filters)->latest()->get(),
            ...$filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $logs = $this->filteredQuery($filters)->oldest()->get();
        $filename = 'mcare-admin-logs-'.$filters['period'].'-'.$filters['anchorDate']->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($logs, $filters) {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['MCARE Admin Activity Log']);
            fputcsv($stream, ['Coverage', $filters['rangeLabel']]);
            fputcsv($stream, ['Generated', now()->format('M d, Y g:i A')]);
            fputcsv($stream, []);
            fputcsv($stream, ['Date and time', 'Account name', 'Email', 'Action', 'IP address', 'Subject', 'Details']);

            foreach ($logs as $log) {
                fputcsv($stream, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $this->safeSpreadsheetValue($log->user?->name ?? 'System / unknown'),
                    $this->safeSpreadsheetValue($log->user?->email ?? ''),
                    $this->safeSpreadsheetValue($log->action),
                    $this->safeSpreadsheetValue($log->ip_address ?? ''),
                    $this->safeSpreadsheetValue(trim(class_basename($log->subject_type ?? '').' '.($log->subject_id ?? ''))),
                    $this->safeSpreadsheetValue($log->meta ? json_encode($log->meta, JSON_UNESCAPED_SLASHES) : ''),
                ]);
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function filters(Request $request): array
    {
        /*
         * Bound search and date inputs before running wildcard or range queries.
         * The selected range is shared by screen, print, and spreadsheet output.
         */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'period' => ['nullable', 'in:all,daily,weekly,monthly'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));
        $period = $validated['period'] ?? 'all';
        $anchorDate = CarbonImmutable::createFromFormat('Y-m-d', $validated['date'] ?? now()->format('Y-m-d'))->startOfDay();

        [$rangeStart, $rangeEnd, $rangeLabel] = match ($period) {
            'daily' => [$anchorDate, $anchorDate->endOfDay(), $anchorDate->format('F j, Y')],
            'weekly' => [
                $anchorDate->startOfWeek(),
                $anchorDate->endOfWeek(),
                $anchorDate->startOfWeek()->format('M j, Y').' - '.$anchorDate->endOfWeek()->format('M j, Y'),
            ],
            'monthly' => [
                $anchorDate->startOfMonth(),
                $anchorDate->endOfMonth(),
                $anchorDate->format('F Y'),
            ],
            default => [null, null, 'All recorded activity'],
        };

        return compact('search', 'period', 'anchorDate', 'rangeStart', 'rangeEnd', 'rangeLabel');
    }

    private function filteredQuery(array $filters): Builder
    {
        return AdminActivityLog::query()
            ->with('user')
            ->when($filters['rangeStart'], function (Builder $query) use ($filters) {
                $query->whereBetween('created_at', [$filters['rangeStart'], $filters['rangeEnd']]);
            })
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('action', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function safeSpreadsheetValue(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}
