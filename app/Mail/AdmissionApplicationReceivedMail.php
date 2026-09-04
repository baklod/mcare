<?php

namespace App\Mail;

use App\Models\AdmissionApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdmissionApplicationReceivedMail extends Mailable
{
    public function __construct(
        public readonly AdmissionApplication $admission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your MCARE application number is '.$this->admission->application_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admission-application-received',
            with: [
                'recipientName' => $this->admission->fullName(),
                'applicationNumber' => $this->admission->application_number,
                'program' => $this->admission->program ?: 'Caregiving NC II',
                'statusUrl' => route('applications.status', [
                    'application_number' => $this->admission->application_number,
                ]),
            ],
        );
    }
}
