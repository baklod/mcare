@extends('admin.layouts.app', ['title' => 'Batch Schedules | MCARE Admin'])

@section('content')
    @php
        $batch = $editingBatch;
        $formAction = $batch ? route('admin.schedules.update', $batch) : route('admin.schedules.store');
    @endphp

    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="dashboard-section-kicker">Scheduling workspace</p>
                <h1 class="dashboard-section-title mt-2 text-3xl">Training calendar</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Review every batch in one calendar, select a date for its complete agenda, then create or update the recurring AM/PM schedule below.</p>
            </div>
            <a href="#schedule-editor" class="primary-action inline-flex w-fit items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="calendar-days" class="h-4 w-4" />
                {{ $batch ? 'Continue editing batch' : 'Create batch schedule' }}
            </a>
        </header>

        <x-training-calendar
            :month="$calendarMonth"
            :sessions="$calendarSessions"
            :selected-date="$calendarSelectedDate"
            :month-route="url()->current()"
            :editable="true"
            eyebrow="Admin master calendar"
            :heading="$calendarMonth->format('F Y').' schedule overview'"
            description="AM and PM blocks from all saved batches appear together. Select a date to see the full agenda and jump directly to the recurring batch editor."
            empty-message="No batch sessions are scheduled for this date."
        />

    <section class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <aside id="schedule-editor" class="order-2 rounded-3xl border border-purple-100 bg-white p-6 shadow-xl shadow-purple-100/40 sm:p-7">
            <p class="text-sm font-bold uppercase text-purple-600">Batch scheduling</p>
            <h1 class="mt-2 text-3xl font-bold leading-tight text-slate-900">{{ $batch ? 'Edit batch' : 'Create batch' }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Set the active batch, enrollment deadline, AM/PM class days, class times, and room destination. Receipts and payment screens will use these details.
            </p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-700">
                    Please correct the schedule fields before saving.
                </div>
            @endif

            <form method="POST" action="{{ $formAction }}" class="mt-6 space-y-5" data-single-action>
                @csrf
                @if ($batch)
                    @method('PATCH')
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">Batch name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $batch->name ?? 'Batch 1') }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                        @error('name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="year" class="mb-2 block text-sm font-semibold text-slate-800">Year</label>
                        <input id="year" name="year" type="number" min="2024" max="2100" value="{{ old('year', $batch->year ?? now()->year) }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                        @error('year') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex items-start gap-3 rounded-2xl border border-purple-100 bg-purple-50 px-4 py-3 text-sm font-semibold text-slate-700">
                    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $batch->is_active ?? false)) class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                    <span>Use this as the active enrollment batch for new applicants.</span>
                </label>

                <div>
                    <label for="enrollment_starts_at" class="mb-2 block text-sm font-semibold text-slate-800">Enrollment starts</label>
                    <input id="enrollment_starts_at" name="enrollment_starts_at" type="datetime-local" value="{{ old('enrollment_starts_at', $batch?->enrollment_starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                    @error('enrollment_starts_at') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="enrollment_ends_at" class="mb-2 block text-sm font-semibold text-slate-800">Enrollment deadline</label>
                    <input id="enrollment_ends_at" name="enrollment_ends_at" type="datetime-local" value="{{ old('enrollment_ends_at', $batch?->enrollment_ends_at?->format('Y-m-d\TH:i')) }}" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                    @error('enrollment_ends_at') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="training_starts_at" class="mb-2 block text-sm font-semibold text-slate-800">Training starts</label>
                        <input id="training_starts_at" name="training_starts_at" type="datetime-local" value="{{ old('training_starts_at', $batch?->training_starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                        @error('training_starts_at') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="training_ends_at" class="mb-2 block text-sm font-semibold text-slate-800">Training target completion</label>
                        <input id="training_ends_at" name="training_ends_at" type="datetime-local" value="{{ old('training_ends_at', $batch?->training_ends_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                        @error('training_ends_at') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-xs leading-5 text-slate-500">Set both training dates before sessions are published to the admin, trainer, and trainee calendars.</p>

                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-sm font-bold uppercase text-purple-600">AM class</p>
                    <div class="mt-4">
                        <label for="am_days" class="mb-2 block text-xs font-bold uppercase text-slate-500">Class days</label>
                        <input id="am_days" name="am_days" type="text" value="{{ old('am_days', $batch->am_days ?? 'MWF') }}" required placeholder="MWF" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="am_start_time" class="mb-2 block text-xs font-bold uppercase text-slate-500">Starts</label>
                            <input id="am_start_time" name="am_start_time" type="time" value="{{ old('am_start_time', $batch->am_start_time ?? '08:00') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                        </div>
                        <div>
                            <label for="am_end_time" class="mb-2 block text-xs font-bold uppercase text-slate-500">Ends</label>
                            <input id="am_end_time" name="am_end_time" type="time" value="{{ old('am_end_time', $batch->am_end_time ?? '12:00') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                        </div>
                    </div>
                    <input name="am_room" type="text" value="{{ old('am_room', $batch->am_room ?? '') }}" placeholder="Room destination, e.g. Skills Lab A" class="mt-4 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                    @error('am_days') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    @error('am_end_time') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    @error('am_room') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-sm font-bold uppercase text-purple-600">PM class</p>
                    <div class="mt-4">
                        <label for="pm_days" class="mb-2 block text-xs font-bold uppercase text-slate-500">Class days</label>
                        <input id="pm_days" name="pm_days" type="text" value="{{ old('pm_days', $batch->pm_days ?? 'TTS') }}" required placeholder="TTS" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pm_start_time" class="mb-2 block text-xs font-bold uppercase text-slate-500">Starts</label>
                            <input id="pm_start_time" name="pm_start_time" type="time" value="{{ old('pm_start_time', $batch->pm_start_time ?? '13:00') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                        </div>
                        <div>
                            <label for="pm_end_time" class="mb-2 block text-xs font-bold uppercase text-slate-500">Ends</label>
                            <input id="pm_end_time" name="pm_end_time" type="time" value="{{ old('pm_end_time', $batch->pm_end_time ?? '17:00') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                        </div>
                    </div>
                    <input name="pm_room" type="text" value="{{ old('pm_room', $batch->pm_room ?? '') }}" placeholder="Room destination, e.g. Lecture Room 2" class="mt-4 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                    @error('pm_days') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    @error('pm_end_time') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    @error('pm_room') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="mb-2 block text-sm font-semibold text-slate-800">Private admin notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">{{ old('notes', $batch->notes ?? '') }}</textarea>
                    <p class="mt-2 text-xs leading-5 text-slate-500">Visible only to administrators; this text is never used as a learner event title.</p>
                    @error('notes') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" data-action-button class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                    {{ $batch ? 'Save batch schedule' : 'Create batch schedule' }}
                </button>

                @if ($batch)
                    <a href="{{ route('admin.schedules.index') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                        Cancel edit
                    </a>
                @endif
            </form>
        </aside>

        <section class="order-1 space-y-5">
            <div class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase text-purple-600">Schedules</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-900">Batch list</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">Applicants are attached to the active batch when they submit enrollment.</p>
                    </div>
                    <a href="{{ route('admin.payment-schedules.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-3 text-sm font-bold text-purple-700 hover:bg-purple-50">
                        Payment scheduling
                    </a>
                </div>
            </div>

            @forelse ($batches as $item)
                <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-bold text-slate-900">{{ $item->name }} {{ $item->year }}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $item->acceptsEnrollment() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $item->enrollmentStateLabel() }}</span>
                                <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 ring-1 ring-purple-100">{{ $item->trainingStateLabel() }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Enrollment ends {{ $item->enrollment_ends_at->format('M d, Y g:i A') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.schedules.edit', $item) }}" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-4 py-2 text-sm font-bold text-purple-700 hover:bg-purple-50">Edit</a>
                            <form method="POST" action="{{ route('admin.schedules.destroy', $item) }}" onsubmit="return confirm('Delete this batch schedule?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" @disabled($item->applications_count > 0) class="inline-flex items-center justify-center rounded-full border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">AM</p>
                            <p class="mt-1 font-bold text-slate-900">{{ $item->scheduleLabelFor('AM') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $item->am_room ?: 'Room TBA' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">PM</p>
                            <p class="mt-1 font-bold text-slate-900">{{ $item->scheduleLabelFor('PM') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $item->pm_room ?: 'Room TBA' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">Applicants</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $item->applications_count }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-bold text-slate-900">No batch schedules yet</p>
                    <p class="mt-2 text-sm text-slate-500">Create Batch 1 2026 to start assigning applicants.</p>
                </div>
            @endforelse

            @if ($batches->hasPages())
                <div class="rounded-2xl border border-slate-100 bg-white px-5 py-4">
                    {{ $batches->links() }}
                </div>
            @endif
        </section>
    </section>
    </div>

    <script>
        document.querySelectorAll('[data-single-action]').forEach((form) => {
            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = 'Saving schedule...';
                });
            });
        });
    </script>
@endsection
