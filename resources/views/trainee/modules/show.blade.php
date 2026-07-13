@extends('trainee.layouts.app', ['title' => $module->title.' | MCARE Learning'])

@section('content')
@php
    $mime = strtolower((string) $module->mime_type);
    $isPdf = $mime === 'application/pdf' || str_ends_with(strtolower($module->original_file_name), '.pdf');
    $isImage = str_starts_with($mime, 'image/');
    $isVideo = str_starts_with($mime, 'video/');
    $viewerUrl = route('trainee.modules.content', $module);
    $traineeName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
    $watermark = $traineeName.' | '.$application->email.' | TRAINEE #'.$application->id.' | VIEWED '.now()->format('Y-m-d H:i');
@endphp

<div class="mx-auto max-w-7xl space-y-5">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="dashboard-section-kicker">Protected learning viewer</p><h1 class="mt-2 font-display text-3xl font-black text-slate-950">{{ $module->title }}</h1><p class="mt-2 text-sm text-slate-600">{{ $module->description }}</p></div>
        <a href="{{ route('trainee.dashboard').'#modules' }}" class="secondary-action">Back to modules</a>
    </header>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">This material is provided for in-system viewing. Access and progress events are logged. Browser controls can be reduced, but screenshots or external capture cannot be fully prevented.</div>

    <section class="relative min-h-[75vh] overflow-hidden rounded-xl border border-slate-200 bg-slate-950 shadow-sm">
        <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-6 overflow-hidden opacity-[0.18]" aria-hidden="true">@for($i = 0; $i < 12; $i++)<span class="grid -rotate-12 place-items-center whitespace-nowrap px-3 text-[11px] font-black uppercase tracking-wider text-white">{{ $watermark }}</span>@endfor</div>
        @if($isVideo)
            <video class="relative z-10 mx-auto max-h-[82vh] min-h-[70vh] w-full bg-black" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">Your browser cannot play this video.</video>
        @elseif($isImage)
            <div class="relative z-10 flex min-h-[75vh] items-start justify-center overflow-auto p-4"><img src="{{ $viewerUrl }}" alt="{{ $module->title }}" class="h-auto max-w-full select-none object-contain" draggable="false"></div>
        @elseif($isPdf)
            <iframe class="relative z-10 h-[82vh] min-h-[720px] w-full bg-white" src="{{ $viewerUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" title="{{ $module->title }} PDF viewer"></iframe>
        @else
            <div class="relative z-10 grid min-h-[45vh] place-items-center bg-white p-8 text-center"><div><p class="font-bold text-slate-950">Inline preview is unavailable for this older file type.</p><p class="mt-2 text-sm text-slate-600">Ask your trainer to replace it with PDF, image, MP4, or WEBM.</p></div></div>
        @endif
    </section>

    <div class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-xs font-bold text-purple-900">Watermark identity: {{ $traineeName }} | {{ $application->email }} | Trainee #{{ $application->id }}</div>

    <section class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="font-bold text-slate-950">Progress: {{ str($progress->status)->headline() }}</p><p class="mt-1 text-sm text-slate-500">Last viewed {{ $progress->last_viewed_at?->format('M d, Y g:i A') }}</p></div>
        <form method="POST" action="{{ route('trainee.modules.progress', $module) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="{{ $progress->status === 'completed' ? 'reopen' : 'complete' }}"><button class="primary-action">{{ $progress->status === 'completed' ? 'Return to in progress' : 'Mark module complete' }}</button></form>
    </section>
</div>
@endsection
