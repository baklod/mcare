@extends('admin.layouts.app', ['title' => 'Career Hub | MCARE Admin'])

@section('content')
@php
    $careerCreateErrors = $errors->getBag('careerCreate');
@endphp

<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="dashboard-section-kicker">Learning system · Alumni Career Hub</p>
            <h1 class="mt-2 dashboard-section-title text-3xl">Connect graduates to their next opportunity</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Publish verified caregiving opportunities for alumni and keep the graduate transition visible to the MCARE team.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="career-opportunity-dialog" class="primary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="plus" class="h-4 w-4" />
                Add opportunity
            </button>
            <a href="{{ route('admin.learning.alumni-jobs.preview') }}" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="briefcase" class="h-4 w-4" />
                Preview alumni portal
            </a>
            <a href="{{ route('notifications.index') }}" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="bell" class="h-4 w-4" />
                Notification center
            </a>
        </div>
    </header>

    @if (session('saved'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('saved') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Approved trainees</p><p class="dashboard-stat-value">{{ $approvedTrainees }}</p><p class="dashboard-stat-help">Current learner pipeline</p></div></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Completed batches</p><p class="dashboard-stat-value">{{ $completedBatches }}</p><p class="dashboard-stat-help">Training records with end dates</p></div></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Alumni accounts</p><p class="dashboard-stat-value">{{ $alumniAccounts }}</p><p class="dashboard-stat-help">Accounts ready for career updates</p></div></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Published opportunities</p><p class="dashboard-stat-value">{{ $publishedJobs }}</p><p class="dashboard-stat-help">Visible in the alumni portal</p></div></article>
    </div>

    <dialog id="career-opportunity-dialog" data-dashboard-dialog data-auto-open="{{ $careerCreateErrors->any() ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(96vw,56rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-opportunity-form-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <h2 id="career-opportunity-form-title" class="font-display text-xl font-bold text-slate-900">Add a career opportunity</h2>
                <p class="mt-1 text-xs text-slate-500">Publish only after employer and application details are confirmed.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close career opportunity form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>

        <form method="POST" action="{{ route('admin.learning.alumni-jobs.store') }}" class="grid gap-4 p-6 md:grid-cols-2" data-dashboard-dialog-form data-submit-label="Saving opportunity...">
            @csrf
            <div><label for="career-title" class="form-label">Role title</label><input id="career-title" name="title" value="{{ old('title') }}" required maxlength="160" class="form-field" placeholder="Caregiver - Home Care" autofocus>@error('title', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-employer" class="form-label">Employer</label><input id="career-employer" name="employer" value="{{ old('employer') }}" required maxlength="160" class="form-field" placeholder="Mission Care partner facility">@error('employer', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-location" class="form-label">Location</label><input id="career-location" name="location" value="{{ old('location') }}" maxlength="160" class="form-field" placeholder="Iriga City, Camarines Sur">@error('location', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-type" class="form-label">Employment type</label><select id="career-type" name="employment_type" class="form-field"><option value="">Select type</option>@foreach ($employmentTypes as $value => $label)<option value="{{ $value }}" @selected(old('employment_type') === $value)>{{ $label }}</option>@endforeach</select>@error('employment_type', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-deadline" class="form-label">Application deadline</label><input id="career-deadline" name="application_deadline" type="datetime-local" value="{{ old('application_deadline') }}" class="form-field">@error('application_deadline', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-email" class="form-label">Application email</label><input id="career-email" name="application_email" type="email" value="{{ old('application_email') }}" maxlength="255" class="form-field" placeholder="recruitment@example.com">@error('application_email', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label for="career-url" class="form-label">Application link</label><input id="career-url" name="application_url" type="url" value="{{ old('application_url') }}" maxlength="2048" class="form-field" placeholder="https://employer.example/jobs/caregiver">@error('application_url', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label for="career-description" class="form-label">Description</label><textarea id="career-description" name="description" rows="4" required maxlength="5000" class="form-field" placeholder="Describe the role, care setting, and responsibilities.">{{ old('description') }}</textarea>@error('description', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label for="career-requirements" class="form-label">Requirements</label><textarea id="career-requirements" name="requirements" rows="4" maxlength="5000" class="form-field" placeholder="List NC II, experience, schedule, and other confirmed requirements.">{{ old('requirements') }}</textarea>@error('requirements', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <label class="flex items-start gap-3 rounded-xl border border-purple-100 bg-purple-50/60 p-4 text-sm text-slate-700 md:col-span-2"><input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Publish now</span><span class="mt-1 block leading-5">Alumni accounts receive an in-app notification when this opportunity becomes visible.</span></span></label>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 md:col-span-2 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Save opportunity</button></div>
        </form>
    </dialog>

    <section class="dashboard-panel" aria-labelledby="career-opportunities-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Career board</p><h2 id="career-opportunities-title" class="dashboard-section-title text-xl">Opportunities and drafts</h2></div>
            <span class="text-sm text-slate-500">{{ $jobs->total() }} total listings</span>
        </div>

        <div class="mt-5 space-y-4">
            @forelse ($jobs as $job)
                <details class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                    <summary class="flex cursor-pointer list-none flex-col gap-3 outline-none sm:flex-row sm:items-start sm:justify-between">
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="text-base font-bold text-slate-950">{{ $job->title }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $job->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $job->is_published ? 'Published' : 'Draft' }}</span>
                                @if ($job->isExpired())<span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">Expired</span>@endif
                            </span>
                            <span class="mt-2 block text-sm text-slate-600">{{ $job->employer }} @if($job->location) · {{ $job->location }} @endif</span>
                        </span>
                        <span class="text-sm font-bold text-purple-700">Edit listing</span>
                    </summary>

                    <form method="POST" action="{{ route('admin.learning.alumni-jobs.update', $job) }}" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 md:grid-cols-2">
                        @csrf
                        @method('PATCH')
                        <div><label class="form-label" for="job-title-{{ $job->id }}">Role title</label><input id="job-title-{{ $job->id }}" name="title" value="{{ $job->title }}" required maxlength="160" class="form-field"></div>
                        <div><label class="form-label" for="job-employer-{{ $job->id }}">Employer</label><input id="job-employer-{{ $job->id }}" name="employer" value="{{ $job->employer }}" required maxlength="160" class="form-field"></div>
                        <div><label class="form-label" for="job-location-{{ $job->id }}">Location</label><input id="job-location-{{ $job->id }}" name="location" value="{{ $job->location }}" maxlength="160" class="form-field"></div>
                        <div><label class="form-label" for="job-type-{{ $job->id }}">Employment type</label><select id="job-type-{{ $job->id }}" name="employment_type" class="form-field"><option value="">Select type</option>@foreach ($employmentTypes as $value => $label)<option value="{{ $value }}" @selected($job->employment_type === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div><label class="form-label" for="job-deadline-{{ $job->id }}">Application deadline</label><input id="job-deadline-{{ $job->id }}" name="application_deadline" type="datetime-local" value="{{ $job->application_deadline?->format('Y-m-d\TH:i') }}" class="form-field"></div>
                        <div><label class="form-label" for="job-email-{{ $job->id }}">Application email</label><input id="job-email-{{ $job->id }}" name="application_email" type="email" value="{{ $job->application_email }}" maxlength="255" class="form-field"></div>
                        <div class="md:col-span-2"><label class="form-label" for="job-url-{{ $job->id }}">Application link</label><input id="job-url-{{ $job->id }}" name="application_url" type="url" value="{{ $job->application_url }}" maxlength="2048" class="form-field"></div>
                        <div class="md:col-span-2"><label class="form-label" for="job-description-{{ $job->id }}">Description</label><textarea id="job-description-{{ $job->id }}" name="description" rows="3" required maxlength="5000" class="form-field">{{ $job->description }}</textarea></div>
                        <div class="md:col-span-2"><label class="form-label" for="job-requirements-{{ $job->id }}">Requirements</label><textarea id="job-requirements-{{ $job->id }}" name="requirements" rows="3" maxlength="5000" class="form-field">{{ $job->requirements }}</textarea></div>
                        <label class="flex items-start gap-3 rounded-xl border border-purple-100 bg-purple-50/60 p-4 text-sm text-slate-700 md:col-span-2"><input type="checkbox" name="is_published" value="1" @checked($job->is_published) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Published for alumni</span><span class="mt-1 block leading-5">Uncheck to keep the record as a draft.</span></span></label>
                        <div class="flex flex-wrap gap-2 md:col-span-2"><button type="submit" class="primary-action">Save changes</button><button type="submit" form="delete-job-{{ $job->id }}" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove listing</button></div>
                    </form>
                    <form id="delete-job-{{ $job->id }}" method="POST" action="{{ route('admin.learning.alumni-jobs.destroy', $job) }}" data-confirm="Remove this career opportunity?"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"></form>
                </details>
            @empty
                <div class="py-10 text-center"><x-dashboard-icon name="briefcase" class="mx-auto h-8 w-8 text-slate-300" /><p class="mt-3 font-bold text-slate-800">No career opportunities yet</p><p class="mt-1 text-sm text-slate-500">Add a verified employer opportunity above to start the alumni board.</p></div>
            @endforelse
        </div>

        @if ($jobs->hasPages())<div class="mt-6 border-t border-slate-100 pt-5">{{ $jobs->links() }}</div>@endif
    </section>

    <section class="dashboard-panel">
        <p class="dashboard-section-kicker">Graduation gate</p>
        <h2 class="mt-2 dashboard-section-title text-xl">Keep the career handoff evidence-based</h2>
        <ol class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach(['Finish required modules', 'Pass assessments and competency', 'Complete attendance requirement', 'Issue a verified certificate'] as $index => $step)
                <li class="rounded-xl border border-slate-200 bg-slate-50 p-4"><span class="text-xs font-bold text-purple-700">STEP {{ $index + 1 }}</span><p class="mt-2 font-bold text-slate-900">{{ $step }}</p></li>
            @endforeach
        </ol>
    </section>
</section>
@endsection
