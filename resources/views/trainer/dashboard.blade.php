@extends('trainer.layouts.app', ['title' => 'Trainer Dashboard'])

@section('content')
    @php
        $batchLabel = $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'No active batch';
    @endphp

    <section id="dashboard" class="space-y-6">
        <div class="dashboard-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="dashboard-pill bg-white/15 text-white ring-white/20">Authorized trainer</span>
                    <h1 class="mt-4">Welcome, {{ auth()->user()->name }}</h1>
                    <p>
                        Manage Caregiving NC II modules, monitor trainees by schedule, and keep your class workflow aligned with the active batch.
                    </p>
                </div>
                <div class="rounded-2xl bg-white/15 px-5 py-4 ring-1 ring-white/20">
                    <p class="text-xs font-black uppercase tracking-wide text-white/65">Current batch</p>
                    <p class="mt-1 font-display text-2xl font-black text-white">{{ $batchLabel }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            @foreach ([
                'Uploaded modules' => $stats['modules'],
                'Active trainees' => $stats['trainees'],
                'AM trainees' => $stats['am'],
                'PM trainees' => $stats['pm'],
            ] as $label => $value)
                <div class="dashboard-stat">
                    <div>
                        <p class="dashboard-stat-label">{{ $label }}</p>
                        <p class="dashboard-stat-value">{{ $value }}</p>
                        <p class="dashboard-stat-help">Caregiving NC II active batch</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="upload" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[420px_1fr]">
        <div class="dashboard-panel">
            <p class="dashboard-section-kicker">Upload module</p>
            <h2 class="dashboard-section-title">Publish learning material</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Files are stored privately so content-security rules, activity logs, and future watermarking can be applied before trainee access.
            </p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-700">
                    Please correct the module upload fields.
                </div>
            @endif

            <form method="POST" action="{{ route('trainer.modules.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5" data-single-action>
                @csrf
                <div>
                    <label for="title" class="mb-2 block text-sm font-semibold text-slate-800">Module title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required placeholder="Example: 03 - Infection Control" class="form-field">
                    @error('title') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-semibold text-slate-800">Description / objectives</label>
                    <textarea id="description" name="description" rows="4" required class="form-field leading-6">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="module_file" class="mb-2 block text-sm font-semibold text-slate-800">Module file</label>
                    <input id="module_file" name="module_file" type="file" required accept=".pdf,.doc,.docx,.ppt,.pptx" class="form-field file:mr-4 file:rounded-full file:border-0 file:bg-purple-50 file:px-4 file:py-2 file:text-sm file:font-bold file:text-purple-700">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Accepted: PDF, DOC, DOCX, PPT, PPTX. Maximum 20MB.</p>
                    @error('module_file') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" data-action-button class="primary-action w-full">
                    Publish module
                </button>
            </form>
        </div>

        <section id="modules" class="dashboard-table-wrap">
            <div class="border-b border-slate-100 p-6">
                <p class="dashboard-section-kicker">My modules</p>
                <h2 class="dashboard-section-title">Published materials</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Module</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Batch</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Uploaded</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($modules as $module)
                            <tr class="hover:bg-purple-50/40">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ $module->title }}</p>
                                    <p class="mt-1 max-w-lg text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $module->original_file_name }} - {{ number_format(($module->file_size ?: 0) / 1024, 1) }} KB</p>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-600">{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'All trainees' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $module->published_at?->format('M d, Y g:i A') ?? 'Draft' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('trainer.modules.download', $module) }}" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-4 py-2 text-sm font-bold text-purple-700 hover:bg-purple-50">
                                        Download
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <p class="font-bold text-slate-900">No modules uploaded yet</p>
                                    <p class="mt-2 text-sm text-slate-500">Your published training materials will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($modules->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $modules->links() }}
                </div>
            @endif
        </section>
    </section>

    <section id="trainees" class="mt-8 dashboard-panel">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">My trainees</p>
                <h2 class="dashboard-section-title">AM and PM class lists</h2>
            </div>
            <span class="rounded-full bg-purple-50 px-4 py-2 text-sm font-bold text-purple-700 ring-1 ring-purple-100">{{ $batchLabel }}</span>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach (['AM' => $amTrainees, 'PM' => $pmTrainees] as $schedule => $trainees)
                <div class="overflow-hidden rounded-3xl border border-slate-100">
                    <div class="bg-slate-50 px-5 py-4">
                        <p class="text-sm font-black text-slate-900">
                            {{ $schedule }} Class
                            <span class="ml-2 text-xs font-bold text-slate-500">{{ $activeBatch?->scheduleLabelFor($schedule) ?? 'Schedule TBA' }}</span>
                        </p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($trainees as $trainee)
                            <article class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-[1fr_auto]">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $trainee->email }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $trainee->contact_number }}</p>
                                </div>
                                <div class="sm:text-right">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">Approved</span>
                                    <p class="mt-2 text-xs text-slate-500">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch' }}</p>
                                </div>
                            </article>
                        @empty
                            <div class="p-8 text-center">
                                <p class="font-bold text-slate-900">No {{ $schedule }} trainees yet</p>
                                <p class="mt-2 text-sm text-slate-500">Approved applicants for this schedule will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="schedule" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="dashboard-panel border-amber-100">
            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Announcements</p>
            <h2 class="mt-2 font-display text-2xl font-black text-slate-900">Trainer notices</h2>
            <div class="mt-5 space-y-3">
                @forelse ($announcements as $announcement)
                    <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900">{{ $announcement->title }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $announcement->message }}</p>
                        <p class="mt-2 text-xs font-bold text-amber-700">{{ $announcement->posted_at?->format('M d, Y g:i A') ?? 'Recently posted' }}</p>
                    </article>
                @empty
                    <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900">No announcements yet</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Admin/trainer notices can be added here once announcement creation is enabled.</p>
                    </article>
                @endforelse
            </div>
        </aside>

        <section class="dashboard-panel">
            <p class="dashboard-section-kicker">My schedule</p>
            <h2 class="dashboard-section-title">Batch class calendar</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-3xl bg-slate-50 p-5">
                    <p class="text-xs font-bold uppercase text-slate-500">AM class</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ $activeBatch?->scheduleLabelFor('AM') ?? 'Schedule TBA' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $activeBatch?->am_room ?: 'Room TBA' }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-5">
                    <p class="text-xs font-bold uppercase text-slate-500">PM class</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ $activeBatch?->scheduleLabelFor('PM') ?? 'Schedule TBA' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $activeBatch?->pm_room ?: 'Room TBA' }}</p>
                </div>
                <div class="rounded-3xl bg-purple-50 p-5 ring-1 ring-purple-100 md:col-span-2">
                    <p class="text-xs font-bold uppercase text-purple-700">Enrollment deadline</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ $activeBatch?->enrollment_ends_at?->format('M d, Y g:i A') ?? 'Deadline TBA' }}</p>
                </div>
            </div>
        </section>
    </section>

    <script>
        document.querySelectorAll('[data-single-action]').forEach((form) => {
            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = 'Publishing module...';
                });
            });
        });
    </script>
@endsection
