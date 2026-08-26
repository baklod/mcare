@extends('trainee.layouts.app', ['title' => $module->title.' | MCARE Learning'])

@section('content')
@php
    $previewKind = $module->previewKind();
    $viewerUrl = route('trainee.modules.content', $module);
    $downloadUrl = route('trainee.modules.download', $module);
    $traineeName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
    $watermark = $traineeName.' | '.$application->email.' | TRAINEE #'.$application->id.' | '.now()->format('Y-m-d H:i');
    $supplementaryList = $module->supplementaryList();
    $isCompetent = $progress?->competency_outcome === 'competent';
    $unpassedQuizzes = $quizzes->reject(function ($q) use ($quizAttempts) {
        $attempts = $quizAttempts->get($q->id);
        return $attempts && $attempts->contains('passed', true);
    });
@endphp

<div class="mx-auto max-w-7xl space-y-6" data-protected-module-viewer data-security-event-url="{{ route('trainee.modules.security-event', $module) }}">
    @if ($errors->any() || session('error') || session('alert'))
        <div class="rounded-2xl border-2 border-rose-300 bg-rose-50 p-4 shadow-sm" role="alert">
            <div class="flex items-start gap-3">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-rose-200 text-rose-800 font-bold text-base">⚠️</span>
                <div>
                    <strong class="block text-sm font-bold text-rose-950">Module Submission Notice</strong>
                    <p class="mt-0.5 text-xs font-semibold text-rose-800">{{ $errors->first('action') ?: ($errors->first() ?: (session('error') ?: session('alert'))) }}</p>
                    @if(isset($unpassedQuizzes) && $unpassedQuizzes->isNotEmpty())
                        <a href="#assessments" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-rose-900 underline hover:text-rose-950">
                            ↓ Jump to required {{ \Illuminate\Support\Str::plural('quiz', $unpassedQuizzes->count()) }} below
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <header class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="dashboard-section-kicker">Protected learning viewer</p>
                    <a href="{{ route('trainee.modules.index') }}" class="text-xs font-bold text-purple-700 hover:text-purple-900 flex items-center gap-1">
                        ← Back to Classwork
                    </a>
                    @if($module->module_code)
                        <span class="rounded bg-purple-100 px-2.5 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                            {{ $module->module_code }}
                        </span>
                    @endif
                    <span class="rounded bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                        {{ $module->categoryLabel() }}
                    </span>
                    @if($module->estimated_hours)
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                            ⏱ {{ $module->estimated_hours }} Hours
                        </span>
                    @endif
                    <span class="rounded px-2.5 py-0.5 text-xs font-bold {{ $isCompetent ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300' : ($progress?->status === 'awaiting_evaluation' ? 'bg-purple-100 text-purple-800' : ($progress?->status === 'needs_remediation' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800')) }}">
                        {{ $progress->workflowStatusLabel() }}
                    </span>
                </div>

                <h1 class="font-display text-2xl font-black text-slate-950 sm:text-3xl">{{ $module->title }}</h1>

                @if($module->topic)
                    <p class="text-sm font-semibold text-purple-800">Learning Outcome / Topic: {{ $module->topic }}</p>
                @endif

                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <x-user-avatar :user="$module->trainer" :name="$module->trainer?->name ?? 'MCARE Trainer'" class="grid h-8 w-8 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800" />
                    <span>Trainer: <strong class="text-slate-700">{{ $module->trainer?->name ?? 'MCARE Trainer' }}</strong></span>
                    @if($module->due_at)<span>· Due: <strong class="text-amber-700">{{ $module->due_at->format('M d, Y g:i A') }}</strong></span>@endif
                </div>
            </div>

            <div class="flex flex-col items-end gap-1.5">
                @if(!$progress->isTrainerValidated())
                <form method="POST" action="{{ route('trainee.modules.progress', $module) }}" target="_self" data-module-progress-form data-dashboard-dialog-form data-submit-label="{{ $progress->status === 'awaiting_evaluation' ? 'Reopening...' : 'Submitting...' }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="{{ $progress->status === 'awaiting_evaluation' ? 'reopen' : 'submit' }}">
                    <button type="submit" class="secondary-action text-xs" data-action-button>
                        {{ $progress->status === 'awaiting_evaluation' ? 'Return to In Progress' : ($progress->status === 'needs_remediation' ? 'Resubmit for Evaluation' : 'Mark as Done') }}
                    </button>
                </form>
                @if($unpassedQuizzes->isNotEmpty() && $progress->status !== 'awaiting_evaluation')
                    <a href="#assessments" class="text-[11px] font-semibold text-amber-700 hover:underline">
                        ⚠️ Pass {{ $unpassedQuizzes->count() }} required {{ \Illuminate\Support\Str::plural('quiz', $unpassedQuizzes->count()) }} below to submit
                    </a>
                @endif
                @else
                    <span class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">Trainer validated</span>
                @endif
            </div>
        </div>
    </header>

    @if($progress->isTrainerValidated())
        <!-- EVALUATED & COMPLETED COMPETENCY OVERVIEW CARD -->
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md">
                        <x-dashboard-icon name="award" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-950">Competency Unit Evaluated & Completed</h2>
                            <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold text-emerald-800 ring-1 ring-emerald-300">🟢 Competent</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-600">
                            Validated by <strong>{{ $progress->evaluator?->name ?? 'MCARE Trainer' }}</strong> on <strong>{{ $progress->evaluated_at?->format('M d, Y g:i A') ?? $progress->completed_at?->format('M d, Y') ?? 'Recorded' }}</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600">
                🔒 <strong>Learning Material Closed:</strong> This learning module has been officially evaluated and completed. The lesson document window and file downloads are closed for completed units. Your official competency results and quiz records remain below for reference.
            </div>
        </section>
    @else
        <!-- Security Notice -->
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-900">
            🛡 <strong>Protected Content:</strong> This learning material is watermarked for <strong>{{ $traineeName }}</strong> ({{ $application->email }}). Unauthorized copying or redistribution is strictly monitored.
        </div>

        <!-- PRIMARY LESSON MEDIA / DOCUMENT VIEWER -->
        <section class="protected-module-content relative min-h-[70vh] overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm" data-protected-module-viewer>
            <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-6 overflow-hidden opacity-[0.18]" aria-hidden="true">
                @for($i = 0; $i < 12; $i++)
                    <span class="grid -rotate-12 place-items-center whitespace-nowrap px-3 text-[11px] font-black uppercase tracking-wider text-white">{{ $watermark }}</span>
                @endfor
            </div>

            @if($previewKind === 'video')
                <video class="relative z-10 mx-auto max-h-[80vh] min-h-[65vh] w-full bg-black" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata">
                    <source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">
                    Your browser cannot play this video.
                </video>
            @elseif($previewKind === 'audio')
                <div class="relative z-10 grid min-h-[40vh] place-items-center bg-white p-8">
                    <div class="w-full max-w-2xl text-center">
                        <x-dashboard-icon name="volume-2" class="mx-auto h-10 w-10 text-purple-600" />
                        <p class="mt-4 font-bold text-slate-950">{{ $module->original_file_name }}</p>
                        <audio class="mt-5 w-full" controls preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}"></audio>
                    </div>
                </div>
            @elseif($previewKind === 'image')
                <div class="relative z-10 flex min-h-[70vh] items-start justify-center overflow-auto p-4">
                    <img src="{{ $viewerUrl }}" alt="{{ $module->title }}" class="h-auto max-w-full select-none object-contain" draggable="false">
                </div>
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
                                <span class="text-sm font-semibold">Rendering lesson document...</span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="relative z-10 grid min-h-[40vh] place-items-center bg-white p-8 text-center">
                    <div>
                        <x-dashboard-icon name="file-text" class="mx-auto h-10 w-10 text-purple-600" />
                        <p class="mt-4 font-bold text-slate-950">{{ $module->fileTypeLabel() }}</p>
                        <p class="mt-2 text-sm text-slate-600">This document format is available for download and review.</p>
                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <a href="{{ $downloadUrl }}" class="primary-action">Download Lesson Document</a>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <!-- SUPPLEMENTARY ATTACHMENTS & HANDOUTS -->
        @if(count($supplementaryList) > 0)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                <h2 class="text-base font-bold text-slate-950">Supplementary Handouts & Worksheets ({{ count($supplementaryList) }})</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($supplementaryList as $idx => $supp)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                            <div class="min-w-0 pr-2">
                                <p class="font-bold text-slate-900 truncate">{{ $supp['original_name'] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $supp['human_size'] ?? '' }}</p>
                            </div>
                            <a href="{{ route('trainee.modules.supplementary.download', [$module, $idx]) }}" class="primary-action shrink-0 text-xs py-1.5 px-3">
                                Download
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    <!-- IN-MODULE ASSESSMENTS & GRADING OUTCOME -->
    <section id="assessments" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <h2 class="text-base font-bold text-slate-950">Module Assessments & Performance Outcome</h2>

        <!-- Trainer Evaluation Status (F2F & Overall) -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                <span class="block text-slate-500 font-semibold mb-1">Knowledge / Quiz Score</span>
                <span class="text-base font-bold {{ filled($progress?->quiz_score) ? 'text-purple-900' : 'text-slate-400' }}">
                    {{ filled($progress?->quiz_score) ? number_format((float)$progress->quiz_score, 1).'%' : 'Not yet scored' }}
                </span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                <span class="block text-slate-500 font-semibold mb-1">Practical Demonstration Rating</span>
                <span class="inline-block mt-0.5 rounded px-2 py-0.5 text-xs font-bold {{ $progress?->practical_rating === 'competent' ? 'bg-emerald-100 text-emerald-800' : ($progress?->practical_rating === 'not_yet_competent' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-700') }}">
                    {{ $progress?->practicalRatingLabel() ?? 'Pending F2F Demo' }}
                </span>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                <span class="block text-slate-500 font-semibold mb-1">Module Competency Outcome</span>
                <span class="inline-block mt-0.5 rounded px-2 py-0.5 text-xs font-bold {{ $isCompetent ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-300' : ($progress?->competency_outcome === 'not_yet_competent' ? 'bg-amber-100 text-amber-900' : 'bg-slate-200 text-slate-700') }}">
                    {{ $isCompetent ? '🟢 Competent (Passed)' : ($progress?->competency_outcome === 'not_yet_competent' ? '🟡 For Remediation' : 'In Progress') }}
                </span>
            </div>
        </div>

        @if($progress?->evaluation_remarks)
            <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-3.5 text-xs text-purple-950">
                <strong>Trainer Feedback:</strong> "{{ $progress->evaluation_remarks }}"
                @if($progress->evaluator) <span class="text-purple-700">· {{ $progress->evaluator->name }}</span> @endif
            </div>
        @endif

        <!-- In-Module Online Quizzes -->
        @if($quizzes->isNotEmpty())
            <div class="space-y-3 pt-2">
                <h3 class="text-xs font-bold uppercase text-slate-600">Online Assessments ({{ $quizzes->count() }})</h3>
                @foreach($quizzes as $quiz)
                    @php
                        $attempts = $quizAttempts->get($quiz->id) ?? collect();
                        $bestAttempt = $attempts->sortByDesc('score_percent')->first();
                        $hasPassed = $bestAttempt && $bestAttempt->score_percent >= $quiz->passing_score_percent;
                    @endphp
                    <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between text-xs">
                        <div>
                            <p class="text-sm font-bold text-slate-950">{{ $quiz->title }}</p>
                            <p class="text-slate-500 mt-0.5">{{ $quiz->questions->count() }} Questions · Passing Score: {{ number_format($quiz->passing_score_percent, 0) }}% · Time Limit: {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' mins' : 'Unlimited' }}</p>
                            @if($bestAttempt)
                                <p class="mt-1 font-semibold {{ $hasPassed ? 'text-emerald-700' : 'text-amber-700' }}">
                                    Best Score: {{ number_format($bestAttempt->score_percent, 1) }}% ({{ $hasPassed ? 'Passed' : 'Needs Retake' }})
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($bestAttempt)
                                <a href="{{ route('trainee.quiz-attempts.result', $bestAttempt) }}" class="secondary-action text-xs py-1.5 px-3">
                                    View Score
                                </a>
                            @endif
                            @if(!$progress->isTrainerValidated())
                                <a href="{{ route('trainee.quizzes.show', $quiz) }}" class="primary-action text-xs py-1.5 px-3.5">
                                    {{ $bestAttempt ? 'Retake Quiz' : 'Take Quiz' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <x-classroom-comments :commentable="$module" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
</div>
@endsection
