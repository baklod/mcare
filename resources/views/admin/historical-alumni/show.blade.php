@extends('admin.layouts.app', ['title' => 'Review alumni claim | MCARE Admin'])

@section('content')
    @php
        $reviewErrors = $errors->getBag('historicalClaimReview');
        $badgeClasses = [
            \App\Models\HistoricalAlumniClaim::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            \App\Models\HistoricalAlumniClaim::STATUS_REJECTED => 'bg-red-50 text-red-700 ring-red-100',
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_EMAIL => 'bg-amber-50 text-amber-700 ring-amber-100',
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_ONSITE => 'bg-purple-50 text-purple-700 ring-purple-100',
        ];
        $address = collect([
            $claim->street,
            $claim->barangay,
            $claim->city,
            $claim->province,
            $claim->region,
            $claim->zip_code,
        ])->filter()->implode(', ');
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.historical-alumni.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">Back to alumni claims</a>
        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $badgeClasses[$claim->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">{{ $claim->statusLabel() }}</span>
    </div>

    @if ($errors->has('historical_alumni'))
        <p class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">{{ $errors->first('historical_alumni') }}</p>
    @endif
    @if ($reviewErrors->any())
        <p class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">{{ $reviewErrors->first() }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex min-w-0 items-center gap-4">
                    <x-user-avatar :user="$claim->user" class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800" />
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Historical alumni claim</p>
                        <h2 class="mt-1 truncate text-2xl font-bold text-slate-950">{{ $claim->user->name }}</h2>
                        <p class="truncate text-sm text-slate-500">{{ $claim->user->email }}</p>
                    </div>
                </div>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'Email status' => $claim->user->hasVerifiedEmail() ? 'Verified' : 'Pending verification',
                        'Contact number' => $claim->contact_number,
                        'Birth date' => $claim->birth_date?->format('M d, Y'),
                        'Gender' => $claim->gender,
                        'Address' => $address ?: '—',
                        'Educational attainment' => $claim->educational_attainment,
                        'School' => $claim->school_name,
                        'Year graduated' => $claim->education_year_graduated,
                    ] as $label => $value)
                        <div @class(['sm:col-span-2' => $label === 'Address'])>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold {{ $label === 'Email status' && ! $claim->user->hasVerifiedEmail() ? 'text-amber-700' : 'text-slate-900' }}">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Previous MCARE training</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'Completion year' => $claim->training_completion_year,
                        'Old batch' => $claim->historical_batch_name ?: 'Not known',
                        'Training schedule' => $claim->training_schedule,
                        'Record presented' => str($claim->evidence_type)->headline(),
                        'COTC number' => $claim->certificate_number ?: 'Not provided',
                        'TOR reference' => $claim->tor_reference ?: 'Not provided',
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($claim->evidence_document_path)
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a class="secondary-action text-xs" target="_blank" rel="noopener" href="{{ route('admin.historical-alumni.evidence', [$claim, 'page' => 1]) }}">View evidence page 1</a>
                        <a class="secondary-action text-xs" href="{{ route('admin.historical-alumni.evidence', [$claim, 'page' => 1, 'disposition' => 'attachment']) }}">Download</a>
                        @if ($claim->evidence_document_page_2_path)
                            <a class="secondary-action text-xs" target="_blank" rel="noopener" href="{{ route('admin.historical-alumni.evidence', [$claim, 'page' => 2]) }}">View page 2</a>
                        @endif
                    </div>
                @else
                    <p class="mt-5 text-sm text-slate-500">No optional document preview was uploaded. Originals must still be inspected on-site.</p>
                @endif
            </section>
        </div>

        <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">On-site verification</p>
            @if ($claim->status !== \App\Models\HistoricalAlumniClaim::STATUS_APPROVED)
                <form method="POST" action="{{ route('admin.historical-alumni.update', $claim) }}" class="mt-4 space-y-4" data-dashboard-dialog-form data-submit-label="Saving alumni review...">
                    @csrf @method('PATCH')
                    @foreach (['identity_verified' => 'Valid government or accepted ID matches the claimant', 'training_evidence_verified' => 'Original COTC and/or TOR was physically inspected', 'archive_record_verified' => 'MCARE archive/paper record confirms graduation'] as $field => $label)
                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-xs leading-5 text-slate-700">
                            <input type="checkbox" name="{{ $field }}" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    <div>
                        <label class="form-label" for="historical-notes">Verification notes</label>
                        <textarea id="historical-notes" name="admin_notes" rows="4" class="form-field" required placeholder="Record the archive reference, documents inspected, or reason for follow-up.">{{ old('admin_notes', $claim->admin_notes) }}</textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-2">
                        <button type="submit" name="decision" value="reject" class="secondary-action justify-center border-red-200 text-red-700 hover:bg-red-50">Return for follow-up</button>
                        <button type="submit" name="decision" value="approve" class="primary-action justify-center" @disabled(! $claim->user->hasVerifiedEmail())>Verify and activate alumni</button>
                    </div>
                    @unless ($claim->user->hasVerifiedEmail())
                        <p class="text-xs font-semibold text-amber-700">Approval unlocks after the claimant verifies their email.</p>
                    @endunless
                </form>
            @else
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs leading-5 text-emerald-800">
                    <strong class="block">Verified by {{ $claim->onsiteVerifier?->name ?? 'MCARE administrator' }}</strong>
                    {{ $claim->onsite_verified_at?->format('M d, Y g:i A') }}
                    @if (filled($claim->admin_notes))
                        <span class="mt-2 block">{{ $claim->admin_notes }}</span>
                    @endif
                </div>
            @endif
        </aside>
    </div>
@endsection
