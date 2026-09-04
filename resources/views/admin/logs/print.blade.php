<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE Admin Log Report - {{ $rangeLabel }}</title>
    <x-site-favicon />
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; color: #172033; font: 12px/1.45 Arial, sans-serif; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 20px; }
        .toolbar button { border: 0; border-radius: 8px; padding: 10px 16px; background: #6d28d9; color: #fff; font-weight: 700; cursor: pointer; }
        header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 16px; border-bottom: 2px solid #6d28d9; }
        h1 { margin: 3px 0 0; font-size: 24px; }
        .meta { color: #5b6475; text-align: right; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { border: 1px solid #cfd5df; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #eee9f8; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .details { max-width: 320px; overflow-wrap: anywhere; font: 10px/1.5 Arial, sans-serif; }
        .details .row { display: flex; gap: 6px; margin-bottom: 2px; }
        .details .row .k { font-weight: 700; color: #3f3355; min-width: 96px; }
        .details .row .v { color: #4b5563; }
        .details .section { margin-bottom: 6px; padding: 4px 6px; border: 1px solid #d5cee2; border-radius: 4px; background: #fbfaff; }
        .details .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6d28d9; margin-bottom: 3px; }
        .details .from { color: #b91c1c; font-weight: 600; }
        .details .to { color: #047857; font-weight: 600; }
        footer { margin-top: 18px; color: #697386; font-size: 10px; }
        @page { size: landscape; margin: 12mm; }
        @media print {
            body { padding: 0; }
            .toolbar { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print this report</button></div>
    <header>
        <div>
            <strong>MISSION CARE TRAINING CENTER</strong>
            <h1>Admin Activity Log</h1>
        </div>
        <div class="meta">
            <div><strong>Coverage:</strong> {{ $rangeLabel }}</div>
            <div><strong>Generated:</strong> {{ now()->format('M d, Y g:i A') }}</div>
            <div><strong>Records:</strong> {{ $logs->count() }}</div>
        </div>
    </header>
    <table>
        <thead>
            <tr><th>Date and time</th><th>Account</th><th>Action</th><th>IP</th><th>Subject</th><th>Details</th></tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('M d, Y g:i A') }}</td>
                    <td><strong>{{ $log->user?->name ?? 'System / unknown' }}</strong><br>{{ $log->user?->email }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->ip_address ?? 'Unknown' }}</td>
                    <td>{{ class_basename($log->subject_type ?? '') }} {{ $log->subject_id }}</td>
                    <td class="details">
                        @if ($log->meta)
                            @include('admin.logs.partials.meta-details-print', ['meta' => $log->meta])
                        @else
                            <em>No extra details</em>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No activity was recorded for this report.</td></tr>
            @endforelse
        </tbody>
    </table>
    <footer>This system-generated report is intended for authorized MCARE administrative review.</footer>
</body>
</html>
