@extends('trainer.layouts.app', ['title' => $module->title.' | MCARE Module Hub'])

@section('content')
@php
    $previewKind = $module->previewKind();
    $viewerUrl = route('trainer.modules.content', $module);
    $downloadUrl = route('trainer.modules.download', $module);
    $supplementaryList = $module->supplementaryList();
    $isPrivate = filled($module->target_enrollment_application_id);
    $activeTab = request()->query('tab', 'materials');
@endphp

<div class="mx-auto max-w-7xl space-y-6" data-trainer-module-hub>
    <!-- Header -->
    <header class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('trainer.resources') }}" class="text-xs font-bold text-purple-700 hover:text-purple-900 flex items-center gap-1">
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
                        <span class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-600">
                            ⏱ {{ $module->estimated_hours }} Hours
                        </span>
                    @endif
                    <span class="rounded px-2.5 py-0.5 text-xs font-bold {{ $module->delivery_status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($module->delivery_status === 'closed' ? 'bg-amber-100 text-amber-800' : 'bg-stone-200 text-stone-700') }}">
                        {{ $module->deliveryStatusLabel() }}
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-stone-950 sm:text-3xl">{{ $module->title }}</h1>

                @if($module->topic)
                    <p class="text-sm font-semibold text-purple-800">Learning Outcome / Topic: {{ $module->topic }}</p>
                @endif

                <p class="text-xs text-stone-500">
                    Assigned to: <strong class="text-stone-800">{{ $isPrivate ? ($module->targetTrainee?->first_name.' '.$module->targetTrainee?->last_name.' (Private)') : ($module->batch ? $module->batch->name.' '.$module->batch->year : 'Entire Class') }}</strong>
                    · Available: {{ $module->available_at?->format('M d, Y') ?? 'Immediately' }}
                    @if($module->due_at) · Due: {{ $module->due_at->format('M d, Y g:i A') }} @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($module->delivery_status !== 'closed')
                <form method="POST" action="{{ route('trainer.modules.update', $module) }}" data-confirm="{{ $module->delivery_status === 'active' ? 'Close this module to future enrollees? Existing assigned trainees will keep access.' : 'Publish this as the current active module? The previous active module will close to new enrollees.' }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="module_code" value="{{ $module->module_code }}">
                    <input type="hidden" name="competency_category" value="{{ $module->competency_category }}">
                    <input type="hidden" name="title" value="{{ $module->title }}">
                    <input type="hidden" name="description" value="{{ $module->description }}">
                    <input type="hidden" name="topic" value="{{ $module->topic }}">
                    <input type="hidden" name="estimated_hours" value="{{ $module->estimated_hours }}">
                    <input type="hidden" name="position" value="{{ $module->position ?? 0 }}">
                    <input type="hidden" name="audience_type" value="{{ $isPrivate ? 'trainee' : 'batch' }}">
                    <input type="hidden" name="training_batch_id" value="{{ $module->training_batch_id }}">
                    <input type="hidden" name="target_enrollment_application_id" value="{{ $module->target_enrollment_application_id }}">
                    <input type="hidden" name="available_at" value="{{ $module->available_at?->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="due_at" value="{{ $module->due_at?->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="is_published" value="{{ $module->is_published ? 0 : 1 }}">
                    <input type="hidden" name="_return_to_module" value="1">
                    <button class="secondary-action text-xs">
                        {{ $module->delivery_status === 'active' ? 'Close to New Enrollees' : 'Publish as Active Module' }}
                    </button>
                </form>
                @else
                    <span class="max-w-xs text-xs leading-5 text-amber-800">Historical delivery: visible only to trainees who were assigned before it closed.</span>
                @endif
            </div>
        </div>

        <!-- Section Navigation Tabs -->
        <nav class="mt-6 flex flex-wrap gap-2 border-t border-stone-100 pt-4" aria-label="Module Hub sections">
            <a href="#materials" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $activeTab === 'materials' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                📖 1. Learning Materials & Files ({{ count($supplementaryList) + 1 }})
            </a>
            <a href="#assessments" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $activeTab === 'assessments' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                📝 2. Assessments ({{ $quizzes->count() }})
            </a>
            <a href="#evaluations" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $activeTab === 'evaluations' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                📊 3. Learner Grades & Competency Matrix ({{ $trainees->count() }})
            </a>
        </nav>
    </header>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- SECTION 1: LEARNING MATERIALS & PREVIEW -->
    <section id="materials" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Primary Lesson Material</h2>
                    <p class="text-xs text-stone-500">{{ $module->original_file_name }} · {{ $module->fileTypeLabel() }} ({{ number_format(($module->file_size ?? 0) / 1048576, 2) }} MB)</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="secondary-action text-xs">Open in New Tab</a>
                    <a href="{{ $downloadUrl }}" class="primary-action text-xs">Download File</a>
                </div>
            </div>

            <!-- Media Preview Embed -->
            <div class="min-h-[50vh] overflow-hidden rounded-xl border border-stone-200 bg-stone-950">
                @if($previewKind === 'video')
                    <video class="mx-auto max-h-[75vh] w-full" controls controlsList="nodownload noremoteplayback" disablePictureInPicture>
                        <source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">
                    </video>
                @elseif($previewKind === 'audio')
                    <div class="grid min-h-[35vh] place-items-center bg-white p-8">
                        <div class="w-full max-w-2xl text-center">
                            <x-dashboard-icon name="volume-2" class="mx-auto h-10 w-10 text-purple-600" />
                            <p class="mt-4 font-bold text-stone-950">{{ $module->original_file_name }}</p>
                            <audio class="mt-5 w-full" controls preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}"></audio>
                        </div>
                    </div>
                @elseif($previewKind === 'image')
                    <div class="flex min-h-[50vh] items-start justify-center overflow-auto p-4">
                        <img src="{{ $viewerUrl }}" alt="{{ $module->title }}" class="h-auto max-w-full object-contain">
                    </div>
                @elseif($previewKind === 'pdf')
                    <iframe class="h-[75vh] min-h-[600px] w-full bg-white" src="{{ $viewerUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH" title="{{ $module->title }} PDF preview"></iframe>
                @else
                    <div class="grid min-h-[35vh] place-items-center bg-white p-8 text-center text-stone-700">
                        <div>
                            <x-dashboard-icon name="file-text" class="mx-auto h-10 w-10 text-purple-600" />
                            <p class="mt-4 font-bold">{{ $module->fileTypeLabel() }}</p>
                            <p class="mt-2 text-sm text-stone-500">Document available for download or external application viewing.</p>
                            <div class="mt-5 flex justify-center gap-2">
                                <a href="{{ $downloadUrl }}" class="primary-action text-xs">Download Attachment</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Supplementary Files List -->
            @if(count($supplementaryList) > 0)
                <div class="border-t border-stone-100 pt-4">
                    <h3 class="text-sm font-bold text-stone-900 mb-2">Supplementary Worksheets & Handouts ({{ count($supplementaryList) }})</h3>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($supplementaryList as $idx => $supp)
                            <div class="flex items-center justify-between rounded-xl border border-stone-200 bg-stone-50 p-3 text-xs">
                                <div class="min-w-0 pr-2">
                                    <p class="font-bold text-stone-900 truncate">{{ $supp['original_name'] }}</p>
                                    <p class="text-[11px] text-stone-500">{{ $supp['human_size'] ?? '' }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <a href="{{ route('trainer.modules.supplementary.download', [$module, $idx]) }}" class="secondary-action text-xs py-1 px-2.5">
                                        Download
                                    </a>
                                    <form method="POST" action="{{ route('trainer.modules.supplementary.destroy', [$module, $idx]) }}" onsubmit="return confirm('Remove this supplementary file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-bold text-rose-700 hover:bg-rose-50">Remove</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Add Supplementary Files Form -->
            <details class="rounded-xl border border-dashed border-stone-300 bg-stone-50/50 p-3 text-xs">
                <summary class="cursor-pointer font-bold text-purple-700 hover:text-purple-900 select-none">
                    + Attach More Supplementary Handouts / Worksheets
                </summary>
                <form method="POST" action="{{ route('trainer.modules.update', $module) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="module_code" value="{{ $module->module_code }}">
                    <input type="hidden" name="competency_category" value="{{ $module->competency_category }}">
                    <input type="hidden" name="title" value="{{ $module->title }}">
                    <input type="hidden" name="description" value="{{ $module->description }}">
                    <input type="hidden" name="topic" value="{{ $module->topic }}">
                    <input type="hidden" name="audience_type" value="{{ $isPrivate ? 'trainee' : 'batch' }}">
                    <input type="hidden" name="training_batch_id" value="{{ $module->training_batch_id }}">
                    <input type="hidden" name="target_enrollment_application_id" value="{{ $module->target_enrollment_application_id }}">
                    <input type="hidden" name="estimated_hours" value="{{ $module->estimated_hours }}">
                    <input type="hidden" name="position" value="{{ $module->position ?? 0 }}">
                    <input type="hidden" name="available_at" value="{{ $module->available_at?->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="due_at" value="{{ $module->due_at?->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="_return_to_module" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 mb-1">Select files to append</label>
                        <input name="supplementary_files[]" type="file" multiple accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field">
                        <p class="mt-1 text-[11px] text-stone-500">{{ count($supplementaryList) }} of {{ \App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES }} slots used; {{ number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024) }} MB maximum per file.</p>
                    </div>
                    <button type="submit" class="primary-action text-xs">Upload Attachments</button>
                </form>
            </details>
        </div>
    </section>

    <!-- SECTION 2: MODULE ASSESSMENTS -->
    <section id="assessments" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Module Assessments</h2>
                    <p class="text-xs text-stone-500">Quizzes attached directly to this module for knowledge evaluation.</p>
                </div>
                <button type="button" class="primary-action text-xs" data-dashboard-dialog-open="module-quiz-dialog">Create quiz</button>
            </div>

            <div class="space-y-3">
                @forelse($quizzes as $quiz)
                    <div class="flex flex-col gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-stone-950">{{ $quiz->title }}</h3>
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $quiz->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700' }}">
                                    {{ $quiz->is_published ? 'Active' : 'Draft' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-stone-600">{{ $quiz->questions->count() }} Questions · Passing Score: {{ number_format($quiz->passing_score_percent, 0) }}% · Time Limit: {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' mins' : 'Unlimited' }}</p>
                            @if($quiz->instructions)<p class="mt-1 text-xs text-stone-500 italic">{{ str($quiz->instructions)->limit(120) }}</p>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="secondary-action text-xs py-1.5 px-3">
                                Edit Questions
                            </a>
                            <a href="{{ route('trainer.quizzes.results', $quiz) }}" class="primary-action text-xs py-1.5 px-3">
                                View Submissions
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-6 text-center text-xs text-stone-500">
                        No quiz has been created for this module yet.
                    </div>
                @endforelse
            </div>

            <dialog id="module-quiz-dialog" data-dashboard-dialog data-auto-open="{{ old('_composer') === 'module-quiz' && $errors->any() ? 'true' : 'false' }}" class="lms-workflow-dialog is-compact" aria-labelledby="module-quiz-title">
                <div class="lms-dialog-header">
                    <div><p class="lms-eyebrow">{{ $module->title }}</p><h2 id="module-quiz-title">Create module quiz</h2><p>Create the draft, then add the answer key and questions.</p></div>
                    <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close quiz creator"><x-dashboard-icon name="xmark" /></button>
                </div>
                <form method="POST" action="{{ route('trainer.modules.quizzes.store', $module) }}" class="grid gap-3 p-6 sm:grid-cols-2" data-dashboard-dialog-form data-submit-label="Creating quiz...">
                    @csrf
                    <input type="hidden" name="_composer" value="module-quiz">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Assessment / Quiz Title</label>
                        <input name="title" required maxlength="160" placeholder="e.g. {{ $module->title }} - Mastery Assessment" class="form-field">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Instructions</label>
                        <textarea name="instructions" rows="2" placeholder="Instructions for trainees before starting the quiz..." class="form-field"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-purple-900 mb-1">Time Limit (Minutes)</label>
                        <input type="number" name="time_limit_minutes" value="20" min="1" max="180" class="form-field">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-purple-900 mb-1">Passing Score (%)</label>
                        <input type="number" name="passing_score_percent" value="75" min="50" max="100" class="form-field">
                    </div>
                    <p class="sm:col-span-2 text-xs text-stone-600">The assessment starts as a draft. Add the answer key and questions before publishing it to trainees.</p>
                    <div class="flex flex-col-reverse gap-2 border-t border-stone-200 pt-4 sm:col-span-2 sm:flex-row sm:justify-end">
                        <button type="button" data-dashboard-dialog-close class="secondary-action text-xs">Cancel</button>
                        <button type="submit" data-action-button class="primary-action text-xs">Create & Add Questions</button>
                    </div>
                </form>
            </dialog>
        </div>
    </section>

    <!-- SECTION 3: LEARNER GRADES & EVALUATION MATRIX -->
    <section id="evaluations" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Learner Evaluation & Grading Matrix</h2>
                    <p class="text-xs text-stone-500">Record practical demonstration ratings, written test scores, and award competency outcomes.</p>
                </div>
                <div class="text-xs text-stone-600">
                    Batch: <strong>{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'Current Roster' }}</strong>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="dashboard-table min-w-[58rem]">
                    <thead>
                        <tr>
                            <th>Learner</th>
                            <th>Material Progress</th>
                            <th>Quiz Score</th>
                            <th>Practical Demo / Activity</th>
                            <th>Competency Outcome</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trainees as $trainee)
                            @php
                                $progress = $progressByApp->get($trainee->id);
                                $isCompetent = $progress?->competency_outcome === 'competent';
                                $isNyc = $progress?->competency_outcome === 'not_yet_competent';
                            @endphp
                            <tr class="align-middle">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar
                                            :user="$trainee->user"
                                            :name="trim($trainee->first_name.' '.$trainee->last_name)"
                                            class="grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800"
                                        />
                                        <div class="min-w-0">
                                            <p class="font-bold text-stone-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                            <p class="truncate text-[11px] text-stone-500">{{ $trainee->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $progress?->isTrainerValidated() ? 'text-emerald-700' : ($progress?->status === 'awaiting_evaluation' ? 'text-purple-700' : 'text-stone-600') }}">
                                        {{ $progress?->workflowStatusLabel() ?? 'Not assigned' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-bold {{ filled($progress?->quiz_score) ? 'text-purple-900' : 'text-stone-400' }}">
                                        {{ filled($progress?->quiz_score) ? number_format((float)$progress->quiz_score, 1).'%' : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="rounded px-2 py-0.5 text-xs font-bold {{ $progress?->practical_rating === 'competent' ? 'bg-emerald-100 text-emerald-800' : ($progress?->practical_rating === 'not_yet_competent' ? 'bg-rose-100 text-rose-800' : 'bg-stone-100 text-stone-600') }}">
                                        {{ $progress?->practicalRatingLabel() ?? 'Pending' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold {{ $isCompetent ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-300' : ($isNyc ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-300' : 'bg-stone-100 text-stone-700') }}">
                                        {{ $isCompetent ? '🟢 Competent' : ($isNyc ? '🟡 For Remediation' : 'In Progress') }}
                                    </span>
                                    @if($progress?->evaluation_remarks)
                                        <p class="mt-1 text-[10px] text-stone-500 italic max-w-xs truncate">"{{ $progress->evaluation_remarks }}"</p>
                                    @endif
                                </td>
                                <td>
                                    @if($progress?->status === 'locked')
                                        <span class="inline-flex max-w-44 rounded-lg bg-stone-100 px-3 py-2 text-xs font-semibold leading-5 text-stone-600">Evaluation opens after this trainee becomes competent in the previous assigned module.</span>
                                    @else
                                    <details class="relative">
                                        <summary class="cursor-pointer rounded-lg bg-stone-100 hover:bg-stone-200 px-3 py-1.5 text-xs font-bold text-stone-800 select-none">
                                            {{ $progress?->status === 'awaiting_evaluation' ? 'Evaluate Submission' : 'Review' }}
                                        </summary>
                                        <div class="absolute right-0 z-20 mt-2 w-80 rounded-2xl border border-stone-200 bg-white p-4 shadow-xl text-xs space-y-3">
                                            <div class="font-bold text-stone-900 border-b border-stone-100 pb-2">
                                                Grade: {{ $trainee->first_name }} {{ $trainee->last_name }}
                                            </div>
                                            @if(!$progress?->submitted_at)
                                                <p class="rounded-lg bg-amber-50 p-2 text-amber-800">Final Competent/NYC outcomes stay blocked until the trainee clicks Mark as Done.</p>
                                            @endif
                                            <form method="POST" action="{{ route('trainer.modules.evaluate', $module) }}" class="space-y-3">
                                                @csrf
                                                <input type="hidden" name="enrollment_application_id" value="{{ $trainee->id }}">

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Knowledge / Quiz Score (%)</label>
                                                    <input type="number" step="0.1" name="quiz_score" value="{{ old('quiz_score', $progress?->quiz_score) }}" min="0" max="100" placeholder="e.g. 85.0" class="form-field">
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Practical Demonstration Rating</label>
                                                    <select name="practical_rating" class="form-field">
                                                        <option value="competent" @selected($progress?->practical_rating === 'competent')>Competent (C) - Passed</option>
                                                        <option value="not_yet_competent" @selected($progress?->practical_rating === 'not_yet_competent')>Not Yet Competent (NYC)</option>
                                                        <option value="pending" @selected(!$progress || $progress->practical_rating === 'pending')>Pending Evaluation</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Overall Module Outcome</label>
                                                    <select name="competency_outcome" class="form-field" required>
                                                        <option value="competent" @selected($progress?->competency_outcome === 'competent')>Competent (Passed & Sync to TOR)</option>
                                                        <option value="not_yet_competent" @selected($progress?->competency_outcome === 'not_yet_competent')>Not Yet Competent (For Remediation)</option>
                                                        <option value="in_progress" @selected(!$progress || $progress->competency_outcome === 'in_progress')>In Progress</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Trainer Remarks</label>
                                                    <textarea name="evaluation_remarks" rows="2" placeholder="F2F demo feedback..." class="form-field">{{ $progress?->evaluation_remarks }}</textarea>
                                                </div>

                                                <button type="submit" class="primary-action w-full justify-center text-xs py-2">
                                                    Save Evaluation
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-stone-500">No approved trainees assigned to this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <x-classroom-comments :commentable="$module" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
</div>
@endsection
