@extends('admin.layouts.app', ['title' => $label.' Preview | MCARE Admin'])

@section('content')
@php
    $isPdf = $mimeType === 'application/pdf';
    $isImage = str_starts_with($mimeType, 'image/');
    $contentUrl = route('admin.enrollments.documents.content', [$application, $document]);
    $watermark = 'ADMIN REVIEW - '.$application->email.' - '.now()->format('Y-m-d H:i');
@endphp
<div class="mx-auto max-w-7xl space-y-5">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <p class="text-sm text-slate-600">{{ $application->first_name }} {{ $application->last_name }} · {{ $application->email }}</p>
        <a href="{{ route('admin.enrollments.document-review', $application) }}" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-3 text-sm font-bold text-purple-700">Back to document review</a>
    </header>
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Preview only. The private source file remains behind admin authentication and every view is recorded.</div>
    <section class="relative min-h-[75vh] overflow-hidden rounded-xl border border-slate-200 bg-slate-950">
        <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-4 overflow-hidden opacity-[0.12]" aria-hidden="true">@for($i = 0; $i < 8; $i++)<span class="grid -rotate-12 place-items-center whitespace-nowrap text-sm font-black uppercase tracking-widest text-white">{{ $watermark }}</span>@endfor</div>
        @if($isPdf)
            <iframe class="relative z-10 h-[82vh] min-h-[720px] w-full bg-white" src="{{ $contentUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" title="{{ $label }} preview"></iframe>
        @elseif($isImage)
            <div class="relative z-10 flex min-h-[75vh] items-start justify-center overflow-auto p-5"><img src="{{ $contentUrl }}" alt="{{ $label }}" class="h-auto max-w-full select-none object-contain" draggable="false"></div>
        @else
            <div class="relative z-10 grid min-h-[75vh] place-items-center bg-white p-8 text-center"><p class="font-bold text-slate-700">This file type cannot be previewed by the browser. Ask the applicant to upload PDF, JPG, or PNG.</p></div>
        @endif
    </section>
</div>
@endsection
