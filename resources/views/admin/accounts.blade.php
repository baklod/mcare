@extends('admin.layouts.app', ['title' => 'User Accounts | MCARE Admin'])

@section('content')
@php
    $trainerErrors = $errors->getBag('trainer');
    $traineeErrors = $errors->getBag('trainee');
@endphp

<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="dashboard-section-kicker">Administration - Accounts</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Trainer and trainee accounts</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Create role-specific sign-in accounts. Admin-created trainees are immediately approved and assigned to the selected batch.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="trainer-account-dialog" class="secondary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="plus" class="h-4 w-4" />Add trainer</button>
            <button type="button" data-dashboard-dialog-open="trainee-account-dialog" class="primary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="plus" class="h-4 w-4" />Add trainee</button>
        </div>
    </header>

    <div class="dashboard-table-wrap overflow-x-auto"><table class="dashboard-table min-w-[52rem]"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead><tbody>@forelse($accounts as $account)<tr><td class="font-bold text-slate-900">{{ $account->name }}</td><td>{{ $account->email }}</td><td><span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">{{ str($account->role)->headline() }}</span></td><td>{{ $account->created_at?->format('M d, Y g:i A') }}</td></tr>@empty<tr><td colspan="4" class="py-12 text-center">No trainer or trainee accounts yet.</td></tr>@endforelse</tbody></table>@if($accounts->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $accounts->links() }}</div>@endif</div>

    <dialog id="trainer-account-dialog" data-dashboard-dialog data-auto-open="{{ $trainerErrors->any() ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(94vw,42rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="trainer-account-dialog-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div><h2 id="trainer-account-dialog-title" class="font-display text-xl font-bold text-slate-900">Add trainer</h2><p class="mt-1 text-xs text-slate-500">Create a trainer account with temporary sign-in credentials.</p></div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close trainer form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>
        <form method="POST" action="{{ route('admin.accounts.trainers.store') }}" class="space-y-4 p-6" data-dashboard-dialog-form data-submit-label="Creating trainer...">
            @csrf
            <div><label for="trainer-name" class="form-label">Full name</label><input id="trainer-name" name="name" value="{{ old('name') }}" class="form-field" required autofocus>@error('name', 'trainer')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="trainer-email" class="form-label">Email</label><input id="trainer-email" name="email" type="email" value="{{ old('email') }}" class="form-field" required>@error('email', 'trainer')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="trainer-password" class="form-label">Temporary password</label><input id="trainer-password" name="password" type="password" class="form-field" required>@error('password', 'trainer')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainer-password-confirmation" class="form-label">Confirm password</label><input id="trainer-password-confirmation" name="password_confirmation" type="password" class="form-field" required></div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action">Create trainer</button></div>
        </form>
    </dialog>

    <dialog id="trainee-account-dialog" data-dashboard-dialog data-auto-open="{{ $traineeErrors->any() ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(96vw,64rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="trainee-account-dialog-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div><h2 id="trainee-account-dialog-title" class="font-display text-xl font-bold text-slate-900">Add trainee</h2><p class="mt-1 text-xs text-slate-500">Create an approved trainee and assign their class batch.</p></div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close trainee form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>
        <form method="POST" action="{{ route('admin.accounts.trainees.store') }}" class="space-y-5 p-6" data-dashboard-dialog-form data-submit-label="Creating trainee...">
            @csrf
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-first-name" class="form-label">First name</label><input id="trainee-first-name" name="first_name" value="{{ old('first_name') }}" class="form-field" required autofocus>@error('first_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-middle-name" class="form-label">Middle name</label><input id="trainee-middle-name" name="middle_name" value="{{ old('middle_name') }}" class="form-field">@error('middle_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-last-name" class="form-label">Last name</label><input id="trainee-last-name" name="last_name" value="{{ old('last_name') }}" class="form-field" required>@error('last_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-email" class="form-label">Email</label><input id="trainee-email" name="email" type="email" value="{{ old('email') }}" class="form-field" required>@error('email', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-contact-number" class="form-label">Contact number</label><input id="trainee-contact-number" name="contact_number" value="{{ old('contact_number') }}" class="form-field" required>@error('contact_number', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-birth-date" class="form-label">Birth date</label><input id="trainee-birth-date" name="birth_date" type="date" value="{{ old('birth_date') }}" class="form-field" required>@error('birth_date', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-gender" class="form-label">Gender</label><select id="trainee-gender" name="gender" class="form-field" required><option value="">Select</option><option @selected(old('gender') === 'Male')>Male</option><option @selected(old('gender') === 'Female')>Female</option></select>@error('gender', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-schedule" class="form-label">Schedule</label><select id="trainee-schedule" name="schedule_preference" class="form-field" required><option value="AM" @selected(old('schedule_preference') === 'AM')>AM</option><option value="PM" @selected(old('schedule_preference') === 'PM')>PM</option></select>@error('schedule_preference', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div><label for="trainee-batch" class="form-label">Batch</label><select id="trainee-batch" name="training_batch_id" class="form-field" required><option value="">Select batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)old('training_batch_id') === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select>@error('training_batch_id', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-street" class="form-label">Number and street</label><input id="trainee-street" name="street" value="{{ old('street') }}" class="form-field" required>@error('street', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-barangay" class="form-label">Barangay</label><input id="trainee-barangay" name="barangay" value="{{ old('barangay') }}" class="form-field" required>@error('barangay', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-city" class="form-label">City</label><input id="trainee-city" name="city" value="{{ old('city') }}" class="form-field" required>@error('city', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-province" class="form-label">Province</label><input id="trainee-province" name="province" value="{{ old('province') }}" class="form-field" required>@error('province', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-zip-code" class="form-label">ZIP code</label><input id="trainee-zip-code" name="zip_code" value="{{ old('zip_code') }}" class="form-field" required>@error('zip_code', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-education" class="form-label">Educational attainment</label><input id="trainee-education" name="educational_attainment" value="{{ old('educational_attainment') }}" class="form-field" required>@error('educational_attainment', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-school" class="form-label">School</label><input id="trainee-school" name="school_name" value="{{ old('school_name') }}" class="form-field" required>@error('school_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-year-graduated" class="form-label">Year graduated</label><input id="trainee-year-graduated" name="year_graduated" type="number" min="1950" max="{{ now()->year }}" value="{{ old('year_graduated') }}" class="form-field" required>@error('year_graduated', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-password" class="form-label">Temporary password</label><input id="trainee-password" name="password" type="password" class="form-field" required>@error('password', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-password-confirmation" class="form-label">Confirm password</label><input id="trainee-password-confirmation" name="password_confirmation" type="password" class="form-field" required></div>
            </div>
            @if($traineeErrors->any())<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">Please review the highlighted fields.</div>@endif
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action">Create trainee</button></div>
        </form>
    </dialog>
</section>
@endsection
