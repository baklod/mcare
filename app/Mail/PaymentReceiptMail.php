<?php

namespace App\Mail;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReceiptMail extends Mailable
{
    public function __construct(
        public readonly EnrollmentApplication $application,
        public readonly PaymentTransaction $transaction,
    ) {}

    public function envelope(): Envelope
    {
        $number = $this->transaction->or_number
            ?: $this->application->payment_receipt_number
            ?: $this->application->payment_reference
            ?: $this->transaction->reference_number
            ?: (string) $this->transaction->id;

        return new Envelope(
            subject: 'MCARE payment receipt '.$number,
        );
    }

    public function content(): Content
    {
        $fullName = trim(collect([
            $this->application->first_name,
            $this->application->middle_name,
            $this->application->last_name,
            $this->application->extension_name,
        ])->filter()->implode(' '));

        $isOnline = $this->transaction->payment_channel === PaymentTransaction::CHANNEL_ONLINE;
        $isVerified = $this->transaction->status === PaymentTransaction::STATUS_VERIFIED;

        $officialReceiptNumber = $this->transaction->or_number
            ?: $this->application->payment_receipt_number;

        $referenceNumber = $this->application->payment_reference
            ?: ($isOnline ? null : ($this->transaction->ticket_number ?: $this->transaction->reference_number));

        $paymongoPaymentNumber = $isOnline
            ? ($this->application->paymongoPaymentId()
                ?: $this->transaction->reference_number
                ?: data_get($this->application->payment_meta, 'paymongo_payment_id'))
            : null;

        return new Content(
            view: 'mail.payment-receipt',
            with: [
                'recipientName' => $fullName !== '' ? $fullName : 'Applicant',
                'applicationEmail' => $this->application->email,
                'program' => $this->application->program ?: 'Caregiving NC II',
                'enrollmentNumber' => $this->application->enrollment_number,
                'officialReceiptNumber' => $officialReceiptNumber,
                'referenceNumber' => $referenceNumber,
                'paymongoPaymentNumber' => $paymongoPaymentNumber,
                'paymentChannel' => $isOnline ? 'PayMongo' : 'On-site',
                'paymentType' => $this->transaction->typeLabel(),
                'amountPaid' => number_format((float) $this->transaction->amount, 2),
                'amountLabel' => $isVerified ? 'Amount paid' : 'Amount due',
                'totalPaid' => number_format((float) $this->application->total_paid_amount, 2),
                'remainingBalance' => number_format($this->application->remainingBalance(), 2),
                'paidAt' => ($this->transaction->paid_at ?: $this->transaction->verified_at)?->timezone(config('app.timezone'))->format('M d, Y g:i A'),
                'isVerified' => $isVerified,
            ],
        );
    }
}
