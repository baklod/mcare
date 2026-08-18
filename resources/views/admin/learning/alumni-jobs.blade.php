@extends('admin.layouts.app', ['title' => 'Alumni Job Board | MCARE Admin'])

@section('content')
@php
    $careerCreateErrors = $errors->getBag('careerCreate');
@endphp

<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="dashboard-section-kicker">Alumni connectivity</p>
            <h1 class="mt-2 dashboard-section-title text-3xl">Alumni Job Board</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="career-opportunity-dialog" class="primary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="plus" class="h-4 w-4" />
                Add caregiving duty
            </button>
            <a href="{{ route('admin.learning.alumni-jobs.preview') }}" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="briefcase" class="h-4 w-4" />
                Preview alumni board
            </a>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Alumni accounts</p><p class="dashboard-stat-value">{{ $alumniAccounts }}</p><p class="dashboard-stat-help">Dedicated Career Hub access</p></div></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Available for Duty</p><p class="dashboard-stat-value">{{ $availableAlumni }}</p><p class="dashboard-stat-help">Alumni accepting placement</p></div></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Published duties</p><p class="dashboard-stat-value">{{ $publishedJobs }}</p><p class="dashboard-stat-help">Visible on the alumni board</p></div></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Draft duties</p><p class="dashboard-stat-value">{{ $draftJobs }}</p><p class="dashboard-stat-help">Awaiting privacy review</p></div></article>
    </div>

    <dialog id="career-opportunity-dialog" data-dashboard-dialog data-auto-open="{{ $careerCreateErrors->any() ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(96vw,48rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-opportunity-form-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <h2 id="career-opportunity-form-title" class="font-display text-xl font-bold text-slate-900">Add a caregiving duty</h2>
                <p class="mt-1 text-xs text-slate-500">Use only the client-approved care summary.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close caregiving duty form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>

        <form method="POST" action="{{ route('admin.learning.alumni-jobs.store') }}" class="grid gap-4 p-6 md:grid-cols-2" data-dashboard-dialog-form data-submit-label="Saving duty...">
            @csrf
            <div><label for="career-start-date" class="form-label">Estimated start date</label><input id="career-start-date" name="estimated_start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('estimated_start_date') }}" required class="form-field" autofocus>@error('estimated_start_date', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-patient-age" class="form-label">Patient age <span class="font-normal text-slate-400">(optional)</span></label><input id="career-patient-age" name="patient_age" type="number" min="0" max="120" value="{{ old('patient_age') }}" class="form-field" placeholder="Example: 72">@error('patient_age', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-patient-gender" class="form-label">Patient gender</label><select id="career-patient-gender" name="patient_gender" required class="form-field"><option value="">Select gender</option>@foreach ($patientGenders as $value => $label)<option value="{{ $value }}" @selected(old('patient_gender') === $value)>{{ $label }}</option>@endforeach</select>@error('patient_gender', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="career-mobility" class="form-label">Mobility status</label><select id="career-mobility" name="mobility_status" required class="form-field"><option value="">Select mobility</option>@foreach ($mobilityStatuses as $value => $label)<option value="{{ $value }}" @selected(old('mobility_status') === $value)>{{ $label }}</option>@endforeach</select>@error('mobility_status', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label for="career-contraptions" class="form-label">Specific contraptions <span class="font-normal text-slate-400">(optional)</span></label><input id="career-contraptions" name="specific_contraptions" value="{{ old('specific_contraptions') }}" maxlength="255" class="form-field" placeholder="Example: wheelchair, oxygen concentrator">@error('specific_contraptions', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label for="career-condition" class="form-label">Condition summary <span class="font-normal text-slate-400">(optional)</span></label><textarea id="career-condition" name="condition_summary" rows="3" maxlength="500" class="form-field" placeholder="Short care-relevant context only">{{ old('condition_summary') }}</textarea>@error('condition_summary', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 md:col-span-2"><span class="font-bold">Privacy boundary:</span> Do not enter patient names, exact addresses, contact details, identification numbers, medical histories, or upload patient documents.</div>
            <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700 md:col-span-2"><input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Publish now</span><span class="mt-1 block leading-5">Alumni receive an in-app notification when this duty becomes visible.</span></span></label>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 md:col-span-2 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Save duty</button></div>
        </form>
    </dialog>

    <section class="dashboard-panel" aria-labelledby="career-opportunities-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Centralized postings</p><h2 id="career-opportunities-title" class="dashboard-section-title text-xl">Caregiving duties</h2></div>
            <span class="text-sm text-slate-500">{{ $jobs->total() }} total records</span>
        </div>

        <div class="mt-5 divide-y divide-slate-100">
            @forelse ($jobs as $job)
                <article class="flex flex-col gap-4 py-5 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-950">Duty #{{ $job->id }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $job->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $job->is_published ? 'Published' : 'Draft' }}</span>
                            @if (! $job->estimated_start_date)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">Privacy review required</span>@endif
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $job->estimated_start_date?->format('M d, Y') ?? 'Start date not set' }}<span class="mx-2 text-slate-300">|</span>{{ $job->patientGenderLabel() }}<span class="mx-2 text-slate-300">|</span>{{ $job->mobilityStatusLabel() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-dashboard-dialog-open="career-edit-{{ $job->id }}" class="secondary-action inline-flex items-center gap-2"><x-dashboard-icon name="pencil" class="h-4 w-4" />Edit</button>
                        <form method="POST" action="{{ route('admin.learning.alumni-jobs.destroy', $job) }}" data-confirm="Remove this caregiving duty?">@csrf @method('DELETE')<button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button></form>
                    </div>
                </article>

                <dialog id="career-edit-{{ $job->id }}" data-dashboard-dialog class="m-auto max-h-[90vh] w-[min(96vw,48rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-edit-title-{{ $job->id }}">
                    <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4"><div><h2 id="career-edit-title-{{ $job->id }}" class="font-display text-xl font-bold text-slate-900">Edit duty #{{ $job->id }}</h2><p class="mt-1 text-xs text-slate-500">Only privacy-approved care details are retained.</p></div><button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close edit form"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button></div>
                    <form method="POST" action="{{ route('admin.learning.alumni-jobs.update', $job) }}" class="grid gap-4 p-6 md:grid-cols-2" data-dashboard-dialog-form data-submit-label="Updating duty...">
                        @csrf @method('PATCH')
                        <div><label for="job-start-{{ $job->id }}" class="form-label">Estimated start date</label><input id="job-start-{{ $job->id }}" name="estimated_start_date" type="date" min="{{ now()->toDateString() }}" value="{{ $job->estimated_start_date?->toDateString() }}" required class="form-field"></div>
                        <div><label for="job-age-{{ $job->id }}" class="form-label">Patient age <span class="font-normal text-slate-400">(optional)</span></label><input id="job-age-{{ $job->id }}" name="patient_age" type="number" min="0" max="120" value="{{ $job->patient_age }}" class="form-field"></div>
                        <div><label for="job-gender-{{ $job->id }}" class="form-label">Patient gender</label><select id="job-gender-{{ $job->id }}" name="patient_gender" required class="form-field"><option value="">Select gender</option>@foreach ($patientGenders as $value => $label)<option value="{{ $value }}" @selected($job->patient_gender === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div><label for="job-mobility-{{ $job->id }}" class="form-label">Mobility status</label><select id="job-mobility-{{ $job->id }}" name="mobility_status" required class="form-field"><option value="">Select mobility</option>@foreach ($mobilityStatuses as $value => $label)<option value="{{ $value }}" @selected($job->mobility_status === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div class="md:col-span-2"><label for="job-contraptions-{{ $job->id }}" class="form-label">Specific contraptions <span class="font-normal text-slate-400">(optional)</span></label><input id="job-contraptions-{{ $job->id }}" name="specific_contraptions" value="{{ $job->specific_contraptions }}" maxlength="255" class="form-field"></div>
                        <div class="md:col-span-2"><label for="job-condition-{{ $job->id }}" class="form-label">Condition summary <span class="font-normal text-slate-400">(optional)</span></label><textarea id="job-condition-{{ $job->id }}" name="condition_summary" rows="3" maxlength="500" class="form-field">{{ $job->condition_summary }}</textarea></div>
                        <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700 md:col-span-2"><input type="checkbox" name="is_published" value="1" @checked($job->is_published) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Published for alumni</span><span class="mt-1 block leading-5">Uncheck to return this duty to draft review.</span></span></label>
                        <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 md:col-span-2 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" class="primary-action">Save changes</button></div>
                    </form>
                </dialog>
            @empty
                <div class="py-10 text-center"><x-dashboard-icon name="briefcase" class="mx-auto h-8 w-8 text-slate-300" /><p class="mt-3 font-bold text-slate-800">No caregiving duties yet</p><p class="mt-1 text-sm text-slate-500">Add the first privacy-reviewed duty to start the board.</p></div>
            @endforelse
        </div>

        @if ($jobs->hasPages())<div class="mt-6 border-t border-slate-100 pt-5">{{ $jobs->links() }}</div>@endif
    </section>

    <section class="dashboard-panel" aria-labelledby="alumni-availability-title">
        <div class="border-b border-slate-200 pb-5"><p class="dashboard-section-kicker">Caregiver connectivity</p><h2 id="alumni-availability-title" class="dashboard-section-title text-xl">Alumni availability</h2></div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3 font-bold">Alumni</th><th class="px-3 py-3 font-bold">Account</th><th class="px-3 py-3 font-bold">Duty status</th><th class="px-3 py-3 font-bold">Updated</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($alumniRoster as $alumni)
                        @php($profile = $alumni->alumniProfile)
                        <tr><td class="px-3 py-4 font-bold text-slate-900">{{ $alumni->name }}</td><td class="px-3 py-4 text-slate-600">{{ $alumni->email }}</td><td class="px-3 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable' }}</span></td><td class="px-3 py-4 text-slate-500">{{ $profile?->availability_updated_at?->diffForHumans() ?? 'Not updated' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No alumni accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($alumniRoster->hasPages())<div class="mt-5 border-t border-slate-100 pt-5">{{ $alumniRoster->links() }}</div>@endif
    </section>
</section>
@endsection
