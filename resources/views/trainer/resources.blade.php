@extends('trainer.layouts.app', ['title' => 'Classwork | MCARE Trainer'])

@section('content')
@php
    $activeBatch = $assignedBatch ?? $batches->firstWhere('is_active', true);
    $publishedCount = collect($modules)->where('is_published', true)->count();
    $catalogUnits = $catalogUnits ?? \App\Support\CaregivingNcIiCatalog::units();
@endphp

<div class="lms-page" data-lms-classwork>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">MCARE Classroom</p>
            <h1>Classwork library</h1>
            <p>Manage Caregiving NC II learning modules, attached lesson materials, in-module quizzes, and learner competency evaluations.</p>
        </div>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainer classroom sections">
        @if(\Illuminate\Support\Facades\Route::has('trainer.stream'))
            <a href="{{ route('trainer.stream') }}">Stream</a>
        @endif
        <a href="{{ route('trainer.resources') }}" class="is-active" aria-current="page">Classwork</a>
        <a href="{{ route('trainer.trainees') }}">People</a>
    </nav>

    @if($errors->any())
        <div class="lms-inline-alert is-danger" role="alert">
            <strong>The classwork item was not saved.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <details class="lms-composer" data-module-composer @if($errors->any()) open @endif>
        <summary class="lms-disclosure-summary">
            <span class="lms-disclosure-icon" aria-hidden="true">+</span>
            <span>
                <strong>Create Learning Module / Caregiving Core Unit</strong>
                <small>Caregiving NC II core units, sub-topics, PDF/video lessons, supplementary worksheets, and in-module assessments</small>
            </span>
            <span class="lms-summary-action">Open composer</span>
        </summary>

        <form method="POST" action="{{ route('trainer.modules.store') }}" enctype="multipart/form-data" class="lms-form-grid lms-composer-form">
            @csrf

            <!-- Caregiving NC II Core Preset Selector -->
            <div class="lms-field lms-field-wide">
                <label for="module-preset-select" class="font-bold text-purple-900">Caregiving NC II Course Module Preset</label>
                <select id="module-preset-select" class="form-field border-purple-300 focus:border-purple-600" data-module-preset-select>
                    <option value="">-- Choose from Caregiving NC II Core Modules or enter custom --</option>
                    <optgroup label="11 Core Competencies (TESDA TOR)">
                        @foreach($catalogUnits as $unit)
                            @if(($unit['category'] ?? '') === 'core')
                                <option value="{{ $unit['code'] }}" data-code="{{ $unit['code'] }}" data-category="core" data-title="{{ $unit['title'] }}" data-hours="{{ $unit['hours'] ?? 40 }}" data-outcomes="{{ json_encode($unit['outcomes']) }}">
                                    [{{ $unit['code'] }}] {{ $unit['title'] }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                    <optgroup label="Common Competencies">
                        @foreach($catalogUnits as $unit)
                            @if(($unit['category'] ?? '') === 'common')
                                <option value="{{ $unit['code'] }}" data-code="{{ $unit['code'] }}" data-category="common" data-title="{{ $unit['title'] }}" data-hours="{{ $unit['hours'] ?? 20 }}" data-outcomes="{{ json_encode($unit['outcomes']) }}">
                                    [{{ $unit['code'] }}] {{ $unit['title'] }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                    <optgroup label="Basic Competencies">
                        @foreach($catalogUnits as $unit)
                            @if(($unit['category'] ?? '') === 'basic')
                                <option value="{{ $unit['code'] }}" data-code="{{ $unit['code'] }}" data-category="basic" data-title="{{ $unit['title'] }}" data-hours="{{ $unit['hours'] ?? 18 }}" data-outcomes="{{ json_encode($unit['outcomes']) }}">
                                    [{{ $unit['code'] }}] {{ $unit['title'] }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                </select>
                <small class="text-xs text-slate-500">Choosing a course module preset automatically sets the official module code, title, competency category, nominal hours, and suggested subtopics.</small>
            </div>

            <div class="lms-field">
                <label for="module-code">Module Code</label>
                <input id="module-code" name="module_code" value="{{ old('module_code') }}" maxlength="50" class="font-mono font-bold" placeholder="e.g. HCS323301">
                @error('module_code')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-category">Competency Category</label>
                <select id="module-category" name="competency_category" class="form-field">
                    <option value="core" @selected(old('competency_category') === 'core')>Core Competency</option>
                    <option value="common" @selected(old('competency_category') === 'common')>Common Competency</option>
                    <option value="basic" @selected(old('competency_category') === 'basic')>Basic Competency</option>
                    <option value="custom" @selected(old('competency_category') === 'custom')>Institutional / Custom</option>
                </select>
                @error('competency_category')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-title">Module Title</label>
                <input id="module-title" name="title" value="{{ old('title') }}" required maxlength="160" placeholder="e.g. Provide Care and Support to Infants and Toddlers">
                @error('title')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-topic">Sub-topic / Learning Outcome</label>
                <input id="module-topic" name="topic" list="subtopics-list" value="{{ old('topic') }}" maxlength="120" placeholder="e.g. Comfort infants and toddlers">
                <datalist id="subtopics-list"></datalist>
            </div>

            <div class="lms-field">
                <label for="module-hours">Nominal / Estimated Hours</label>
                <input id="module-hours" type="number" name="estimated_hours" value="{{ old('estimated_hours', 40) }}" min="1" max="500" placeholder="e.g. 40">
            </div>

            <div class="lms-field">
                <label for="module-position">Display Order</label>
                <input id="module-position" type="number" name="position" value="{{ old('position', 0) }}" min="0" max="999">
            </div>

            <div class="lms-field lms-field-wide">
                <label for="module-description">Instructions & Overview</label>
                <textarea id="module-description" name="description" required maxlength="5000" rows="3" placeholder="Tell trainees what to review, learning elements covered, and practical objectives.">{{ old('description') }}</textarea>
                @error('description')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <fieldset class="lms-audience-fieldset lms-field-wide" data-audience-scope>
                <legend>Assign to</legend>
                <div class="lms-choice-grid">
                    <label class="lms-choice-card">
                        <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control @checked(old('audience_type', 'batch') === 'batch')> Entire class</span>
                        <span class="lms-choice-help">Everyone in one approved batch</span>
                        <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                            <option value="">Choose class</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) old('training_batch_id', $activeBatch?->id) === (string) $batch->id)>
                                    {{ $batch->name }} {{ $batch->year }}{{ $batch->is_active ? ' - Active' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="lms-choice-card">
                        <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control @checked(old('audience_type') === 'trainee')> Specific trainee</span>
                        <span class="lms-choice-help">Private support or remediation material</span>
                        <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                            <option value="">Choose approved trainee</option>
                            @foreach($trainees as $trainee)
                                <option value="{{ $trainee->id }}" @selected((string) old('target_enrollment_application_id') === (string) $trainee->id)>
                                    {{ $trainee->last_name }}, {{ $trainee->first_name }} - {{ $trainee->batch?->name ?? 'No class' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
                @error('audience_type')<p class="lms-field-error">{{ $message }}</p>@enderror
            </fieldset>

            <div class="lms-field">
                <label for="module-available-at">Available from</label>
                <input id="module-available-at" type="datetime-local" name="available_at" value="{{ old('available_at') }}">
            </div>
            <div class="lms-field">
                <label for="module-due-at">Due date</label>
                <input id="module-due-at" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
            </div>

            <!-- Primary Lesson File Upload -->
            <div class="lms-field lms-field-wide">
                <label for="module-file">Primary Lesson File (PDF, PPTX, Video, Image, Audio)</label>
                <div class="lms-file-picker">
                    <input id="module-file" name="module_file" type="file" required accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" data-lms-file-input>
                    <div class="lms-file-preview" data-lms-file-preview aria-live="polite">
                        <x-dashboard-icon name="cloud-arrow-up" />
                        <span><strong>Choose primary lesson file</strong><small>PDF, PPTX, DOCX, Video, or Audio - maximum 38MB</small></span>
                    </div>
                </div>
                @error('module_file')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <!-- Supplementary Attachments Upload -->
            <div class="lms-field lms-field-wide">
                <label for="module-supplementary-files">Supplementary Handouts / Worksheets (Optional, Multiple)</label>
                <input id="module-supplementary-files" name="supplementary_files[]" type="file" multiple accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field file:mr-4 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-purple-700 hover:file:bg-purple-100">
                <small class="mt-1 block text-xs text-slate-500">Up to {{ \App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES }} worksheets, reference documents, or activity rubrics; {{ number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024) }} MB maximum per file.</small>
                @error('supplementary_files')<p class="lms-field-error">{{ $message }}</p>@enderror
                @error('supplementary_files.*')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-form-options lms-field-wide">
                <label class="lms-check">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                    <span>Publish to trainees immediately</span>
                </label>
            </div>
            <div class="lms-form-actions lms-field-wide">
                <button class="primary-action">Create & Open Module Hub</button>
            </div>
        </form>
    </details>

    <section aria-labelledby="classwork-list-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Learning materials</p>
                <h2 id="classwork-list-title">Your classwork</h2>
            </div>
            <div class="lms-heading-stats">
                <span>{{ collect($modules)->count() }} total</span>
                <span>{{ $publishedCount }} published</span>
            </div>
        </div>

        <div class="lms-module-grid">
            @forelse($modules as $module)
                @php
                    $isPrivate = filled($module->target_enrollment_application_id);
                    $isAvailable = ! $module->available_at || $module->available_at->isPast();
                    $fileType = $module->fileTypeLabel();
                    $suppCount = count($module->supplementaryList());
                    $quizCount = $module->quizzes()->count();
                    $completedCount = $module->progressRecords->where('status', 'completed')->count();
                    $competentCount = $module->progressRecords->where('competency_outcome', 'competent')->count();
                @endphp
                <article class="lms-module-card" data-module-card data-module-title="{{ str($module->title)->lower() }}">
                    <div class="lms-module-accent" aria-hidden="true"></div>
                    <header>
                        <div class="lms-module-icon"><x-dashboard-icon name="book-open" /></div>
                        <div class="lms-module-statuses">
                            <span class="lms-status-chip {{ $module->is_published ? 'is-green' : 'is-neutral' }}">{{ $module->is_published ? 'Published' : 'Draft' }}</span>
                            @if($isPrivate)<span class="lms-status-chip is-purple">Private</span>@endif
                        </div>
                    </header>
                    <div class="lms-module-content">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                            @if($module->module_code)
                                <span class="rounded bg-purple-100 px-2 py-0.5 text-[11px] font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                    {{ $module->module_code }}
                                </span>
                            @endif
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                                {{ $module->categoryLabel() }}
                            </span>
                            @if($module->topic)
                                <p class="lms-module-topic text-xs text-slate-500">· {{ $module->topic }}</p>
                            @endif
                        </div>
                        <h3 class="font-bold text-slate-950 text-base">
                            <a href="{{ route('trainer.modules.show', $module) }}" class="hover:text-purple-700 transition">
                                {{ $module->title }}
                            </a>
                        </h3>
                        <p>{{ str($module->description)->limit(140) }}</p>

                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            @if($suppCount > 0)
                                <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                    <x-dashboard-icon name="paperclip" class="h-3 w-3" /> {{ $suppCount }} {{ str('attachment')->plural($suppCount) }}
                                </span>
                            @endif
                            @if($quizCount > 0)
                                <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 ring-1 ring-amber-200">
                                    <x-dashboard-icon name="list-check" class="h-3 w-3" /> {{ $quizCount }} {{ str('Assessment')->plural($quizCount) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <dl class="lms-module-meta">
                        <div><dt>Class</dt><dd>{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General' }}</dd></div>
                        <div><dt>Audience</dt><dd>{{ $isPrivate ? ($module->targetTrainee?->first_name.' '.$module->targetTrainee?->last_name) : 'Entire class' }}</dd></div>
                        <div><dt>File</dt><dd>{{ $fileType }} · {{ number_format(($module->file_size ?? 0) / 1048576, 1) }} MB</dd></div>
                        <div><dt>Due</dt><dd>{{ $module->due_at?->format('M d, Y g:i A') ?? 'No due date' }}</dd></div>
                    </dl>
                    <div class="lms-progress-summary flex justify-between items-center text-xs">
                        <span><strong>{{ $completedCount }}</strong> viewed</span>
                        <span class="font-bold text-emerald-700">{{ $competentCount }} Competent (Passed)</span>
                    </div>
                    <footer class="lms-card-footer">
                        <a href="{{ route('trainer.modules.show', $module) }}" class="primary-action text-xs py-1.5 px-3">
                            <span>Open Module Hub</span>
                        </a>
                        <div class="lms-card-actions">
                            <form method="POST" action="{{ route('trainer.modules.update', $module) }}">
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
                                <button class="lms-text-action">{{ $module->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <form method="POST" action="{{ route('trainer.modules.destroy', $module) }}" data-confirm="Delete '{{ $module->title }}' and its recorded learner progress?">
                                @csrf
                                @method('DELETE')
                                <button class="lms-text-action is-danger">Delete</button>
                            </form>
                        </div>
                    </footer>
                </article>
            @empty
                <div class="lms-empty-state lms-grid-empty">
                    <x-dashboard-icon name="book-open" />
                    <h2>No classwork yet</h2>
                    <p>Create a learning material to begin organizing the classwork library.</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($modules, 'hasPages') && $modules->hasPages())
            <div class="lms-pagination">{{ $modules->links() }}</div>
        @endif
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const presetSelect = document.querySelector('[data-module-preset-select]');
        const codeInput = document.getElementById('module-code');
        const categorySelect = document.getElementById('module-category');
        const titleInput = document.getElementById('module-title');
        const topicInput = document.getElementById('module-topic');
        const hoursInput = document.getElementById('module-hours');
        const datalist = document.getElementById('subtopics-list');

        if (presetSelect) {
            presetSelect.addEventListener('change', () => {
                const selectedOption = presetSelect.selectedOptions[0];
                if (!selectedOption || !selectedOption.value) return;

                const code = selectedOption.dataset.code || '';
                const category = selectedOption.dataset.category || 'core';
                const title = selectedOption.dataset.title || '';
                const hours = selectedOption.dataset.hours || '40';
                let outcomes = [];

                try {
                    outcomes = JSON.parse(selectedOption.dataset.outcomes || '[]');
                } catch (e) {}

                if (codeInput && code) codeInput.value = code;
                if (categorySelect && category) categorySelect.value = category;
                if (titleInput && title) titleInput.value = title;
                if (hoursInput && hours) hoursInput.value = hours;

                if (datalist) {
                    datalist.innerHTML = '';
                    outcomes.forEach(outcome => {
                        const opt = document.createElement('option');
                        opt.value = outcome;
                        datalist.appendChild(opt);
                    });
                }

                if (topicInput && outcomes.length > 0 && !topicInput.value) {
                    topicInput.value = outcomes[0];
                }
            });
        }
    });
</script>
@endsection
