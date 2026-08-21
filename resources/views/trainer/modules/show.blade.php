@extends('trainer.layouts.app', ['title' => $module->title.' | MCARE Trainer'])

@section('content')
@php
    $previewKind = $module->previewKind();
    $viewerUrl = route('trainer.modules.content', $module);
    $downloadUrl = route('trainer.modules.download', $module);
@endphp
<div class="mx-auto max-w-7xl space-y-5">
    <header class="flex flex-col gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Trainer preview</p>
                @if($module->module_code)
                    <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                        {{ $module->module_code }}
                    </span>
                @endif
                @if($module->topic)
                    <span class="text-xs font-semibold text-stone-500">· {{ $module->topic }}</span>
                @endif
            </div>
            <h1 class="mt-2 text-3xl font-bold text-stone-950">{{ $module->title }}</h1>
            <p class="mt-2 text-stone-600">{{ $module->targetTrainee ? 'Private to '.$module->targetTrainee->first_name.' '.$module->targetTrainee->last_name : 'Visible to the entire batch' }}</p>
        </div>
        <a href="{{ route('trainer.resources') }}" class="border border-stone-300 bg-white px-4 py-2 font-bold">Back to resources</a>
    </header>
    <section class="min-h-[75vh] overflow-hidden border border-stone-200 bg-stone-950">
        @if($previewKind === 'video')<video class="mx-auto max-h-[82vh] min-h-[70vh] w-full" controls controlsList="nodownload noremoteplayback" disablePictureInPicture><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}"></video>
        @elseif($previewKind === 'audio')<div class="grid min-h-[45vh] place-items-center bg-white p-8"><div class="w-full max-w-2xl text-center"><x-dashboard-icon name="volume-2" class="mx-auto h-10 w-10 text-violet-600" /><p class="mt-4 font-bold text-stone-950">{{ $module->original_file_name }}</p><audio class="mt-5 w-full" controls preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}"></audio></div></div>
        @elseif($previewKind === 'image')<div class="flex min-h-[75vh] items-start justify-center overflow-auto p-4"><img src="{{ $viewerUrl }}" alt="{{ $module->title }}" class="h-auto max-w-full object-contain"></div>
        @elseif($previewKind === 'pdf')<iframe class="h-[82vh] min-h-[720px] w-full bg-white" src="{{ $viewerUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" title="{{ $module->title }} PDF preview"></iframe>
        @else<div class="grid min-h-[45vh] place-items-center bg-white p-8 text-center text-stone-700"><div><x-dashboard-icon name="file-text" class="mx-auto h-10 w-10 text-violet-600" /><p class="mt-4 font-bold">{{ $module->fileTypeLabel() }}</p><p class="mt-2 text-sm">Office files use a reliable open/download fallback.</p><div class="mt-5 flex justify-center gap-2"><a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="border border-stone-300 bg-white px-4 py-2 font-bold">Open file</a><a href="{{ $downloadUrl }}" class="bg-violet-700 px-4 py-2 font-bold text-white">Download</a></div></div></div>@endif
    </section>
    <section class="border border-stone-200 bg-white p-5"><h2 class="text-lg font-bold">Learner progress</h2><div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">@forelse($module->progressRecords as $record)<div class="bg-stone-50 p-4"><p class="font-bold">{{ $record->application?->first_name }} {{ $record->application?->last_name }}</p><p class="mt-1 text-sm text-stone-600">{{ str($record->status)->headline() }} · {{ $record->progress_percent }}%</p></div>@empty<p class="text-sm text-stone-600">No trainee has opened this module yet.</p>@endforelse</div></section>
</div>
@endsection
