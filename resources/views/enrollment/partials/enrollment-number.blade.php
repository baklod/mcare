@php
    $enrollmentNumber = $application->enrollment_number;
@endphp

@if (filled($enrollmentNumber))
    <div class="enrollment-number-card">
        <p>Enrollment number</p>
        <div class="enrollment-number-row">
            <strong id="enrollment-number-value">{{ $enrollmentNumber }}</strong>
            <button type="button" class="secondary-action" data-copy-enrollment-number data-copy-value="{{ $enrollmentNumber }}">Copy</button>
        </div>
        <p>Save this number. If you are not ready to pay now, you can return later on the payments page and enter it there.</p>
    </div>
@endif
