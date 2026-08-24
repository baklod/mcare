@extends('admin.layouts.app', ['title' => 'Review Application | MCARE Admin'])

@section('content')
    @php
        $badgeClasses = [
            'profile_submitted' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'pre_enlistment' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $paymentBadgeClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $defaultDecision = in_array($application->status, $reviewableStatuses, true)
            ? $application->status
            : 'pre_enlistment';

        $documentsReadyForApproval = $pendingDocumentApprovals === [];
        $paymentReadyForApproval = $application->hasEnrollmentPaymentClearance();

        $personalFields = [
            'Full name' => trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name),
            'Email' => $application->email,
            'Contact number' => $application->contact_number,
            'Birthdate' => $application->birth_date?->format('M d, Y'),
            'Birthplace' => collect([$application->birthplace_city, $application->birthplace_province, $application->birthplace_region])->filter()->join(', '),
            'Sex' => $application->gender,
            'Civil status' => $application->civil_status,
            'Nationality' => $application->nationality,
            'Employment status' => $application->employment_status,
            'Employment type' => $application->employment_type,
        ];

        $addressFields = [
            'Street' => $application->street,
            'Barangay' => $application->barangay,
            'City/Municipality' => $application->city,
            'Province' => $application->province,
            'Region' => $application->region,
            'ZIP code' => $application->zip_code,
        ];

        $educationFields = [
            'Educational attainment' => $application->educational_attainment,
            'School name' => $application->school_name,
            'Year graduated' => $application->year_graduated,
            'Guardian name' => $application->guardian_name,
            'Guardian address' => $application->guardian_address,
        ];

        $classificationFields = [
            'Client classification' => $application->classification,
            'Disability type' => $application->disability_type,
            'Disability cause' => $application->disability_cause,
            'Scholarship package' => $application->scholarship_type,
            'Signature name' => $application->signature_name,
            'Date accomplished' => $application->date_accomplished?->format('M d, Y'),
        ];

        $documentMime = fn (?string $path) => $path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path)
            ? (\Illuminate\Support\Facades\Storage::disk('local')->mimeType($path) ?: 'application/octet-stream')
            : null;

        $documents = [
            'birth-certificate' => [
                'label' => 'Birth Certificate',
                'path' => $application->birth_certificate_path,
                'mime' => $documentMime($application->birth_certificate_path),
            ],
            'education-document' => [
                'label' => 'Form 137/138 or Diploma',
                'path' => $application->education_document_path,
                'mime' => $documentMime($application->education_document_path),
            ],
            'good-moral-certificate' => [
                'label' => 'Good Moral Certificate',
                'path' => $application->good_moral_certificate_path,
                'mime' => $documentMime($application->good_moral_certificate_path),
            ],
            'id-photo' => [
                'label' => 'ID Photo',
                'path' => $application->id_photo_path,
                'mime' => $documentMime($application->id_photo_path),
            ],
            'signature' => [
                'label' => 'E-Signature'.($application->signature_type ? ' ('.str($application->signature_type)->headline().')' : ''),
                'path' => $application->signature_path,
                'mime' => $documentMime($application->signature_path),
            ],
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.enrollments.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">
            Back to queue
        </a>
        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $badgeClasses[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
            {{ $application->statusLabel() }}
        </span>
    </div>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-8">
                <p class="text-sm font-bold uppercase text-purple-600">Caregiving NC II</p>
                <h1 class="mt-2 text-4xl font-bold leading-tight text-slate-900">
                    {{ $application->last_name }}, {{ $application->first_name }}
                </h1>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Schedule</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $application->schedule_preference }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Submitted</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $application->created_at?->format('M d, Y g:i A') }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Account status</p>
                        <p class="mt-1 font-bold text-slate-900">{{ str($application->user?->applicant_status ?? 'No account')->headline() }}</p>
                    </div>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase text-purple-600">Payment</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900">Payment selection</h2>
                    </div>
                    <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                        {{ $application->paymentStatusLabel() }}
                    </span>
                </div>
                <dl class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ([
                        'Method' => $application->payment_method ? str($application->payment_method)->headline() : 'Not selected',
                        'Amount' => $application->payment_currency.' '.number_format((float) $application->payment_amount, 2),
                        'Batch' => $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned',
                        'Class schedule' => $application->batch?->scheduleLabelFor($application->schedule_preference),
                        'Room destination' => $application->batch?->roomFor($application->schedule_preference),
                        'Enrollment deadline' => $application->batch?->enrollment_ends_at?->format('M d, Y g:i A'),
                        'Reference' => $application->payment_reference,
                        'Receipt number' => $application->payment_receipt_number,
                        'Receipt expires' => $application->effectivePaymentDeadline()?->format('M d, Y g:i A'),
                        'PayMongo reference' => $application->paymongo_checkout_reference,
                    ] as $label => $value)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 break-all text-sm font-semibold leading-6 text-slate-900">{{ filled($value) ? $value : 'Not available' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @foreach ([
                'Personal information' => $personalFields,
                'Permanent mailing address' => $addressFields,
                'Education and guardian' => $educationFields,
                'TESDA classification' => $classificationFields,
            ] as $sectionTitle => $fields)
                <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-7">
                    <h2 class="text-xl font-bold text-slate-900">{{ $sectionTitle }}</h2>
                    <dl class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($fields as $label => $value)
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="text-xs font-bold uppercase text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-semibold leading-6 text-slate-900">{{ filled($value) ? $value : 'Not provided' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            <section id="document-review" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-7">
                <div>
                    <p class="text-sm font-bold uppercase text-purple-600">Applicant documents</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">Documents and feedback</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Preview each file and record its status and feedback in one compact review area.</p>
                </div>

                <div class="mt-6 block">
                    <div aria-label="Uploaded documents" class="hidden">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Uploaded documents</h3>
                        @foreach ($documents as $key => $document)
                            <article id="document-card-{{ $key }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition-shadow hover:shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-slate-900">{{ $document['label'] }}</p>
                                    @if ($document['path'])
                                        <button type="button"
                                            class="document-preview-trigger inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-4 py-2 text-sm font-bold text-purple-700 transition hover:border-purple-300 hover:bg-purple-50 focus:outline-none focus:ring-4 focus:ring-purple-100"
                                            data-document-key="{{ $key }}"
                                            data-document-label="{{ $document['label'] }}"
                                            data-document-mime="{{ $document['mime'] ?? '' }}"
                                            data-document-url="{{ route('admin.enrollments.documents.content', [$application, $key]) }}"
                                            aria-haspopup="dialog">
                                            Preview document
                                        </button>
                                    @else
                                        <span class="text-sm font-semibold text-red-600">Missing</span>
                                    @endif
                                </div>
                                @if ($document['path'])
                                    <p class="mt-2 text-xs text-slate-500">Private preview · access is logged</p>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.enrollments.documents.review', $application) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-purple-600">Document feedback</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-950">Tell the applicant what is accepted or lacking</h3>
                        </div>
                        @foreach($documents as $key => $document)
                            @php
                                $storedReview = data_get($application->document_review, $key, []);
                                $defaultDocumentStatus = $document['path'] ? 'unreviewed' : 'missing';
                            @endphp
                            <div class="grid items-start gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 md:grid-cols-[minmax(14rem,0.8fr)_minmax(18rem,1.2fr)]">
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label for="review-{{ $key }}" class="text-sm font-bold text-slate-900">{{ $document['label'] }}</label>
                                        @if ($document['path'])
                                            <button type="button"
                                                class="document-preview-trigger inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 transition hover:border-purple-300 hover:bg-purple-50 focus:outline-none focus:ring-4 focus:ring-purple-100"
                                                data-document-key="{{ $key }}"
                                                data-document-label="{{ $document['label'] }}"
                                                data-document-mime="{{ $document['mime'] ?? '' }}"
                                                data-document-url="{{ route('admin.enrollments.documents.content', [$application, $key]) }}"
                                                aria-haspopup="dialog">
                                                Preview
                                            </button>
                                        @else
                                            <span class="text-xs font-bold text-red-600">Missing</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $document['path'] ? 'Private preview; access is logged.' : 'Applicant must upload this file.' }}</p>
                                </div>
                                <div>
                                    <label for="review-{{ $key }}" class="sr-only">Review status for {{ $document['label'] }}</label>
                                    <select id="review-{{ $key }}" name="documents[{{ $key }}][status]" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                                    <option value="unreviewed" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'unreviewed')>Not reviewed</option>
                                    <option value="accepted" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'accepted')>Accepted</option>
                                    <option value="replace" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'replace')>Needs replacement</option>
                                    <option value="missing" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'missing')>Missing</option>
                                </select>
                                    <label for="note-{{ $key }}" class="mt-3 block text-xs font-bold uppercase tracking-wide text-slate-500">Feedback or problem found</label>
                                    <textarea id="note-{{ $key }}" name="documents[{{ $key }}][note]" rows="2" maxlength="500" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-purple-300 focus:ring-4 focus:ring-purple-100" placeholder="Example: Image is blurry; upload a clear copy showing all corners.">{{ old("documents.$key.note", $storedReview['note'] ?? '') }}</textarea>
                                </div>
                            </div>
                        @endforeach
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-purple-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-200">Save document feedback</button>
                        @if($application->documents_reviewed_at)<p class="text-xs text-slate-500">Last reviewed {{ $application->documents_reviewed_at->format('M d, Y g:i A') }} by {{ $application->documentReviewer?->name ?? 'Admin' }}</p>@endif
                    </form>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-purple-100 bg-white p-6 shadow-xl shadow-purple-100/40">
                <p class="text-sm font-bold uppercase text-purple-600">Official TESDA form</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Registration Form MIS 03-01</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">Applicant answers, ID photo, and e-signature are placed on the original two-page TESDA form.</p>
                <div class="mt-5 grid grid-cols-1 gap-3">
                    <a href="{{ route('admin.enrollments.tesda-form', [$application, 'disposition' => 'inline']) }}" target="_blank" rel="noopener" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                        Preview / Print form
                    </a>
                    <a href="{{ route('admin.enrollments.tesda-form', [$application, 'disposition' => 'attachment']) }}" class="inline-flex w-full items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-3 text-sm font-bold text-purple-700 hover:bg-purple-50">
                        Download PDF
                    </a>
                </div>
            </section>

            <section class="rounded-3xl border border-purple-100 bg-white p-6 shadow-xl shadow-purple-100/40">
                <p class="text-sm font-bold uppercase text-purple-600">Review decision</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Update application</h2>

                <div class="mt-5 space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold text-slate-700">Required documents</span>
                        <span class="text-right font-bold {{ $documentsReadyForApproval ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $documentsReadyForApproval ? 'All accepted' : count($pendingDocumentApprovals).' pending' }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold text-slate-700">Enrollment payment</span>
                        <span class="text-right font-bold {{ $paymentReadyForApproval ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $paymentReadyForApproval ? 'Verified' : 'Not verified' }}
                        </span>
                    </div>
                    @if (! $documentsReadyForApproval)
                        <p class="pt-1 text-xs leading-5 text-slate-500">Pending: {{ implode(', ', $pendingDocumentApprovals) }}. Save document feedback separately before approving.</p>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-700">
                        Please correct the review form before saving.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.enrollments.update', $application) }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="status" class="mb-2 block text-sm font-semibold text-slate-800">Decision</label>
                        <select id="status" name="status" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            @foreach ($reviewableStatuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $defaultDecision) === $status)>{{ $statuses[$status] }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin_notes" class="mb-2 block text-sm font-semibold text-slate-800">Admin notes</label>
                        <textarea id="admin_notes" name="admin_notes" rows="6" maxlength="2000" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100" placeholder="Add document requirements, pre-enlistment instructions, or denial reason.">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                        <p class="mt-2 text-xs leading-5 text-slate-500">A note is required when denying an application. Approval requires verified payment and all five required documents accepted.</p>
                        @error('admin_notes') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">
                        Save decision
                    </button>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase text-purple-600">Review trail</p>
                <div class="mt-5 space-y-4 text-sm leading-6">
                    <div>
                        <p class="font-bold text-slate-900">Reviewed by</p>
                        <p class="text-slate-500">{{ $application->reviewer?->name ?? 'Not reviewed yet' }}</p>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900">Reviewed at</p>
                        <p class="text-slate-500">{{ $application->reviewed_at?->format('M d, Y g:i A') ?? 'Not reviewed yet' }}</p>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900">Privacy consent</p>
                        <p class="text-slate-500">{{ $application->privacy_consent ? 'Accepted' : 'Not accepted' }}</p>
                    </div>
                </div>
            </section>
        </aside>
    </section>

    <div id="document-preview-modal"
        class="fixed inset-0 z-[100] hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="document-preview-title"
        aria-hidden="true">
        <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" data-document-modal-close></div>
        <div class="relative mx-auto flex h-full w-full max-w-6xl flex-col p-3 sm:p-6">
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-white/15 bg-slate-950 shadow-2xl">
                <header class="flex shrink-0 items-center justify-between gap-4 border-b border-white/10 px-4 py-3 text-white sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-purple-300">Private document preview</p>
                        <h2 id="document-preview-title" class="truncate text-base font-bold sm:text-lg">Document</h2>
                    </div>
                    <button type="button" data-document-modal-close class="inline-flex shrink-0 items-center justify-center rounded-full border border-white/20 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-purple-300/40">
                        Close
                    </button>
                </header>
                <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-900">
                    <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-4 overflow-hidden opacity-[0.12]" aria-hidden="true">
                        @for($i = 0; $i < 8; $i++)
                            <span class="grid -rotate-12 place-items-center whitespace-nowrap text-xs font-black uppercase tracking-widest text-white sm:text-sm">ADMIN REVIEW · {{ $application->email }} · {{ now()->format('Y-m-d H:i') }}</span>
                        @endfor
                    </div>
                    <iframe id="document-preview-frame" class="relative z-10 hidden h-full min-h-[70vh] w-full bg-white" title="Document preview"></iframe>
                    <div id="document-preview-image-wrap" class="relative z-10 hidden h-full overflow-auto bg-slate-100 p-4 sm:p-8">
                        <img id="document-preview-image" src="" alt="" class="mx-auto h-auto max-w-full select-none object-contain" draggable="false">
                    </div>
                    <div id="document-preview-unavailable" class="relative z-10 hidden h-full min-h-[70vh] place-items-center bg-white p-8 text-center">
                        <p class="max-w-md font-semibold leading-6 text-slate-700">This file type cannot be previewed in the browser. Ask the applicant to upload a PDF, JPG, or PNG.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('document-preview-modal');
            if (!modal) return;

            const frame = document.getElementById('document-preview-frame');
            const imageWrap = document.getElementById('document-preview-image-wrap');
            const image = document.getElementById('document-preview-image');
            const unavailable = document.getElementById('document-preview-unavailable');
            const title = document.getElementById('document-preview-title');
            let activeTrigger = null;
            let previousScrollY = 0;

            const resetViewer = () => {
                frame.classList.add('hidden');
                imageWrap.classList.add('hidden');
                unavailable.classList.add('hidden');
                frame.removeAttribute('src');
                image.removeAttribute('src');
                image.removeAttribute('alt');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                resetViewer();
                window.scrollTo({ top: previousScrollY, behavior: 'auto' });
                activeTrigger?.focus({ preventScroll: true });
            };

            const openModal = (trigger) => {
                activeTrigger = trigger;
                previousScrollY = window.scrollY;
                const label = trigger.dataset.documentLabel || 'Document';
                const url = trigger.dataset.documentUrl;
                const mime = trigger.dataset.documentMime || '';
                title.textContent = label;
                resetViewer();

                if (mime === 'application/pdf' || url.toLowerCase().endsWith('.pdf')) {
                    frame.src = `${url}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;
                    frame.title = `${label} preview`;
                    frame.classList.remove('hidden');
                } else if (mime.startsWith('image/')) {
                    image.src = url;
                    image.alt = `${label} preview`;
                    imageWrap.classList.remove('hidden');
                } else {
                    unavailable.classList.remove('hidden');
                    unavailable.classList.add('grid');
                }

                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                modal.querySelector('[data-document-modal-close]')?.focus();
            };

            document.querySelectorAll('.document-preview-trigger').forEach((trigger) => {
                trigger.addEventListener('click', () => openModal(trigger));
            });
            modal.querySelectorAll('[data-document-modal-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>
@endsection
