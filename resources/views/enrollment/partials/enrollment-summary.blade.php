@php
    $fullName = preg_replace('/\s+/', ' ', trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name)) ?: 'Applicant';
    $batch = $application->batch;
    $programName = $application->program ?: $batch?->program?->name ?: 'Training program';
    $amount = 'PHP '.number_format((float) ($application->downpayment_amount ?: $application->payment_amount ?: 0), 2);
    $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
    $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room to be announced';
    $address = collect([
        $application->street,
        $application->barangay,
        $application->city,
        $application->province,
        $application->region,
        $application->zip_code,
    ])->filter()->implode(', ');
@endphp

<div class="enrollment-lookup-summary">
<dl class="enrollment-status-row">
    <div>
        <dt>Applicant</dt>
        <dd>{{ $fullName }}<span>{{ $application->email }}</span></dd>
    </div>
    <div>
        <dt>Program</dt>
        <dd>{{ $programName }}<span>{{ $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned' }}</span></dd>
    </div>
    <div>
        <dt>Downpayment</dt>
        <dd>{{ $amount }}<span>{{ $application->paymentStatusLabel() }}</span></dd>
    </div>
</dl>

<dl class="enrollment-payment-facts enrollment-lookup-facts">
    <div>
        <dt>Contact</dt>
        <dd>{{ $application->contact_number ?: 'Not on file' }}</dd>
    </div>
    <div>
        <dt>Class</dt>
        <dd>{{ $application->schedule_preference }} · {{ $scheduleLabel }} · {{ $roomLabel }}</dd>
    </div>
    <div>
        <dt>Enrollment status</dt>
        <dd>{{ $application->statusLabel() }}</dd>
    </div>
    @if (filled($address))
        <div>
            <dt>Address</dt>
            <dd>{{ $address }}</dd>
        </div>
    @endif
</dl>
</div>
