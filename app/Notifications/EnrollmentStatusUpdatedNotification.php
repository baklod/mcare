<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use App\Support\AccountPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class EnrollmentStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public EnrollmentApplication $application,
    ) {
        $this->onQueue('mail');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return filled($notifiable->email)
            ? ['database', 'mail']
            : ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $approved = $this->application->status === EnrollmentApplication::STATUS_APPROVED;
        $denied = $this->application->status === EnrollmentApplication::STATUS_DENIED;
        $needsVerification = ! $notifiable->hasVerifiedEmail();

        return [
            'title' => $approved
                ? 'MCARE account approved'
                : ($denied ? 'Enrollment application not approved' : 'Enrollment status updated'),
            'message' => $approved
                ? ($needsVerification
                    ? 'Your account was verified and approved. Use the email verification link first, then log in to the MCARE trainee portal.'
                    : 'Your account was verified and approved. You can now log in to the MCARE trainee portal.')
                : ($denied
                    ? 'Your '.($this->application->program ?: 'training program').' enrollment application was not approved. Review the administrator note for the reason and next steps.'
                    : 'Your '.($this->application->program ?: 'training program').' application is now '.$this->application->statusLabel().'.'),
            'url' => AccountPortal::urlFor($notifiable),
            'icon' => $this->application->status === EnrollmentApplication::STATUS_APPROVED ? 'badge-check' : 'clipboard-check',
            'enrollment_application_id' => $this->application->id,
            'status' => $this->application->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $needsVerification = ! $notifiable->hasVerifiedEmail();
        $program = $this->application->program ?: 'training program';
        $recipientName = $notifiable->name ?: 'Applicant';

        if ($this->application->status === EnrollmentApplication::STATUS_APPROVED) {
            if ($needsVerification) {
                return (new MailMessage)
                    ->subject('Verify your email to open your approved MCARE account')
                    ->view('mail.enrollment-status', $this->mailViewData(
                        title: 'Verify your email to open your approved MCARE account',
                        heading: 'Your enrollment account is approved',
                        recipientName: $recipientName,
                        intro: 'MCARE administration approved your '.$program.' enrollment account. Verify this email address first, then sign in to the trainee portal with the same Gmail account you used for enrollment.',
                        actionLabel: 'Verify your email address',
                        actionUrl: $this->verificationUrl($notifiable),
                        secondaryActionLabel: 'Sign in to MCARE Hub',
                        secondaryActionUrl: route('login'),
                        closing: 'You can sign in only after this email address is verified.',
                    ));
            }

            return (new MailMessage)
                ->subject('Your MCARE account is approved - you can now log in')
                ->view('mail.enrollment-status', $this->mailViewData(
                    title: 'Your MCARE account is approved',
                    heading: 'Your enrollment account is approved',
                    recipientName: $recipientName,
                    intro: 'MCARE administration approved your '.$program.' enrollment account. Your account is now active. You may sign in and open the trainee portal.',
                    actionLabel: 'Sign in to MCARE Hub',
                    actionUrl: route('login'),
                    closing: 'Use the same verified Gmail account or MCARE credentials from your enrollment.',
                ));
        }

        if ($this->application->status === EnrollmentApplication::STATUS_DENIED) {
            $intro = 'MCARE administration completed the review of your '.$program.' enrollment application and did not approve the account.';
            if ($needsVerification) {
                $intro .= ' If your enrollment payment was already verified, it remains recorded. Verify this email, then sign in to review the decision or resubmit.';
            } else {
                $intro .= ' If your enrollment payment was already verified, it remains recorded. Please contact MCARE administration regarding correction, resubmission, or other next steps.';
            }

            return (new MailMessage)
                ->subject('Important: Your MCARE enrollment application was not approved')
                ->view('mail.enrollment-status', $this->mailViewData(
                    title: 'MCARE enrollment application update',
                    heading: 'Enrollment application update',
                    recipientName: $recipientName,
                    intro: $intro,
                    adminNotes: $this->application->admin_notes,
                    actionLabel: $needsVerification ? 'Verify your email address' : 'Open MCARE',
                    actionUrl: $needsVerification ? $this->verificationUrl($notifiable) : route('landing'),
                    closing: $needsVerification
                        ? 'You can review the decision in MCARE after this email address is verified.'
                        : null,
                ));
        }

        return (new MailMessage)
            ->subject('MCARE enrollment status: '.$this->application->statusLabel())
            ->view('mail.enrollment-status', $this->mailViewData(
                title: 'MCARE enrollment status',
                heading: 'Enrollment status update',
                recipientName: $recipientName,
                intro: 'Your '.$program.' enrollment application status is now '.$this->application->statusLabel().'.',
                adminNotes: $this->application->admin_notes,
                actionLabel: 'Sign in to MCARE Hub',
                actionUrl: route('login'),
                closing: 'Sign in to MCARE to review your enrollment and next steps.',
            ));
    }

    /** @return array<string, mixed> */
    private function mailViewData(
        string $title,
        string $heading,
        string $recipientName,
        string $intro,
        string $actionLabel,
        string $actionUrl,
        ?string $adminNotes = null,
        ?string $secondaryActionLabel = null,
        ?string $secondaryActionUrl = null,
        ?string $closing = null,
    ): array {
        return [
            'title' => $title,
            'heading' => $heading,
            'recipientName' => $recipientName,
            'intro' => $intro,
            'enrollmentNumber' => $this->application->enrollment_number,
            'adminNotes' => $adminNotes,
            'actionLabel' => $actionLabel,
            'actionUrl' => $actionUrl,
            'secondaryActionLabel' => $secondaryActionLabel,
            'secondaryActionUrl' => $secondaryActionUrl,
            'closing' => $closing,
        ];
    }

    private function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
