@extends('trainee.layouts.app', ['title' => $module->title.' | MCARE Learning'])

@section('content')
@php
    $previewKind = $module->previewKind();
    $viewerUrl = route('trainee.modules.content', $module);
    $downloadUrl = route('trainee.modules.download', $module);
    $traineeName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
    $watermark = $traineeName.' | '.$application->email.' | TRAINEE #'.$application->id.' | VIEWED '.now()->format('Y-m-d H:i');
@endphp

<div class="mx-auto max-w-7xl space-y-5" data-protected-module-viewer data-security-event-url="{{ route('trainee.modules.security-event', $module) }}">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <p class="dashboard-section-kicker">Protected learning viewer</p>
                @if($module->module_code)
                    <span class="rounded bg-purple-100 px-2.5 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                        {{ $module->module_code }}
                    </span>
                @endif
                @if($module->topic)
                    <span class="text-xs font-semibold text-slate-500">· {{ $module->topic }}</span>
                @endif
            </div>
            <h1 class="mt-2 font-display text-3xl font-black text-slate-950">{{ $module->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $module->description }}</p>
        </div>
        <a href="{{ route('trainee.modules.index') }}" class="secondary-action">Back to modules</a>
    </header>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">This material is provided for in-system viewing. Right-click, save shortcuts, and ordinary browser printing are restricted and attempted actions are logged. Screenshots or external capture cannot be fully prevented.</div>
    <div class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="status" data-protected-viewer-notice></div>

    <section class="protected-module-content relative min-h-[75vh] overflow-hidden rounded-xl border border-slate-200 bg-slate-950 shadow-sm">
        <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-6 overflow-hidden opacity-[0.18]" aria-hidden="true">@for($i = 0; $i < 12; $i++)<span class="grid -rotate-12 place-items-center whitespace-nowrap px-3 text-[11px] font-black uppercase tracking-wider text-white">{{ $watermark }}</span>@endfor</div>
        @if($previewKind === 'video')
            <video class="relative z-10 mx-auto max-h-[82vh] min-h-[70vh] w-full bg-black" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">Your browser cannot play this video.</video>
        @elseif($previewKind === 'audio')
            <div class="relative z-10 grid min-h-[45vh] place-items-center bg-white p-8"><div class="w-full max-w-2xl text-center"><x-dashboard-icon name="volume-2" class="mx-auto h-10 w-10 text-purple-600" /><p class="mt-4 font-bold text-slate-950">{{ $module->original_file_name }}</p><audio class="mt-5 w-full" controls preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">Your browser cannot play this audio.</audio></div></div>
        @elseif($previewKind === 'image')
            <div class="relative z-10 flex min-h-[75vh] items-start justify-center overflow-auto p-4"><img src="{{ $viewerUrl }}" alt="{{ $module->title }}" class="h-auto max-w-full select-none object-contain" draggable="false"></div>
        @elseif($previewKind === 'pdf')
            <div class="relative z-10 flex min-h-[75vh] flex-col bg-slate-900" data-pdf-canvas-viewer data-pdf-url="{{ $viewerUrl }}" data-watermark="{{ $watermark }}">
                <div class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-3 border-b border-slate-700 bg-slate-900/95 px-4 py-2.5 text-xs text-white backdrop-blur select-none">
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded bg-slate-800 px-3 py-1.5 font-bold hover:bg-slate-700 disabled:opacity-40" data-pdf-prev disabled title="Previous Page">
                            &larr; Prev
                        </button>
                        <span class="font-medium text-slate-300">
                            Page <span data-pdf-current-page>1</span> of <span data-pdf-total-pages>-</span>
                        </span>
                        <button type="button" class="rounded bg-slate-800 px-3 py-1.5 font-bold hover:bg-slate-700 disabled:opacity-40" data-pdf-next disabled title="Next Page">
                            Next &rarr;
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold hover:bg-slate-700" data-pdf-zoom-out title="Zoom Out">
                            &minus;
                        </button>
                        <span class="min-w-[3rem] text-center font-medium text-slate-300" data-pdf-zoom-level>125%</span>
                        <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold hover:bg-slate-700" data-pdf-zoom-in title="Zoom In">
                            +
                        </button>
                        <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold text-slate-300 hover:bg-slate-700" data-pdf-fit-width title="Fit Width">
                            Fit Width
                        </button>
                    </div>
                </div>

                <div class="relative flex flex-1 items-start justify-center overflow-auto p-4 select-none" data-pdf-canvas-container>
                    <div class="relative inline-block shadow-2xl" data-pdf-page-wrapper>
                        <canvas class="block max-w-full bg-white shadow-md" data-pdf-canvas></canvas>
                        <div class="pointer-events-none absolute inset-0 z-10 grid grid-cols-2 grid-rows-6 overflow-hidden select-none" aria-hidden="true">
                            @for($i = 0; $i < 12; $i++)
                                <span class="grid -rotate-12 place-items-center whitespace-nowrap px-3 text-[11px] font-black uppercase tracking-wider text-slate-900/30">{{ $watermark }}</span>
                            @endfor
                        </div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-950/80 text-white" data-pdf-loading>
                        <div class="flex items-center gap-3">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-purple-400 border-t-transparent"></span>
                            <span class="text-sm font-semibold">Rendering learning material...</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="relative z-10 grid min-h-[45vh] place-items-center bg-white p-8 text-center"><div><x-dashboard-icon name="file-text" class="mx-auto h-10 w-10 text-purple-600" /><p class="mt-4 font-bold text-slate-950">{{ $module->fileTypeLabel() }}</p><p class="mt-2 text-sm text-slate-600">This format cannot be previewed reliably in every browser.</p><div class="mt-5 flex flex-wrap justify-center gap-2"><a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="secondary-action">Open file</a><a href="{{ $downloadUrl }}" class="primary-action">Download</a></div></div></div>
        @endif
    </section>

    <div class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-xs font-bold text-purple-900">Watermark identity: {{ $traineeName }} | {{ $application->email }} | Trainee #{{ $application->id }}</div>

    <section class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="font-bold text-slate-950">Progress: {{ str($progress->status)->headline() }}</p><p class="mt-1 text-sm text-slate-500">Last viewed {{ $progress->last_viewed_at?->format('M d, Y g:i A') }}</p></div>
        <form method="POST" action="{{ route('trainee.modules.progress', $module) }}" data-module-progress-form>@csrf @method('PATCH')<input type="hidden" name="action" value="{{ $progress->status === 'completed' ? 'reopen' : 'complete' }}"><button type="submit" class="primary-action" data-action-button>{{ $progress->status === 'completed' ? 'Return to in progress' : 'Mark module complete' }}</button></form>
    </section>
</div>
@endsection
