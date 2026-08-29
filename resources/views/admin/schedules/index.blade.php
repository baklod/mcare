@extends('admin.layouts.app', ['title' => 'Batch Schedules | MCARE Admin'])

@section('content')
    @php
        $batch = $editingBatch;
        $formAction = $batch ? route('admin.schedules.update', $batch) : route('admin.schedules.store');
        $batchFormHasErrors = collect([
            'training_program_id', 'name', 'year', 'is_active', 'show_on_enrollment_page', 'is_continuous_enrollment', 'enrollment_starts_at', 'enrollment_ends_at',
            'training_starts_at', 'training_ends_at', 'am_days', 'am_start_time',
            'am_end_time', 'am_room', 'pm_days', 'pm_start_time', 'pm_end_time',
            'pm_room', 'trainer_id', 'notes',
        ])->contains(fn (string $field): bool => $errors->has($field));
        $programErrorId = (string) old('program_id', '');
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

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-lg font-bold text-slate-900">Training program catalog</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Programs provide the public name, fee, and required downpayment used by their batches.</p>
                </div>
                <span class="w-fit rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">{{ $programs->count() }} configured</span>
            </div>

            @if ($errors->program->any())
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                    <p class="font-bold">Please correct the program details:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->program->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <form method="POST" action="{{ route('admin.training-programs.store') }}" class="rounded-xl border border-purple-100 bg-purple-50/50 p-4" data-single-action>
                    @csrf
                    <input type="hidden" name="program_id" value="">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Add a program</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="new-program-name" class="mb-1 block text-xs font-semibold text-slate-700">Program name</label>
                            <input id="new-program-name" name="program_name" value="{{ $programErrorId === '' ? old('program_name') : '' }}" required placeholder="Caregiving NC III" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="new-program-code" class="mb-1 block text-xs font-semibold text-slate-700">Program code</label>
                            <input id="new-program-code" name="program_code" value="{{ $programErrorId === '' ? old('program_code') : '' }}" required placeholder="CAREGIVING-NC-III" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm uppercase">
                        </div>
                        <div>
                            <label for="new-program-fee" class="mb-1 block text-xs font-semibold text-slate-700">Total fee</label>
                            <input id="new-program-fee" name="program_total_fee" type="number" min="1" step="0.01" value="{{ $programErrorId === '' ? old('program_total_fee', '22000.00') : '22000.00' }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="new-program-downpayment" class="mb-1 block text-xs font-semibold text-slate-700">Required downpayment</label>
                            <input id="new-program-downpayment" name="program_downpayment" type="number" min="1" step="0.01" value="{{ $programErrorId === '' ? old('program_downpayment', '2000.00') : '2000.00' }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        </div>
                    </div>
                    <textarea name="program_description" rows="2" placeholder="Short public description (optional)" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">{{ $programErrorId === '' ? old('program_description') : '' }}</textarea>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700"><input type="checkbox" name="program_is_active" value="1" @checked($programErrorId === '' ? old('program_is_active', true) : true) class="rounded border-slate-300 text-purple-600"> Active program</label>
                        <button type="submit" class="rounded-lg bg-purple-700 px-4 py-2 text-xs font-bold text-white hover:bg-purple-800">Add program</button>
                    </div>
                </form>

                <div class="max-h-80 space-y-3 overflow-y-auto pr-1">
                    @foreach ($programs as $program)
                        @php($useProgramOld = $programErrorId === (string) $program->id)
                        <form method="POST" action="{{ route('admin.training-programs.update', $program) }}" class="rounded-xl border border-slate-200 p-4" data-single-action>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="program_id" value="{{ $program->id }}">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input name="program_name" value="{{ $useProgramOld ? old('program_name') : $program->name }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-900" aria-label="Program name">
                                <input name="program_code" value="{{ $useProgramOld ? old('program_code') : $program->code }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase" aria-label="Program code">
                                <input name="program_total_fee" type="number" min="1" step="0.01" value="{{ $useProgramOld ? old('program_total_fee') : $program->total_program_fee }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm" aria-label="Total program fee">
                                <input name="program_downpayment" type="number" min="1" step="0.01" value="{{ $useProgramOld ? old('program_downpayment') : $program->downpayment_amount }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm" aria-label="Required downpayment">
                            </div>
                            <textarea name="program_description" rows="2" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" aria-label="Program description">{{ $useProgramOld ? old('program_description') : $program->description }}</textarea>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700"><input type="checkbox" name="program_is_active" value="1" @checked($useProgramOld ? old('program_is_active') : $program->is_active) class="rounded border-slate-300 text-purple-600"> Active</label>
                                <div class="flex items-center gap-3"><span class="text-[11px] font-semibold text-slate-500">{{ $program->batches_count }} batches</span><button type="submit" class="rounded-lg border border-purple-200 px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">Save</button></div>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </section>

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

                @if ($batchFormHasErrors || $errors->any())
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800 space-y-1.5">
                        <p class="font-bold">Please correct the schedule fields before saving:</p>
                        <ul class="list-disc pl-5 space-y-0.5 font-medium">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $formAction }}" class="mt-5 space-y-4" data-single-action data-batch-form>
                    @csrf
                    @if ($batch)
                        @method('PATCH')
                    @endif

                    <label class="flex items-start gap-3 rounded-xl border border-purple-200 bg-purple-50 p-4">
                        <input type="hidden" name="is_continuous_enrollment" value="0">
                        <input type="checkbox" name="is_continuous_enrollment" value="1" @checked(old('is_continuous_enrollment', $batch?->is_continuous_enrollment ?? true)) class="mt-1 rounded border-purple-300 text-purple-700 focus:ring-purple-600">
                        <span><strong class="block text-sm text-purple-950">Continuous enrollment</strong><span class="mt-1 block text-xs leading-5 text-purple-800">Keep this latest active batch open while training is already in progress. New trainees receive only its currently active module.</span></span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="training_program_id" class="mb-1 block text-xs font-semibold text-slate-700">Training program</label>
                            <select id="training_program_id" name="training_program_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <option value="">Select a program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" @selected((string) old('training_program_id', $batch->training_program_id ?? '') === (string) $program->id)>{{ $program->name }}{{ $program->is_active ? '' : ' (inactive)' }}</option>
                                @endforeach
                            </select>
                            @error('training_program_id') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
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

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-700 cursor-pointer">
                            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $batch->is_active ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                            <span>Enable this batch for enrollment</span>
                        </label>
                        <label class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-medium text-emerald-900 cursor-pointer">
                            <input name="show_on_enrollment_page" type="checkbox" value="1" @checked(old('show_on_enrollment_page', $batch->show_on_enrollment_page ?? false)) class="mt-0.5 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                            <span><strong class="block">Show on enrollment page</strong><span class="mt-1 block font-normal text-emerald-800">Public only while active and inside its enrollment window.</span></span>
                        </label>
                        <div>
                            <label for="trainer_id" class="mb-1 block text-xs font-semibold text-slate-700">Assigned trainer</label>
                            <select id="trainer_id" name="trainer_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <option value="">Needs trainer assignment</option>
                                @foreach($trainers as $trainer)
                                    <option value="{{ $trainer->id }}" @selected((string) old('trainer_id', $batch->trainer_id ?? '') === (string) $trainer->id)>
                                        {{ $trainer->name }} ({{ $trainer->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('trainer_id') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="enrollment_starts_at" class="mb-1 block text-xs font-semibold text-slate-700">Enrollment starts</label>
                            <input id="enrollment_starts_at" name="enrollment_starts_at" type="datetime-local" value="{{ old('enrollment_starts_at', $batch?->enrollment_starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('enrollment_starts_at') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="enrollment_ends_at" class="mb-1 block text-xs font-semibold text-slate-700">Enrollment deadline</label>
                            <input id="enrollment_ends_at" name="enrollment_ends_at" type="datetime-local" value="{{ old('enrollment_ends_at', $batch?->enrollment_ends_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <p class="mt-1 text-[11px] text-slate-500">Optional when continuous enrollment is enabled.</p>
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
                            @error('am_days') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="am_start_time" class="mb-1 block text-xs font-medium text-slate-600">Starts</label>
                                <input id="am_start_time" name="am_start_time" type="time" value="{{ old('am_start_time', $batch?->am_start_time ? substr($batch->am_start_time, 0, 5) : '08:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                @error('am_start_time') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="am_end_time" class="mb-1 block text-xs font-medium text-slate-600">Ends</label>
                                <input id="am_end_time" name="am_end_time" type="time" value="{{ old('am_end_time', $batch?->am_end_time ? substr($batch->am_end_time, 0, 5) : '12:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                @error('am_end_time') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <input name="am_room" type="text" value="{{ old('am_room', $batch->am_room ?? '') }}" placeholder="Room / Skills Lab" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        @error('am_room') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- PM Class Details -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">PM Class Block</span>
                        <div>
                            <label for="pm_days" class="mb-1 block text-xs font-medium text-slate-600">Days (e.g. TTH)</label>
                            <input id="pm_days" name="pm_days" type="text" value="{{ old('pm_days', $batch->pm_days ?? 'TTH') }}" required placeholder="TTH" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            @error('pm_days') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="pm_start_time" class="mb-1 block text-xs font-medium text-slate-600">Starts</label>
                                <input id="pm_start_time" name="pm_start_time" type="time" value="{{ old('pm_start_time', $batch?->pm_start_time ? substr($batch->pm_start_time, 0, 5) : '13:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                @error('pm_start_time') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="pm_end_time" class="mb-1 block text-xs font-medium text-slate-600">Ends</label>
                                <input id="pm_end_time" name="pm_end_time" type="time" value="{{ old('pm_end_time', $batch?->pm_end_time ? substr($batch->pm_end_time, 0, 5) : '17:00') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                @error('pm_end_time') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <input name="pm_room" type="text" value="{{ old('pm_room', $batch->pm_room ?? '') }}" placeholder="Room / Lecture Room" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        @error('pm_room') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
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
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100">{{ $item->program?->name ?? 'Program not assigned' }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 {{ $item->acceptsEnrollment() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $item->enrollmentStateLabel() }}</span>
                                    <span class="rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-bold text-purple-700 ring-1 ring-purple-100">{{ $item->trainingStateLabel() }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 {{ $item->show_on_enrollment_page ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">{{ $item->show_on_enrollment_page ? 'Shown publicly' : 'Hidden from enrollment' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->is_continuous_enrollment ? 'No enrollment deadline' : 'Enrollment ends '.$item->enrollment_ends_at?->format('M d, Y g:i A') }}</p>
                                <p class="mt-1 text-xs font-semibold {{ $item->trainer ? 'text-violet-700' : 'text-amber-700' }}">
                                    Trainer: {{ $item->trainer?->name ?? 'Needs assignment' }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.schedules.edit', $item) }}" data-dashboard-prefetch class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
                                    <x-dashboard-icon name="pencil" class="h-3.5 w-3.5" />
                                    Edit
                                </a>
                                @php($batchHasRelatedRecords = ($item->applications_count + $item->modules_count + $item->announcements_count + $item->quizzes_count + $item->official_documents_count + $item->document_exports_count) > 0)
                                <form method="POST" action="{{ route('admin.schedules.destroy', $item) }}" data-confirm="{{ $batchHasRelatedRecords ? 'This batch has related records and cannot be deleted.' : 'Delete this batch schedule? This cannot be undone.' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" @disabled($batchHasRelatedRecords) class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" title="{{ $batchHasRelatedRecords ? 'Delete is disabled while related records exist.' : 'Delete batch schedule' }}">
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
                                @if($batchHasRelatedRecords)<p class="mt-1 text-xs font-semibold text-amber-700">Deletion protected by related records.</p>@endif
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
