@extends('admin.layouts.app', ['title' => 'Batch Schedules | MCARE Admin'])

@section('content')
    @php
        $batch = $editingBatch;
        $formAction = $batch ? route('admin.schedules.update', $batch) : route('admin.schedules.store');
        $batchFormHasErrors = collect([
            'name', 'year', 'is_active', 'enrollment_starts_at', 'enrollment_ends_at',
            'training_starts_at', 'training_ends_at', 'am_days', 'am_start_time',
            'am_end_time', 'am_room', 'pm_days', 'pm_start_time', 'pm_end_time',
            'pm_room', 'notes',
        ])->contains(fn (string $field): bool => $errors->has($field));
    @endphp

    <div class="space-y-6">
        
        <!-- Header -->
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Training calendar & schedules</h1>
                <p class="mt-1.5 text-sm text-slate-600">Manage enrollment windows and AM/PM class schedules.</p>
            </div>
            <button type="button" data-batch-dialog-open class="inline-flex w-fit items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                <x-dashboard-icon name="plus" class="h-4 w-4" />
                Create batch
            </button>
        </header>

        <!-- Master Calendar Component -->
        <x-training-calendar
            :month="$calendarMonth"
            :sessions="$calendarSessions"
            :selected-date="$calendarSelectedDate"
            :month-route="url()->current()"
            :editable="true"
            eyebrow="Admin master calendar"
            :heading="$calendarMonth->format('F Y').' schedule overview'"
            description="All AM and PM sessions across configured batches."
            empty-message="No batch sessions are scheduled for this date."
        />

        <section class="space-y-6">
            
            <!-- Batch Form Editor -->
            <dialog
                id="schedule-editor"
                data-batch-dialog
                data-auto-open="{{ ($batch || $batchFormHasErrors) ? 'true' : 'false' }}"
                data-cancel-url="{{ $batch ? route('admin.schedules.index') : '' }}"
                class="m-auto max-h-[90vh] w-[min(94vw,56rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45"
                aria-labelledby="batch-dialog-title"
            >
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                    <div>
                        <p class="dashboard-section-kicker">Batch scheduling</p>
                        <h2 id="batch-dialog-title" class="mt-1 font-display text-xl font-bold text-slate-900">{{ $batch ? 'Edit batch' : 'Create batch' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">Enrollment window, class periods, and room assignments.</p>
                    </div>
                    @if ($batch)
                        <a href="{{ route('admin.schedules.index') }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close batch editor" title="Close">
                            <x-dashboard-icon name="xmark" class="h-4 w-4" />
                        </a>
                    @else
                        <button type="button" data-batch-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close batch editor" title="Close">
                            <x-dashboard-icon name="xmark" class="h-4 w-4" />
                        </button>
                    @endif
                </div>

                <div class="px-6 pb-6">

                @if ($batchFormHasErrors)
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-semibold text-red-700">
                        Please correct the schedule fields before saving.
                    </div>
                @endif

                <form method="POST" action="{{ $formAction }}" class="mt-5 space-y-4" data-single-action data-batch-form>
                    @csrf
                    @if ($batch)
                        @method('PATCH')
                    @endif

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-1 block text-xs font-semibold text-slate-700">Batch name</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $batch->name ?? 'Batch 1') }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="year" class="mb-1 block text-xs font-semibold text-slate-700">Year</label>
                            <input id="year" name="year" type="number" min="2024" max="2100" value="{{ old('year', $batch->year ?? now()->year) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('year') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-700 cursor-pointer">
                        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $batch->is_active ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <span>Active enrollment batch for new applicants</span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="enrollment_starts_at" class="mb-1 block text-xs font-semibold text-slate-700">Enrollment starts</label>
                            <input id="enrollment_starts_at" name="enrollment_starts_at" type="datetime-local" value="{{ old('enrollment_starts_at', $batch?->enrollment_starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('enrollment_starts_at') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="enrollment_ends_at" class="mb-1 block text-xs font-semibold text-slate-700">Enrollment deadline</label>
                            <input id="enrollment_ends_at" name="enrollment_ends_at" type="datetime-local" value="{{ old('enrollment_ends_at', $batch?->enrollment_ends_at?->format('Y-m-d\TH:i')) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('enrollment_ends_at') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="training_starts_at" class="mb-1 block text-xs font-semibold text-slate-700">Training starts</label>
                            <input id="training_starts_at" name="training_starts_at" type="datetime-local" value="{{ old('training_starts_at', $batch?->training_starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('training_starts_at') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="training_ends_at" class="mb-1 block text-xs font-semibold text-slate-700">Training completes</label>
                            <input id="training_ends_at" name="training_ends_at" type="datetime-local" value="{{ old('training_ends_at', $batch?->training_ends_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('training_ends_at') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- AM Class Details -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">AM Class Block</span>
                        <div>
                            <label for="am_days" class="mb-1 block text-xs font-medium text-slate-600">Days (e.g. MWF)</label>
                            <input id="am_days" name="am_days" type="text" value="{{ old('am_days', $batch->am_days ?? 'MWF') }}" required placeholder="MWF" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="am_start_time" class="mb-1 block text-xs font-medium text-slate-600">Starts</label>
                                <input id="am_start_time" name="am_start_time" type="time" value="{{ old('am_start_time', $batch->am_start_time ?? '08:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            </div>
                            <div>
                                <label for="am_end_time" class="mb-1 block text-xs font-medium text-slate-600">Ends</label>
                                <input id="am_end_time" name="am_end_time" type="time" value="{{ old('am_end_time', $batch->am_end_time ?? '12:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            </div>
                        </div>
                        <input name="am_room" type="text" value="{{ old('am_room', $batch->am_room ?? '') }}" placeholder="Room / Skills Lab" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                    </div>

                    <!-- PM Class Details -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">PM Class Block</span>
                        <div>
                            <label for="pm_days" class="mb-1 block text-xs font-medium text-slate-600">Days (e.g. TTH)</label>
                            <input id="pm_days" name="pm_days" type="text" value="{{ old('pm_days', $batch->pm_days ?? 'TTH') }}" required placeholder="TTH" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="pm_start_time" class="mb-1 block text-xs font-medium text-slate-600">Starts</label>
                                <input id="pm_start_time" name="pm_start_time" type="time" value="{{ old('pm_start_time', $batch->pm_start_time ?? '13:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            </div>
                            <div>
                                <label for="pm_end_time" class="mb-1 block text-xs font-medium text-slate-600">Ends</label>
                                <input id="pm_end_time" name="pm_end_time" type="time" value="{{ old('pm_end_time', $batch->pm_end_time ?? '17:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            </div>
                        </div>
                        <input name="pm_room" type="text" value="{{ old('pm_room', $batch->pm_room ?? '') }}" placeholder="Room / Lecture Room" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-xs font-semibold text-slate-700">Private admin notes</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">{{ old('notes', $batch->notes ?? '') }}</textarea>
                    </div>

                    <button type="submit" data-action-button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-800">
                        <x-dashboard-icon name="save" class="h-4 w-4" />
                        {{ $batch ? 'Save batch schedule' : 'Create batch schedule' }}
                    </button>

                    @if ($batch)
                        <a href="{{ route('admin.schedules.index') }}" class="block text-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel edit
                        </a>
                    @else
                        <button type="button" data-batch-dialog-close class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                    @endif
                </form>
                </div>
            </dialog>

            <!-- Active Batches List -->
            <section class="space-y-4">
                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-display text-xl font-bold text-slate-900">Configured batches</h2>
                        <p class="text-xs text-slate-500">Active batch handles incoming applicant enrollments.</p>
                    </div>
                    <a href="{{ route('admin.payment-schedules.index') }}" class="rounded-xl border border-purple-200 bg-white px-4 py-2 text-xs font-bold text-purple-700 hover:bg-purple-50">
                        Payment scheduling
                    </a>
                </div>

                @forelse ($batches as $item)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-bold text-slate-900">{{ $item->name }} {{ $item->year }}</h3>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 {{ $item->acceptsEnrollment() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $item->enrollmentStateLabel() }}</span>
                                    <span class="rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-bold text-purple-700 ring-1 ring-purple-100">{{ $item->trainingStateLabel() }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Enrollment ends {{ $item->enrollment_ends_at->format('M d, Y g:i A') }}</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.schedules.edit', $item) }}" data-dashboard-prefetch class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
                                    <x-dashboard-icon name="pencil" class="h-3.5 w-3.5" />
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.schedules.destroy', $item) }}" onsubmit="return confirm('Delete this batch schedule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" @disabled($item->applications_count > 0) class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">
                                        <x-dashboard-icon name="trash-2" class="h-3.5 w-3.5" />
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <span class="text-xs font-bold uppercase text-slate-500">AM Class</span>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $item->scheduleLabelFor('AM') }}</p>
                                <p class="text-xs text-slate-500">{{ $item->am_room ?: 'Room TBA' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <span class="text-xs font-bold uppercase text-slate-500">PM Class</span>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $item->scheduleLabelFor('PM') }}</p>
                                <p class="text-xs text-slate-500">{{ $item->pm_room ?: 'Room TBA' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <span class="text-xs font-bold uppercase text-slate-500">Applicants</span>
                                <p class="mt-1 text-xl font-bold text-slate-900">{{ $item->applications_count }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                        <p class="text-base font-bold text-slate-900">No batch schedules configured yet</p>
                        <p class="mt-1 text-xs text-slate-500">Create a batch schedule to start accepting learner enrollments.</p>
                    </div>
                @endforelse

                @if ($batches->hasPages())
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        {{ $batches->links() }}
                    </div>
                @endif
            </section>
        </section>
    </div>

    <script>
        (() => {
            const dialog = document.querySelector('[data-batch-dialog]');
            const form = document.querySelector('[data-batch-form]');

            if (!dialog || !form) return;

            // The native modal traps keyboard focus while Laravel remains in
            // charge of authorization, validation, rate limiting, and saving.
            const openDialog = () => {
                if (!dialog.open) dialog.showModal();
                requestAnimationFrame(() => form.querySelector('input:not([type="hidden"])')?.focus());
            };

            const closeDialog = () => {
                if (dialog.dataset.cancelUrl) {
                    window.location.assign(dialog.dataset.cancelUrl);
                    return;
                }

                dialog.close();
            };

            document.querySelectorAll('[data-batch-dialog-open]').forEach((button) => {
                button.addEventListener('click', openDialog);
            });

            dialog.querySelectorAll('[data-batch-dialog-close]').forEach((button) => {
                button.addEventListener('click', closeDialog);
            });

            dialog.addEventListener('cancel', (event) => {
                if (!dialog.dataset.cancelUrl) return;
                event.preventDefault();
                closeDialog();
            });

            dialog.addEventListener('click', (event) => {
                const bounds = dialog.getBoundingClientRect();
                const outside = event.clientX < bounds.left || event.clientX > bounds.right
                    || event.clientY < bounds.top || event.clientY > bounds.bottom;

                if (outside) closeDialog();
            });

            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = '{{ $batch ? 'Saving changes...' : 'Creating batch...' }}';
                });
            });

            if (dialog.dataset.autoOpen === 'true') openDialog();
        })();
    </script>
@endsection
