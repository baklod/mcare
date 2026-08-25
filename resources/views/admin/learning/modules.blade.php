@extends('admin.layouts.app', ['title' => 'LMS Module Management | MCARE Admin'])

@section('content')
@php
    $catalogUnits = $catalogUnits ?? \App\Support\CaregivingNcIiCatalog::units();
@endphp
<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="dashboard-section-kicker">Learning system - LMS Modules</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Module management</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Upload Caregiving NC II core modules on behalf of a trainer, assign them to specific batches, and manage learning codes and subtopics.</p>
        </div>
        <button type="button" class="primary-action" data-dashboard-dialog-open="admin-module-dialog"><x-dashboard-icon name="plus" class="h-4 w-4" />Add module</button>
    </header>

    <dialog id="admin-module-dialog" data-dashboard-dialog data-auto-open="{{ $errors->hasAny(['trainer_id', 'training_batch_id', 'module_code', 'competency_category', 'estimated_hours', 'title', 'description', 'module_file', 'supplementary_files', 'supplementary_files.*']) ? 'true' : 'false' }}" class="lms-workflow-dialog" aria-labelledby="admin-module-dialog-title">
        <div class="lms-dialog-header">
            <div><p class="lms-eyebrow">Admin LMS</p><h2 id="admin-module-dialog-title">Add a learning module</h2><p>Upload a protected module and assign its trainer and batch.</p></div>
            <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close module creator"><x-dashboard-icon name="xmark" /></button>
        </div>
        <form method="POST" action="{{ route('admin.learning.modules.store') }}" enctype="multipart/form-data" class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4" data-dashboard-dialog-form data-submit-label="Adding module...">
            @csrf

            <!-- Preset Dropdown -->
            <div class="md:col-span-2 xl:col-span-4">
                <label class="mb-2 block text-xs font-bold uppercase text-purple-800">Caregiving NC II Course Module Preset</label>
                <select id="admin-module-preset-select" class="form-field border-purple-300 focus:border-purple-600">
                    <option value="">-- Choose from Caregiving NC II Core Modules or type custom --</option>
                    <optgroup label="11 Core Competencies (TESDA TOR)">
                        @foreach($catalogUnits as $unit)
                            @if(($unit['category'] ?? '') === 'core')
                                <option value="{{ $unit['code'] }}" data-code="{{ $unit['code'] }}" data-title="{{ $unit['title'] }}" data-category="{{ $unit['category'] }}" data-hours="{{ $unit['nominal_hours'] ?? '' }}" data-outcomes="{{ json_encode($unit['outcomes']) }}">
                                    [{{ $unit['code'] }}] {{ $unit['title'] }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                    <optgroup label="Common Competencies">
                        @foreach($catalogUnits as $unit)
                            @if(($unit['category'] ?? '') === 'common')
                                <option value="{{ $unit['code'] }}" data-code="{{ $unit['code'] }}" data-title="{{ $unit['title'] }}" data-category="{{ $unit['category'] }}" data-hours="{{ $unit['nominal_hours'] ?? '' }}" data-outcomes="{{ json_encode($unit['outcomes']) }}">
                                    [{{ $unit['code'] }}] {{ $unit['title'] }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                    <optgroup label="Basic Competencies">
                        @foreach($catalogUnits as $unit)
                            @if(($unit['category'] ?? '') === 'basic')
                                <option value="{{ $unit['code'] }}" data-code="{{ $unit['code'] }}" data-title="{{ $unit['title'] }}" data-category="{{ $unit['category'] }}" data-hours="{{ $unit['nominal_hours'] ?? '' }}" data-outcomes="{{ json_encode($unit['outcomes']) }}">
                                    [{{ $unit['code'] }}] {{ $unit['title'] }}
                                </option>
                            @endif
                        @endforeach
                    </optgroup>
                </select>
                <small class="mt-1 block text-xs text-slate-500">Choosing a course module preset automatically fills the Module Code, Title, and suggested subtopics.</small>
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Trainer</label>
                <select name="trainer_id" class="form-field" required>
                    <option value="">Select trainer</option>
                    @foreach($trainers as $trainer)
                        <option value="{{ $trainer->id }}" @selected((int)old('trainer_id') === $trainer->id)>{{ $trainer->name }} · {{ $trainer->email }}</option>
                    @endforeach
                </select>
                @error('trainer_id')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch Assignment</label>
                <select name="training_batch_id" class="form-field" required>
                    <option value="">Select batch</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" @selected((int)old('training_batch_id') === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                    @endforeach
                </select>
                @error('training_batch_id')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Code</label>
                <input id="admin-module-code" name="module_code" value="{{ old('module_code') }}" class="form-field font-mono font-bold" placeholder="e.g. HCS323301">
                @error('module_code')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Competency Category</label>
                <select id="admin-module-category" name="competency_category" class="form-field">
                    <option value="custom" @selected(old('competency_category', 'custom') === 'custom')>Institutional / Custom</option>
                    <option value="core" @selected(old('competency_category') === 'core')>Core Competency</option>
                    <option value="common" @selected(old('competency_category') === 'common')>Common Competency</option>
                    <option value="basic" @selected(old('competency_category') === 'basic')>Basic Competency</option>
                </select>
                @error('competency_category')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Sub-topic / Learning Outcome</label>
                <input id="admin-module-topic" name="topic" list="admin-subtopics-list" value="{{ old('topic') }}" class="form-field" placeholder="e.g. Comfort infants and toddlers">
                <datalist id="admin-subtopics-list"></datalist>
                @error('topic')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Nominal Hours</label>
                <input id="admin-module-hours" name="estimated_hours" type="number" min="1" max="500" value="{{ old('estimated_hours') }}" class="form-field" placeholder="e.g. 40">
                @error('estimated_hours')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2 xl:col-span-4">
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Title</label>
                <input id="admin-module-title" name="title" value="{{ old('title') }}" class="form-field" required placeholder="e.g. Provide Care and Support to Infants and Toddlers">
                @error('title')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Instructions & Description</label>
                <textarea name="description" rows="3" class="form-field" required placeholder="Overview of the core module, competencies, and learning instructions.">{{ old('description') }}</textarea>
                @error('description')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">PDF, Office, image, video, or audio</label>
                <input name="module_file" type="file" accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field" required>
                @error('module_file')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2 xl:col-span-4">
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Supplementary Handouts (Optional)</label>
                <input name="supplementary_files[]" type="file" multiple accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field">
                <p class="mt-1 text-xs text-slate-500">Up to {{ \App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES }} files, {{ number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024) }} MB each.</p>
                @error('supplementary_files')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                @error('supplementary_files.*')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                <input type="hidden" name="is_published" value="0">
                <input name="is_published" type="checkbox" value="1" @checked(old('is_published', true))> Publish as the current active batch module
            </label>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 md:col-span-2 md:flex-row md:justify-end xl:col-span-3">
                <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                <button class="primary-action" data-action-button>Add module</button>
            </div>
        </form>
    </dialog>

    <form method="GET" data-auto-filter class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search module, code, or trainer</label>
            <input name="search" value="{{ $filters['search'] ?? '' }}" class="form-field" placeholder="Search by title, code (e.g. HCS323301), trainer...">
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
            <select name="batch_id" class="form-field">
                <option value="">All batches</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Publication</label>
            <select name="published" class="form-field">
                <option value="">All states</option>
                <option value="yes" @selected(($filters['published'] ?? '') === 'yes')>Published</option>
                <option value="no" @selected(($filters['published'] ?? '') === 'no')>Draft</option>
            </select>
        </div>
        <div class="flex gap-2 md:col-span-4">
            <button class="primary-action">Filter modules</button>
            <a href="{{ route('admin.learning.modules') }}" class="secondary-action">Reset</a>
        </div>
    </form>

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table min-w-[64rem]">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Trainer</th>
                    <th>Batch</th>
                    <th>File</th>
                    <th>Publication</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modules as $module)
                    <tr>
                        <td>
                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                @if($module->module_code)
                                    <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                        {{ $module->module_code }}
                                    </span>
                                @endif
                                @if($module->topic)
                                    <span class="text-xs font-medium text-slate-500">{{ $module->topic }}</span>
                                @endif
                            </div>
                            <p class="font-bold text-slate-950">{{ $module->title }}</p>
                            <p class="mt-1 max-w-md text-xs line-clamp-2 text-slate-600">{{ $module->description }}</p>
                        </td>
                        <td>{{ $module->trainer?->name ?? 'Unassigned' }}</td>
                        <td>{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General' }}</td>
                        <td>
                            <span class="break-all text-xs">{{ $module->original_file_name }}</span>
                            @if(count($module->supplementaryList()) > 0)
                                <p class="mt-1 text-[11px] font-semibold text-purple-700">+ {{ count($module->supplementaryList()) }} supplementary</p>
                            @endif
                        </td>
                        <td>
                            <span class="dashboard-pill {{ $module->delivery_status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($module->delivery_status === 'closed' ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-100 text-slate-700 ring-slate-200') }}">
                                {{ $module->deliveryStatusLabel() }}
                            </span>
                            <p class="mt-2 text-xs text-slate-500">{{ $module->published_at?->format('M d, Y g:i A') ?? 'Not published' }}</p>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('classroom-comments.index', ['type' => 'module', 'id' => $module->id]) }}" class="secondary-action text-xs">Comments</a>
                                <form method="POST" action="{{ route('admin.learning.modules.destroy', $module) }}" data-confirm="Remove this module and its recorded progress?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="min-h-10 rounded-lg border border-red-200 bg-white px-3 text-xs font-bold text-red-700 hover:bg-red-50">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-14 text-center text-slate-500">No modules match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($modules->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $modules->links() }}</div>@endif
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const presetSelect = document.getElementById('admin-module-preset-select');
        const codeInput = document.getElementById('admin-module-code');
        const titleInput = document.getElementById('admin-module-title');
        const topicInput = document.getElementById('admin-module-topic');
        const categoryInput = document.getElementById('admin-module-category');
        const hoursInput = document.getElementById('admin-module-hours');
        const datalist = document.getElementById('admin-subtopics-list');

        if (presetSelect) {
            presetSelect.addEventListener('change', () => {
                const selectedOption = presetSelect.selectedOptions[0];
                if (!selectedOption || !selectedOption.value) return;

                const code = selectedOption.dataset.code || '';
                const title = selectedOption.dataset.title || '';
                const category = selectedOption.dataset.category || '';
                const hours = selectedOption.dataset.hours || '';
                let outcomes = [];

                try {
                    outcomes = JSON.parse(selectedOption.dataset.outcomes || '[]');
                } catch (e) {}

                if (codeInput && code) codeInput.value = code;
                if (titleInput && title) titleInput.value = title;
                if (categoryInput && category) categoryInput.value = category;
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
