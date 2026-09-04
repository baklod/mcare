<?php

namespace App\Mail;

use App\Models\AdmissionApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdmissionApplicationReviewedMail extends Mailable
{
    public function __construct(
        public readonly AdmissionApplication $admission,
    ) {}

    public function envelope(): Envelope
    {
        $number = $this->admission->application_number;

        return new Envelope(
            subject: $this->admission->isApproved()
                ? 'Your MCARE application is approved - '.$number
                : 'MCARE application update - '.$number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->admission->isApproved()
                ? 'mail.admission-application-approved'
                : 'mail.admission-application-denied',
            with: [
                'recipientName' => $this->admission->fullName(),
                'applicationNumber' => $this->admission->application_number,
                'program' => $this->admission->program ?: 'Caregiving NC II',
                'adminNotes' => $this->admission->admin_notes,
                'enrollmentUrl' => $this->admission->enrollmentUrl(),
                'statusUrl' => route('applications.status', [
                    'application_number' => $this->admission->application_number,
                ]),
            ],
        );
    }
}
