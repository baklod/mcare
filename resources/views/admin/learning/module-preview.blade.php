@extends('admin.layouts.app', ['title' => $module->title.' Preview | MCARE Admin'])

@section('content')
@php
    $viewerUrl = route('admin.learning.modules.content', $module);
    $downloadUrl = route('admin.learning.modules.download', $module);
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <p class="dashboard-section-kicker">Admin module preview</p>
            <h1 class="dashboard-section-title mt-2 truncate text-3xl">{{ $module->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ $module->original_file_name }} · {{ $module->fileTypeLabel() }} · {{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General module' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.learning.modules') }}" class="secondary-action">Back to modules</a>
            <a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="secondary-action">Open in new tab</a>
            <a href="{{ $downloadUrl }}" class="primary-action">Download file</a>
        </div>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Module file preview">
        <x-module-file-preview :module="$module" :viewer-url="$viewerUrl" />
    </section>
</div>
@endsection
