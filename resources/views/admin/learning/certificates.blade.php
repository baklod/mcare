@extends('admin.layouts.app', ['title' => 'Training Records | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="dashboard-section-kicker">Learning system - Official records</p><h1 class="dashboard-section-title mt-2 text-3xl">COTC and transcript issuance</h1></div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="competency-excel-dialog" class="secondary-action inline-flex items-center gap-2"><x-dashboard-icon name="file-excel" class="h-4 w-4" />Export competency Excel</button>
            <button type="button" data-dashboard-dialog-open="batch-tor-dialog" class="primary-action inline-flex items-center gap-2"><x-dashboard-icon name="folder-open" class="h-4 w-4" />Prepare batch TOR ZIP</button>
        </div>
    </header>

    @if($errors->any())<div class="border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-bold">The action could not be completed.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div><label class="form-label">Batch</label><select name="batch_id" class="form-field"><option value="">All batches</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
        <div><label class="form-label">Class</label><select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select></div>
        <div><label class="form-label">Completion gate</label><select name="eligibility" class="form-field"><option value="">All states</option><option value="eligible" @selected(($filters['eligibility'] ?? '') === 'eligible')>Eligible</option><option value="blocked" @selected(($filters['eligibility'] ?? '') === 'blocked')>Blocked</option></select></div>
        <div class="flex items-end gap-2"><button class="primary-action">Apply</button><a class="secondary-action" href="{{ route('admin.learning.certificates') }}">Reset</a></div>
    </form>

    <div class="space-y-4">
        @forelse($records as $record)
            @php
                $cotc = $record->officialDocuments->firstWhere('type', 'cotc');
                $tor = $record->officialDocuments->firstWhere('type', 'tor');
                $eligibility = $record->completion_eligibility;
            @endphp
            <article class="dashboard-panel">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div><p class="font-bold text-slate-950">{{ $record->last_name }}, {{ $record->first_name }} {{ $record->middle_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $record->batch ? $record->batch->name.' '.$record->batch->year : 'Unassigned' }} - {{ $record->schedule_preference }} class</p></div>
                    <span class="dashboard-pill {{ $eligibility['eligible'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">{{ $eligibility['eligible'] ? 'Eligible for issuance' : 'Completion checks pending' }}</span>
                </div>

                <div class="mt-5 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($eligibility['checks'] as $check)
                        <div class="border border-slate-200 p-3"><p class="text-xs font-bold {{ $check['passed'] ? 'text-emerald-700' : 'text-slate-500' }}">{{ $check['passed'] ? 'PASS' : 'WAIT' }} - {{ $check['label'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $check['detail'] }}</p></div>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-4 border-t border-slate-200 pt-5 lg:grid-cols-2">
                    <div class="border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-900">Certificate of Training Completion</p><p class="mt-1 text-xs text-slate-500">Trainee receives one download after release.</p></div>@if($cotc)<span class="dashboard-pill bg-slate-100 text-slate-700 ring-slate-200">{{ str($cotc->status)->headline() }}</span>@endif</div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if(!$cotc)
                                <form method="POST" action="{{ route('admin.learning.documents.generate', [$record, 'cotc']) }}">@csrf<button class="primary-action" @disabled(!$eligibility['eligible'])>Generate COTC</button></form>
                            @elseif($cotc->status === 'generated')
                                <a class="secondary-action" href="{{ route('admin.learning.documents.download', $cotc) }}">Review PDF</a>
                                <form method="POST" action="{{ route('admin.learning.documents.release', $cotc) }}">@csrf @method('PATCH')<button class="primary-action">Release to trainee</button></form>
                            @elseif(in_array($cotc->status, ['released', 'downloaded']))
                                <a class="secondary-action" href="{{ route('admin.learning.documents.download', $cotc) }}">Admin copy</a>
                                <button type="button" data-dashboard-dialog-open="reissue-cotc-{{ $record->id }}" class="secondary-action">Reissue</button>
                            @elseif($cotc->status === 'failed')
                                <button type="button" data-dashboard-dialog-open="reissue-cotc-{{ $record->id }}" class="secondary-action">Retry as new version</button>
                            @else
                                <span class="text-sm text-slate-500">Queue status: {{ str($cotc->status)->headline() }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-900">Official Transcript of Record</p><p class="mt-1 text-xs text-slate-500">Admin-only generation and download.</p></div>@if($tor)<span class="dashboard-pill bg-slate-100 text-slate-700 ring-slate-200">{{ str($tor->status)->headline() }}</span>@endif</div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if(!$tor)
                                <form method="POST" action="{{ route('admin.learning.documents.generate', [$record, 'tor']) }}">@csrf<button class="primary-action" @disabled(!$eligibility['eligible'])>Generate TOR</button></form>
                            @elseif(in_array($tor->status, ['generated', 'released', 'downloaded']))
                                <a class="primary-action" href="{{ route('admin.learning.documents.download', $tor) }}">Download TOR</a>
                                <button type="button" data-dashboard-dialog-open="reissue-tor-{{ $record->id }}" class="secondary-action">Reissue</button>
                            @elseif($tor->status === 'failed')
                                <button type="button" data-dashboard-dialog-open="reissue-tor-{{ $record->id }}" class="secondary-action">Retry as new version</button>
                            @else
                                <span class="text-sm text-slate-500">Queue status: {{ str($tor->status)->headline() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </article>

            @foreach(['cotc' => 'COTC', 'tor' => 'TOR'] as $type => $typeLabel)
                <dialog id="reissue-{{ $type }}-{{ $record->id }}" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
                    <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Reissue {{ $typeLabel }}</h2><p class="mt-1 text-xs text-slate-500">The current version will be revoked and retained in the audit history.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
                    <form method="POST" action="{{ route('admin.learning.documents.reissue', [$record, $type]) }}" class="space-y-4 p-6">@csrf<label class="form-label" for="reason-{{ $type }}-{{ $record->id }}">Reason for reissue</label><textarea id="reason-{{ $type }}-{{ $record->id }}" class="form-field min-h-28" name="reason" minlength="10" maxlength="1000" required></textarea><div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action">Queue new version</button></div></form>
                </dialog>
            @endforeach
        @empty
            <div class="dashboard-panel py-14 text-center text-slate-500">No approved trainees match these filters.</div>
        @endforelse
    </div>
    @if($records->hasPages()){{ $records->links() }}@endif

    <section class="space-y-3">
        <div><p class="dashboard-section-kicker">Batch exports</p><h2 class="mt-2 text-xl font-bold text-slate-950">Recent TOR archives</h2></div>
        <div class="dashboard-table-wrap overflow-x-auto"><table class="dashboard-table min-w-[44rem]"><thead><tr><th>Batch</th><th>Status</th><th>Progress</th><th>Expires</th><th class="text-right">File</th></tr></thead><tbody>@forelse($exports as $export)<tr><td>{{ $export->batch?->name }} {{ $export->batch?->year }}</td><td>{{ str($export->status)->headline() }}</td><td>{{ $export->processed_records }} / {{ $export->total_records }}</td><td>{{ $export->expires_at?->format('M j, Y g:i A') ?? '-' }}</td><td class="text-right">@if($export->isDownloadable())<a class="secondary-action" href="{{ route('admin.learning.batch-exports.download', $export) }}">Download ZIP</a>@elseif($export->failure_reason)<span class="text-xs text-red-700">Failed</span>@else<span class="text-xs text-slate-500">Processing</span>@endif</td></tr>@empty<tr><td colspan="5" class="py-10 text-center text-slate-500">No batch exports requested.</td></tr>@endforelse</tbody></table></div>
    </section>

    <dialog id="batch-tor-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Prepare batch TOR ZIP</h2><p class="mt-1 text-xs text-slate-500">The queue worker generates eligible TORs in chunks and creates one expiring archive.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
        <form method="POST" action="{{ route('admin.learning.batch-exports.store') }}" class="space-y-4 p-6">@csrf<div><label class="form-label" for="export-batch">Training batch</label><select id="export-batch" class="form-field" name="training_batch_id" required><option value="">Select batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div><div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action">Queue export</button></div></form>
    </dialog>

    <dialog id="competency-excel-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Export competency Excel</h2><p class="mt-1 text-xs text-slate-500">Download a read-only progress matrix, achievement outcomes, and status guide.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
        <form method="GET" action="{{ route('admin.learning.competency-workbooks.download') }}" class="space-y-4 p-6">
            <div><label class="form-label" for="competency-export-batch">Training batch</label><select id="competency-export-batch" class="form-field" name="batch_id" required><option value="">Select batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
            <div><label class="form-label" for="competency-export-schedule">Class</label><select id="competency-export-schedule" class="form-field" name="schedule"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select></div>
            <div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action"><x-dashboard-icon name="save" class="h-4 w-4" />Download Excel</button></div>
        </form>
    </dialog>
</section>
@endsection
