@extends('trainee.layouts.app', ['title' => 'Trainee Dashboard'])

@section('content')
    @php
        $fullName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
        $batchLabel = $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned';
        $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
        $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room TBA';
        $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
        $documents = [
            'birth-certificate' => ['label' => 'Birth Certificate', 'path' => $application->birth_certificate_path],
            'education-document' => ['label' => 'Form 137/138 or Diploma', 'path' => $application->education_document_path],
            'good-moral-certificate' => ['label' => 'Good Moral Certificate', 'path' => $application->good_moral_certificate_path],
            'id-photo' => ['label' => 'ID Photo', 'path' => $application->id_photo_path],
            'signature' => ['label' => 'E-Signature', 'path' => $application->signature_path],
        ];
    @endphp

    <section id="dashboard" class="space-y-6">
        <div class="dashboard-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Approved trainee</span>
                    <h1 class="mt-4">Welcome back, {{ $application->first_name }}</h1>
                    <p>
                        Continue your Caregiving NC II training with your approved batch schedule, modules, payment status, and submitted records in one place.
                    </p>
                </div>
                <div class="rounded-xl bg-purple-50 px-5 py-4 ring-1 ring-purple-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Current batch</p>
                    <p class="mt-1 font-display text-2xl font-extrabold text-slate-950">{{ $batchLabel }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Training progress</p>
                    <p class="dashboard-stat-value">{{ $stats['progress'] }}%</p>
                    <p class="dashboard-stat-help">Calculated from your server-recorded module activity.</p>
                </div>
            </div>
            <div class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Available modules</p>
                    <p class="dashboard-stat-value">{{ $stats['modules'] }}</p>
                    <p class="dashboard-stat-help">Published by trainer for your batch.</p>
                </div>
            </div>
            <div class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Documents</p>
                    <p class="dashboard-stat-value">{{ $stats['documents'] }}/5</p>
                    <p class="dashboard-stat-help">TESDA registration files on record.</p>
                </div>
            </div>
            <div class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Payment</p>
                    <p class="mt-2 text-lg font-black leading-tight text-slate-900">{{ $stats['payment'] }}</p>
                    <p class="dashboard-stat-help">{{ $deadline ? 'Deadline '.$deadline->format('M d, Y g:i A') : 'Deadline TBA' }}</p>
                </div>
            </div>
        </div>
    </section>

    <section id="modules" class="mt-8 dashboard-panel">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">My modules</p>
                <h2 class="dashboard-section-title">Learning materials</h2>
            </div>
            <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Private LMS access</span>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($modules as $module)
                @php
                    $moduleProgress = $progressByModule->get($module->id);
                @endphp
                <article class="dashboard-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-purple-600">{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'General module' }}</p>
                            <h3 class="mt-2 font-display text-xl font-black leading-tight text-slate-900">{{ $module->title }}</h3>
                        </div>
                        <span class="dashboard-pill {{ $moduleProgress?->status === 'completed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($moduleProgress ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-50 text-slate-600 ring-slate-100') }}">{{ $moduleProgress ? str($moduleProgress->status)->headline() : 'Not started' }}</span>
                    </div>
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                    <div class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <x-user-avatar :user="$module->trainer" :name="$module->trainer?->name ?? 'MCARE Trainer'" class="grid h-7 w-7 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800" />
                        <span class="truncate">Trainer: {{ $module->trainer?->name ?? 'MCARE Trainer' }}</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-purple-600" style="width: {{ $moduleProgress?->progress_percent ?? 0 }}%"></div></div>
                    <p class="mt-2 text-xs font-bold text-slate-500">{{ $moduleProgress?->progress_percent ?? 0 }}% recorded</p>
                    <a href="{{ route('trainee.modules.show', $module) }}" class="secondary-action mt-5 w-full">
                        Open protected viewer
                    </a>
                </article>
            @empty
                <div class="dashboard-card p-10 text-center md:col-span-2 xl:col-span-3">
                    <p class="font-display text-xl font-black text-slate-900">No modules yet</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Your trainer's published materials will appear here once available.</p>
                </div>
            @endforelse
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
                        <p class="mt-2 text-sm leading-6 text-slate-600">Class announcements will appear here.</p>
                    </article>
                @endforelse
            </div>
        </aside>

        <section class="dashboard-panel">
            <p class="dashboard-section-kicker">My schedule</p>
            <h2 class="dashboard-section-title">Class details</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Preferred class</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900">{{ $application->schedule_preference }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Schedule</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900">{{ $scheduleLabel }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $roomLabel }}</p>
                </div>
                <div class="rounded-2xl bg-purple-50 p-5 ring-1 ring-purple-100 md:col-span-3">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment/payment deadline</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'Deadline TBA' }}</p>
                </div>
            </div>
        </section>
    </section>

    <section id="payments" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        <div class="dashboard-panel">
            <p class="dashboard-section-kicker">Billing and payments</p>
            <h2 class="dashboard-section-title">Payment summary</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Payment method</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $application->payment_method ? str($application->payment_method)->headline() : 'Not selected' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $application->payment_currency }} {{ number_format((float) $application->payment_amount, 2) }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</p>
                    <p class="mt-2 break-all text-lg font-black text-slate-900">{{ $application->payment_receipt_number ?: $application->paymongo_checkout_reference ?: $application->payment_reference ?: 'Reference pending' }}</p>
                </div>
            </div>
        </div>

        <aside class="dashboard-panel">
            <p class="text-xs font-black uppercase tracking-wide text-purple-600">Need payment action?</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Use the payment page to review your current online/on-site payment status or receipt.</p>
            <a href="{{ route('payment.show') }}" class="primary-action mt-5 w-full">Open payment page</a>
        </aside>
    </section>

    <section id="documents" class="mt-8 dashboard-panel">
        <p class="dashboard-section-kicker">My documents</p>
        <h2 class="dashboard-section-title">Submitted registration files</h2>
        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($documents as $documentKey => $document)
                @php
                    $documentFeedback = data_get($application->document_review, $documentKey, []);
                @endphp
                <article class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                    <p class="font-bold text-slate-900">{{ $document['label'] }}</p>
                    @if ($document['path'])
                        <span class="dashboard-pill mt-4 bg-emerald-50 text-emerald-700 ring-emerald-100">On file</span>
                    @else
                        <span class="dashboard-pill mt-4 bg-red-50 text-red-700 ring-red-100">Missing</span>
                    @endif
                    @if($documentFeedback)
                        <p class="mt-3 text-xs font-black uppercase {{ ($documentFeedback['status'] ?? '') === 'accepted' ? 'text-emerald-700' : 'text-amber-700' }}">{{ str($documentFeedback['status'] ?? 'unreviewed')->headline() }}</p>
                        @if($documentFeedback['note'] ?? null)<p class="mt-1 text-xs leading-5 text-slate-600">{{ $documentFeedback['note'] }}</p>@endif
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endsection
