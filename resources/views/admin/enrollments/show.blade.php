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

        $documents = [
            'birth-certificate' => [
                'label' => 'Birth Certificate',
                'path' => $application->birth_certificate_path,
            ],
            'education-document' => [
                'label' => 'Form 137/138 or Diploma',
                'path' => $application->education_document_path,
            ],
            'good-moral-certificate' => [
                'label' => 'Good Moral Certificate',
                'path' => $application->good_moral_certificate_path,
            ],
            'id-photo' => [
                'label' => 'ID Photo',
                'path' => $application->id_photo_path,
            ],
            'signature' => [
                'label' => 'E-Signature'.($application->signature_type ? ' ('.str($application->signature_type)->headline().')' : ''),
                'path' => $application->signature_path,
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

            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-7">
                <h2 class="text-xl font-bold text-slate-900">Uploaded documents</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($documents as $key => $document)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">{{ $document['label'] }}</p>
                            @if ($document['path'])
                                <a href="{{ route('admin.enrollments.documents.show', [$application, $key]) }}" class="mt-3 inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-4 py-2 text-sm font-bold text-purple-700 hover:bg-purple-50">
                                    Download file
                                </a>
                            @else
                                <p class="mt-2 text-sm font-semibold text-red-600">Missing</p>
                            @endif
                        </div>
                    @endforeach
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
                        <p class="mt-2 text-xs leading-5 text-slate-500">A note is required when denying an application.</p>
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
@endsection
