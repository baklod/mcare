@php
    $label = $type === \App\Models\OfficialDocument::TYPE_COTC ? 'COTC' : 'TOR';
    $isCotc = $type === \App\Models\OfficialDocument::TYPE_COTC;
@endphp

<div class="space-y-2">
    @if($document)
        <span class="dashboard-pill bg-slate-100 text-slate-700 ring-slate-200">{{ str($document->status)->headline() }}</span>
    @endif

    <div class="flex flex-wrap gap-2">
        @if(!$document)
            <form method="POST" action="{{ route('admin.learning.documents.generate', [$record, $type]) }}">@csrf<button class="primary-action" @disabled(!$eligibility['eligible'])>Generate {{ $label }}</button></form>
        @elseif($document->status === 'queued')
            <form method="POST" action="{{ route('admin.learning.documents.generate', [$record, $type]) }}">@csrf<button class="primary-action">Generate {{ $label }} now</button></form>
        @elseif($isCotc && $document->status === 'generated')
            <a class="secondary-action" href="{{ route('admin.learning.documents.preview', $document) }}">Review PDF</a>
            <form method="POST" action="{{ route('admin.learning.documents.release', $document) }}">@csrf @method('PATCH')<button class="primary-action">Release to trainee</button></form>
        @elseif($isCotc && in_array($document->status, ['released', 'downloaded']))
            <a class="secondary-action" href="{{ route('admin.learning.documents.download', $document) }}">Admin copy</a>
            <button type="button" data-dashboard-dialog-open="reissue-{{ $type }}-{{ $record->id }}" class="secondary-action">Reissue</button>
        @elseif(! $isCotc && in_array($document->status, ['generated', 'released', 'downloaded']))
            <a class="secondary-action" href="{{ route('admin.learning.documents.preview', $document) }}">Preview TOR</a>
            <a class="primary-action" href="{{ route('admin.learning.documents.download', $document) }}">Download TOR</a>
            <button type="button" data-dashboard-dialog-open="reissue-{{ $type }}-{{ $record->id }}" class="secondary-action">Reissue</button>
        @elseif($document->status === 'failed')
            <button type="button" data-dashboard-dialog-open="reissue-{{ $type }}-{{ $record->id }}" class="secondary-action">Retry as new version</button>
        @else
            <span class="text-sm text-slate-500">Queue status: {{ str($document->status)->headline() }}</span>
        @endif
    </div>
</div>
