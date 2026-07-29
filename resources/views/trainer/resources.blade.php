@extends('trainer.layouts.app', ['title' => 'Classwork | MCARE Trainer'])

@section('content')
@php
    $activeBatch = $batches->firstWhere('is_active', true);
    $publishedCount = collect($modules)->where('is_published', true)->count();
@endphp

<div class="lms-page" data-lms-classwork>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">MCARE Classroom</p>
            <h1>Classwork library</h1>
            <p>Create learning materials, control who can see them, and revise the content without rebuilding the class page.</p>
        </div>
        <a href="{{ route('trainer.assessments') }}" class="secondary-action">Manage quizzes</a>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainer classroom sections">
        @if(\Illuminate\Support\Facades\Route::has('trainer.stream'))
            <a href="{{ route('trainer.stream') }}">Stream</a>
        @endif
        <a href="{{ route('trainer.resources') }}" class="is-active" aria-current="page">Classwork</a>
        <a href="{{ route('trainer.trainees') }}">People</a>
        <a href="{{ route('trainer.assessments') }}">Quizzes</a>
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
                <strong>Create learning material</strong>
                <small>PDF, image, or video for a class or one trainee</small>
            </span>
            <span class="lms-summary-action">Open composer</span>
        </summary>

        <form method="POST" action="{{ route('trainer.modules.store') }}" enctype="multipart/form-data" class="lms-form-grid lms-composer-form">
            @csrf
            <div class="lms-field lms-field-wide">
                <label for="module-title">Title</label>
                <input id="module-title" name="title" value="{{ old('title') }}" required maxlength="160" placeholder="Example: Module 03 - Infection Control">
                @error('title')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>
            <div class="lms-field">
                <label for="module-topic">Topic</label>
                <input id="module-topic" name="topic" value="{{ old('topic') }}" maxlength="120" placeholder="Example: Basic patient care">
            </div>
            <div class="lms-field">
                <label for="module-position">Order</label>
                <input id="module-position" type="number" name="position" value="{{ old('position', 0) }}" min="0" max="999">
            </div>
            <div class="lms-field lms-field-wide">
                <label for="module-description">Instructions</label>
                <textarea id="module-description" name="description" required maxlength="1200" rows="4" placeholder="Tell trainees what to review and what they should learn.">{{ old('description') }}</textarea>
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
            <div class="lms-field lms-field-wide">
                <label for="module-file">Learning file</label>
                <div class="lms-file-picker">
                    <input id="module-file" name="module_file" type="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.mp4,.webm" data-lms-file-input>
                    <div class="lms-file-preview" data-lms-file-preview aria-live="polite">
                        <x-dashboard-icon name="cloud-arrow-up" />
                        <span><strong>Choose a file</strong><small>PDF, image, MP4, or WEBM - maximum 100MB</small></span>
                    </div>
                </div>
                @error('module_file')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>
            <div class="lms-form-options lms-field-wide">
                <label class="lms-check">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                    <span>Publish to trainees immediately</span>
                </label>
            </div>
            <div class="lms-form-actions lms-field-wide">
                <button class="primary-action">Create material</button>
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
                    $fileType = str($module->mime_type ?: pathinfo($module->original_file_name, PATHINFO_EXTENSION))->contains('pdf')
                        ? 'PDF'
                        : (str_starts_with((string) $module->mime_type, 'video/') ? 'Video' : 'Image');
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
                        <p class="lms-module-topic">{{ $module->topic ?: 'Learning material' }}</p>
                        <h3>{{ $module->title }}</h3>
                        <p>{{ str($module->description)->limit(150) }}</p>
                    </div>
                    <dl class="lms-module-meta">
                        <div><dt>Class</dt><dd>{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General' }}</dd></div>
                        <div><dt>Audience</dt><dd>{{ $isPrivate ? ($module->targetTrainee?->first_name.' '.$module->targetTrainee?->last_name) : 'Entire class' }}</dd></div>
                        <div><dt>File</dt><dd>{{ $fileType }} - {{ number_format(($module->file_size ?? 0) / 1048576, 1) }} MB</dd></div>
                        <div><dt>Due</dt><dd>{{ $module->due_at?->format('M d, Y g:i A') ?? 'No due date' }}</dd></div>
                    </dl>
                    <div class="lms-progress-summary">
                        <span>{{ $module->progressRecords->where('status', 'completed')->count() }} completed</span>
                        <span>{{ $module->progressRecords->where('status', 'in_progress')->count() }} in progress</span>
                    </div>
                    <footer class="lms-card-footer">
                        <a href="{{ route('trainer.modules.show', $module) }}" class="lms-text-action">Preview</a>
                        <div class="lms-card-actions">
                            <form method="POST" action="{{ route('trainer.modules.update', $module) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="title" value="{{ $module->title }}">
                                <input type="hidden" name="description" value="{{ $module->description }}">
                                <input type="hidden" name="topic" value="{{ $module->topic }}">
                                <input type="hidden" name="position" value="{{ $module->position ?? 0 }}">
                                <input type="hidden" name="audience_type" value="{{ $isPrivate ? 'trainee' : 'batch' }}">
                                <input type="hidden" name="training_batch_id" value="{{ $module->training_batch_id }}">
                                <input type="hidden" name="target_enrollment_application_id" value="{{ $module->target_enrollment_application_id }}">
                                <input type="hidden" name="available_at" value="{{ $module->available_at?->format('Y-m-d\TH:i') }}">
                                <input type="hidden" name="due_at" value="{{ $module->due_at?->format('Y-m-d\TH:i') }}">
                                <input type="hidden" name="is_published" value="{{ $module->is_published ? 0 : 1 }}">
                                <button class="lms-text-action">{{ $module->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <details class="lms-inline-editor" data-module-editor>
                                <summary>Edit</summary>
                                <form method="POST" action="{{ route('trainer.modules.update', $module) }}" enctype="multipart/form-data" class="lms-form-grid" data-audience-scope>
                                    @csrf
                                    @method('PATCH')
                                    <div class="lms-field lms-field-wide"><label for="module-{{ $module->id }}-title">Title</label><input id="module-{{ $module->id }}-title" name="title" value="{{ $module->title }}" required maxlength="160"></div>
                                    <div class="lms-field"><label for="module-{{ $module->id }}-topic">Topic</label><input id="module-{{ $module->id }}-topic" name="topic" value="{{ $module->topic }}" maxlength="120"></div>
                                    <div class="lms-field"><label for="module-{{ $module->id }}-position">Order</label><input id="module-{{ $module->id }}-position" type="number" name="position" value="{{ $module->position ?? 0 }}" min="0" max="999"></div>
                                    <div class="lms-field lms-field-wide"><label for="module-{{ $module->id }}-description">Instructions</label><textarea id="module-{{ $module->id }}-description" name="description" rows="4" required maxlength="1200">{{ $module->description }}</textarea></div>
                                    <fieldset class="lms-audience-fieldset lms-field-wide">
                                        <legend>Assign to</legend>
                                        <div class="lms-choice-grid">
                                            <label class="lms-choice-card">
                                                <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control @checked(! $isPrivate)> Entire class</span>
                                                <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                                                    @foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int) $module->training_batch_id === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach
                                                </select>
                                            </label>
                                            <label class="lms-choice-card">
                                                <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control @checked($isPrivate)> Specific trainee</span>
                                                <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                                                    <option value="">Choose approved trainee</option>
                                                    @foreach($trainees as $trainee)<option value="{{ $trainee->id }}" @selected((int) $module->target_enrollment_application_id === $trainee->id)>{{ $trainee->last_name }}, {{ $trainee->first_name }}</option>@endforeach
                                                </select>
                                            </label>
                                        </div>
                                    </fieldset>
                                    <div class="lms-field"><label for="module-{{ $module->id }}-available">Available from</label><input id="module-{{ $module->id }}-available" type="datetime-local" name="available_at" value="{{ $module->available_at?->format('Y-m-d\TH:i') }}"></div>
                                    <div class="lms-field"><label for="module-{{ $module->id }}-due">Due date</label><input id="module-{{ $module->id }}-due" type="datetime-local" name="due_at" value="{{ $module->due_at?->format('Y-m-d\TH:i') }}"></div>
                                    <div class="lms-field lms-field-wide"><label for="module-{{ $module->id }}-file">Replace file (optional)</label><input id="module-{{ $module->id }}-file" name="module_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.mp4,.webm" data-lms-file-input><div class="lms-file-preview is-compact" data-lms-file-preview aria-live="polite"><span><strong>{{ $module->original_file_name }}</strong><small>Keep this file or choose a replacement</small></span></div></div>
                                    <div class="lms-form-options lms-field-wide"><label class="lms-check"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked($module->is_published)><span>Published</span></label></div>
                                    <div class="lms-form-actions lms-field-wide"><button type="button" class="secondary-action" data-close-inline-editor>Cancel</button><button class="primary-action">Save material</button></div>
                                </form>
                            </details>
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
@endsection
